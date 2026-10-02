<?php

namespace Tests\Feature;

use App\Actions\Attendances\ConfirmAttendanceAction;
use App\DTOs\AttendanceData;
use App\Enums\OnlineOrderStatus;
use App\Enums\RoleName;
use App\Exceptions\ErpException;
use App\Models\Client;
use App\Models\ClientUser;
use App\Models\Employee;
use App\Models\InventoryItem;
use App\Models\OnlineOrder;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use App\Services\Payments\FakeGateway;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Con el sistema (ERP) conectado: el catálogo y el stock llegan de allá, lo que
 * vende la web se registra allá, y el panel ya no edita lo que es del sistema.
 */
class ErpIntegrationTest extends TestCase
{
    use \Illuminate\Foundation\Testing\RefreshDatabase;

    private const ERP = 'https://sistema.test/api/v1/integration';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        config([
            'izipay.driver' => 'fake',
            'erp.url' => 'https://sistema.test',
            'erp.token' => 'secreto-de-prueba',
        ]);
    }

    /** @return array<string, mixed> Ítem con la forma del catálogo del sistema. */
    private function remote(int $id, array $overrides = []): array
    {
        return $overrides + [
            'id' => $id, 'type' => 'product', 'code' => "PRD-{$id}", 'name' => "Producto {$id}",
            'description' => 'Del sistema', 'category' => 'Suplementos', 'unit' => 'unidad',
            'price' => 40, 'cost' => 20, 'track_stock' => true, 'stock' => 10, 'stock_min' => 2, 'active' => true,
        ];
    }

    private function linkedProduct(int $erpId, float $stock = 10, float $price = 40): InventoryItem
    {
        $item = InventoryItem::factory()->sellable($price)->withStock($stock)->create(['is_published' => true]);
        $item->forceFill(['erp_id' => $erpId])->save();

        return $item;
    }

    private function actingAsAdmin(): User
    {
        $admin = User::factory()->create(['active' => true]);
        $admin->assignRole(RoleName::ADMINISTRADOR->value);
        Sanctum::actingAs($admin);

        return $admin;
    }

    private function paidOrder(InventoryItem $product, int $quantity = 2): OnlineOrder
    {
        $this->actingAs(ClientUser::factory()->create(), 'customer');
        $this->post('/carrito', ['type' => 'product', 'id' => $product->id, 'quantity' => $quantity]);
        $this->post('/checkout', ['fulfillment' => 'pickup', 'recipient_name' => 'Ana Pérez', 'phone' => '987654321'])
            ->assertRedirect();

        $order = OnlineOrder::query()->latest('id')->firstOrFail();
        $checkout = app(FakeGateway::class)->checkout($order);
        $this->post('/checkout/resultado', ['order' => $order->code, 'result' => 'approved', 'signature' => $checkout['approve']]);

        return $order->fresh();
    }

    // ------------------------------------------------- Aviso desde el sistema

    #[Test]
    public function el_aviso_del_sistema_crea_la_copia_sin_publicarla(): void
    {
        $this->withHeader('X-Integration-Token', 'secreto-de-prueba')
            ->postJson('/erp/catalogo', ['items' => [
                $this->remote(7, ['stock' => 12]),
                $this->remote(8, ['type' => 'service', 'name' => 'Limpieza facial', 'category' => 'Faciales', 'price' => 120, 'stock' => null]),
            ]])
            ->assertOk()
            ->assertJson(['actualizados' => 2]);

        $product = InventoryItem::where('erp_id', 7)->firstOrFail();
        $this->assertEquals(12, $product->stock);
        $this->assertSame('Suplementos', $product->category->name);
        $this->assertFalse($product->is_published, 'Lo nuevo llega sin publicar: lo decide la web.');

        $service = Service::where('erp_id', 8)->firstOrFail();
        $this->assertSame('Faciales', $service->category->name);
        $this->assertEquals(120, $service->price);
        $this->assertFalse($service->is_published);
    }

    #[Test]
    public function el_aviso_no_pisa_lo_que_es_de_la_web(): void
    {
        $item = $this->linkedProduct(7);
        $item->update(['description' => 'Texto de la web', 'image_path' => 'catalog/products/foto.jpg']);

        $this->withHeader('X-Integration-Token', 'secreto-de-prueba')
            ->postJson('/erp/catalogo', ['items' => [$this->remote(7, ['name' => 'Nombre nuevo', 'price' => 55, 'stock' => 3])]])
            ->assertOk();

        $item->refresh();
        $this->assertSame('Nombre nuevo', $item->name);
        $this->assertEquals(55, $item->sale_price);
        $this->assertEquals(3, $item->stock);
        $this->assertSame('Texto de la web', $item->description);
        $this->assertSame('catalog/products/foto.jpg', $item->image_path);
        $this->assertTrue($item->is_published);
    }

    #[Test]
    public function el_aviso_exige_el_token_y_sin_sistema_no_existe(): void
    {
        $payload = ['items' => [$this->remote(7)]];

        $this->postJson('/erp/catalogo', $payload)->assertForbidden();
        $this->withHeader('X-Integration-Token', 'otro')->postJson('/erp/catalogo', $payload)->assertForbidden();

        config(['erp.url' => null]);
        $this->withHeader('X-Integration-Token', 'secreto-de-prueba')->postJson('/erp/catalogo', $payload)->assertNotFound();

        $this->assertDatabaseCount('inventory_items', 0);
    }

    #[Test]
    public function sincronizar_desactiva_lo_que_el_sistema_ya_no_tiene(): void
    {
        $sigue = $this->linkedProduct(7);
        $borrado = $this->linkedProduct(9);
        Http::fake([self::ERP.'/catalog*' => Http::response(['data' => [$this->remote(7, ['stock' => 4])]])]);

        $this->artisan('erp:sincronizar')->assertSuccessful();

        $this->assertEquals(4, $sigue->fresh()->stock);
        $this->assertFalse($borrado->fresh()->active, 'Se desactiva, nunca se borra: tiene historial.');
    }

    // --------------------------------------------------------- Alta inicial

    #[Test]
    public function vincular_da_de_alta_lo_existente_una_sola_vez(): void
    {
        $crema = InventoryItem::factory()->sellable(55)->withStock(8)->create(['name' => 'Crema']);
        $facial = Service::factory()->create(['name' => 'Facial', 'price' => 120]);
        $ids = ["WEB-P-{$crema->id}" => 100, "WEB-S-{$facial->id}" => 200];

        Http::fake(function (HttpRequest $request) use ($ids) {
            if (str_ends_with($request->url(), '/products')) {
                return Http::response(['data' => $this->remote($ids[$request['code']], [
                    'code' => $request['code'], 'type' => $request['type'], 'name' => $request['name'],
                    'price' => $request['price'], 'stock' => $request['stock'] ?? null,
                ])]);
            }

            return Http::response(['data' => [
                $this->remote(100, ['name' => 'Crema', 'price' => 55, 'stock' => 8]),
                $this->remote(200, ['type' => 'service', 'name' => 'Facial', 'price' => 120, 'stock' => null]),
            ]]);
        });

        $this->artisan('erp:vincular')->assertSuccessful();
        $this->artisan('erp:vincular')->assertSuccessful();

        $this->assertSame(100, $crema->fresh()->erp_id);
        $this->assertSame(200, $facial->fresh()->erp_id);
        $this->assertDatabaseCount('inventory_items', 1);
        $this->assertDatabaseCount('services', 1);
        Http::assertSent(fn (HttpRequest $r) => str_ends_with($r->url(), '/products') && $r['code'] === "WEB-P-{$crema->id}" && $r['stock'] == 8);
        $this->assertCount(2, Http::recorded(fn (HttpRequest $r) => str_ends_with($r->url(), '/products')), 'La segunda vez no hay nada que enlazar.');
    }

    /**
     * Mientras se da de alta un ítem, el sistema avisa de él por su cuenta; si
     * ese aviso llega antes que la respuesta, debe enlazar la fila original.
     */
    #[Test]
    public function el_aviso_de_un_item_recien_importado_enlaza_la_fila_original(): void
    {
        $crema = InventoryItem::factory()->sellable(55)->withStock(8)->create(['name' => 'Crema', 'is_published' => true]);

        $this->withHeader('X-Integration-Token', 'secreto-de-prueba')
            ->postJson('/erp/catalogo', ['items' => [$this->remote(100, ['code' => "WEB-P-{$crema->id}", 'name' => 'Crema'])]])
            ->assertOk();

        $this->assertDatabaseCount('inventory_items', 1);
        $this->assertSame(100, $crema->fresh()->erp_id);
        $this->assertTrue($crema->fresh()->is_published, 'Sigue siendo la misma fila, con lo de la web intacto.');
    }

    // ---------------------------------------------------------- Tienda web

    #[Test]
    public function un_pedido_pagado_se_manda_al_sistema_que_registra_su_venta(): void
    {
        $crema = $this->linkedProduct(7, stock: 10, price: 40);
        Http::fake([self::ERP.'/orders' => Http::response(['data' => ['order' => ['id' => 1], 'sale' => ['id' => 1], 'stock' => ['7' => 8]]])]);

        $order = $this->paidOrder($crema, 2);

        $this->assertSame(OnlineOrderStatus::PAID, $order->status);
        $this->assertSame(OnlineOrder::ERP_REGISTERED, $order->erp_sale_status);
        $this->assertEquals(8, $crema->fresh()->stock, 'El stock lo devuelve el sistema.');
        $this->assertDatabaseCount('inventory_movements', 0);
        Http::assertSent(fn (HttpRequest $r) => $r->url() === self::ERP.'/orders'
            && $r['code'] === $order->code
            && $r['status'] === 'paid'
            && $r['historical'] === false
            && $r['items'][0]['product_id'] === 7
            && $r['items'][0]['quantity'] == 2
            && $r['customer']['email'] !== null
            && $r->header('X-Integration-Token')[0] === 'secreto-de-prueba');
        // También se mandó al crearse, todavía sin cobrar (para que el sistema lo vea).
        Http::assertSent(fn (HttpRequest $r) => $r->url() === self::ERP.'/orders' && $r['status'] === 'pending_payment');
    }

    #[Test]
    public function si_el_sistema_no_contesta_el_pedido_queda_pagado_y_se_reintenta(): void
    {
        $crema = $this->linkedProduct(7);
        $ok = ['data' => ['order' => ['id' => 1], 'sale' => ['id' => 1], 'stock' => ['7' => 8]]];
        Http::fake([
            // 1) aviso al crearse, 2) cobro (falla), 3) reintento al sincronizar.
            self::ERP.'/orders' => Http::sequence()->push($ok)->pushStatus(500)->push($ok),
            self::ERP.'/orders/statuses*' => Http::response(['data' => []]),
            self::ERP.'/customers' => Http::response(['data' => ['id' => 50, 'code' => 'CLI-000050']]),
            self::ERP.'/catalog*' => Http::response(['data' => [$this->remote(7, ['stock' => 8])]]),
        ]);

        $order = $this->paidOrder($crema, 2);

        $this->assertSame(OnlineOrderStatus::PAID, $order->status);
        $this->assertSame(OnlineOrder::ERP_PENDING, $order->erp_sale_status);
        $this->assertTrue($order->histories()->where('internal', true)->where('note', 'like', '%sistema%')->exists());

        $this->artisan('erp:sincronizar')->assertSuccessful();

        $this->assertSame(OnlineOrder::ERP_REGISTERED, $order->fresh()->erp_sale_status);
    }

    #[Test]
    public function el_seguimiento_de_los_pedidos_se_mueve_en_el_sistema(): void
    {
        $crema = $this->linkedProduct(7);
        Http::fake([self::ERP.'/orders' => Http::response(['data' => ['order' => ['id' => 1], 'sale' => ['id' => 1], 'stock' => ['7' => 8]]])]);
        $order = $this->paidOrder($crema, 2);

        $this->actingAsAdmin();
        $this->postJson("/api/online-orders/{$order->id}/status", ['status' => 'preparing'])->assertStatus(409);

        // El sistema avisa cada paso; el cliente lo ve en su seguimiento.
        $this->withHeader('X-Integration-Token', 'secreto-de-prueba')
            ->postJson("/erp/pedidos/{$order->code}/estado", ['status' => 'preparing', 'note' => 'Empacando', 'user_name' => 'Rosa', 'happened_at' => now()->toIso8601String()])
            ->assertOk();
        $this->withHeader('X-Integration-Token', 'secreto-de-prueba')
            ->postJson("/erp/pedidos/{$order->code}/estado", ['status' => 'cancelled', 'note' => 'Sin stock'])
            ->assertOk();

        $order->refresh();
        $this->assertSame(OnlineOrderStatus::CANCELLED, $order->status);
        $this->assertTrue($order->histories()->where('from_erp', true)->where('note', 'Empacando')->where('actor_name', 'Rosa')->exists());
        $this->assertEquals(8, $crema->fresh()->stock, 'Anulado allá: el stock no se devuelve aquí, llega con el aviso de catálogo del sistema.');
        Http::assertNotSent(fn (HttpRequest $r) => str_contains($r->url(), '/cancel'));
    }

    #[Test]
    public function el_aviso_de_estado_exige_el_token(): void
    {
        $crema = $this->linkedProduct(7);
        Http::fake([self::ERP.'/orders' => Http::response(['data' => ['order' => ['id' => 1], 'sale' => null, 'stock' => []]])]);
        $order = $this->paidOrder($crema, 1);

        $this->postJson("/erp/pedidos/{$order->code}/estado", ['status' => 'preparing'])->assertForbidden();

        config(['erp.token' => null]);
        $this->postJson("/erp/pedidos/{$order->code}/estado", ['status' => 'preparing'])->assertNotFound();
    }

    #[Test]
    public function un_pedido_anterior_a_la_integracion_no_se_registra_al_sincronizar(): void
    {
        $this->linkedProduct(7);
        Http::fake([
            self::ERP.'/catalog*' => Http::response(['data' => [$this->remote(7)]]),
            self::ERP.'/orders/statuses*' => Http::response(['data' => []]),
            self::ERP.'/customers' => Http::response(['data' => ['id' => 50, 'code' => 'CLI-000050']]),
        ]);
        // Pagado antes de conectar el sistema: su stock ya se descontó aquí.
        $order = OnlineOrder::create([
            'code' => 'W-000001', 'client_user_id' => ClientUser::factory()->create()->id,
            'status' => OnlineOrderStatus::PAID, 'fulfillment' => 'pickup', 'recipient_name' => 'Ana', 'phone' => '987654321',
            'subtotal' => 80, 'delivery_fee' => 0, 'total' => 80, 'paid_at' => now(),
        ]);

        $this->artisan('erp:sincronizar')->assertSuccessful();

        $this->assertNull($order->fresh()->erp_sale_status, 'Lo trae erp:migrar como histórico, sin mover stock.');
        Http::assertNotSent(fn (HttpRequest $r) => $r->method() === 'POST' && str_ends_with($r->url(), '/orders'));
    }

    // ---------------------------------------------------------- Atenciones

    #[Test]
    public function los_insumos_de_una_atencion_se_descuentan_en_el_sistema(): void
    {
        $aguja = $this->linkedProduct(7, stock: 100);
        Http::fake([self::ERP.'/consumptions' => Http::response(['data' => ['stock' => ['7' => 98]]])]);

        $attendance = app(ConfirmAttendanceAction::class)->execute(new AttendanceData(
            clientId: Client::factory()->create()->id,
            serviceId: Service::factory()->create()->id,
            employeeId: Employee::factory()->create()->id,
            attendedAt: now()->toDateString(),
            supplies: [['inventory_item_id' => $aguja->id, 'quantity' => 2]],
        ));

        $this->assertEquals(98, $aguja->fresh()->stock);
        $this->assertDatabaseCount('attendance_supplies', 1);
        $this->assertDatabaseCount('inventory_movements', 0);
        Http::assertSent(fn (HttpRequest $r) => $r['reference'] === "atencion-{$attendance->id}"
            && $r['items'][0]['product_id'] === 7
            && $r['items'][0]['quantity'] == 2);
    }

    #[Test]
    public function si_el_sistema_no_tiene_stock_la_atencion_no_se_registra(): void
    {
        $aguja = $this->linkedProduct(7, stock: 100);
        Http::fake([self::ERP.'/consumptions' => Http::response(['message' => 'Stock insuficiente de «Aguja» en el sistema.'], 422)]);

        try {
            app(ConfirmAttendanceAction::class)->execute(new AttendanceData(
                clientId: Client::factory()->create()->id,
                serviceId: Service::factory()->create()->id,
                employeeId: Employee::factory()->create()->id,
                attendedAt: now()->toDateString(),
                supplies: [['inventory_item_id' => $aguja->id, 'quantity' => 2]],
            ));
            $this->fail('Debió rechazarse.');
        } catch (ErpException $e) {
            $this->assertStringContainsString('Aguja', $e->getMessage());
        }

        $this->assertDatabaseCount('attendances', 0);
        $this->assertDatabaseCount('attendance_supplies', 0);
    }

    // ---------------------------------------------------------------- Panel

    #[Test]
    public function el_panel_ya_no_crea_productos_ni_mueve_stock(): void
    {
        $this->actingAsAdmin();
        $item = $this->linkedProduct(7);

        $this->postJson('/api/inventory-items', ['name' => 'Nuevo', 'unit' => 'unidad', 'min_stock' => 0, 'cost' => 1])->assertStatus(409);
        $this->postJson("/api/inventory-items/{$item->id}/adjust", ['type' => 'manual_in', 'quantity' => 5])->assertStatus(409);
        $this->deleteJson("/api/inventory-items/{$item->id}")->assertStatus(409);
        $this->postJson('/api/inventory-categories', ['name' => 'Otra'])->assertStatus(409);
        $this->postJson('/api/services', [])->assertStatus(409);
        $this->postJson('/api/service-categories', ['name' => 'Otra'])->assertStatus(409);

        $this->assertEquals(10, $item->fresh()->stock);
    }

    #[Test]
    public function editar_en_el_panel_solo_cambia_lo_de_la_web(): void
    {
        $this->actingAsAdmin();
        $item = $this->linkedProduct(7, price: 40);
        $service = Service::factory()->create(['price' => 120, 'duration_minutes' => 60]);
        $service->forceFill(['erp_id' => 8])->save();

        $this->putJson("/api/inventory-items/{$item->id}", [
            'name' => 'Otro nombre', 'unit' => 'caja', 'min_stock' => 9, 'cost' => 99, 'sale_price' => 1,
            'description' => 'Descripción web', 'is_sellable' => true,
        ])->assertOk();
        $this->putJson("/api/services/{$service->id}", [
            'name' => 'Otro', 'category_id' => ServiceCategory::factory()->create()->id, 'price' => 1,
            'duration_minutes' => 90, 'description' => 'Para la web',
        ])->assertOk();

        $item->refresh();
        $this->assertNotSame('Otro nombre', $item->name);
        $this->assertEquals(40, $item->sale_price);
        $this->assertSame('Descripción web', $item->description);

        $service->refresh();
        $this->assertEquals(120, $service->price);
        $this->assertSame(90, $service->duration_minutes);
        $this->assertSame('Para la web', $service->description);
    }

    #[Test]
    public function las_ventas_y_los_cobros_se_hacen_en_el_sistema(): void
    {
        $this->actingAsAdmin();
        $service = Service::factory()->create(['price' => 120]);

        $this->postJson('/api/sales', ['items' => [['type' => 'service', 'id' => $service->id, 'quantity' => 1]]])
            ->assertStatus(409)
            ->assertJsonPath('message', 'Las ventas y sus cobros se registran en el sistema: https://sistema.test');
    }

    #[Test]
    public function el_panel_sabe_si_el_sistema_esta_conectado(): void
    {
        $this->actingAsAdmin();

        $this->getJson('/api/erp/status')->assertOk()->assertJsonPath('data.enabled', true);

        config(['erp.token' => null]);
        $this->getJson('/api/erp/status')->assertOk()->assertJsonPath('data.enabled', false);
    }
}
