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
use Illuminate\Support\Facades\Storage;
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
        // Cada prueba dice qué contesta el sistema; nada sale a la red.
        Http::preventStrayRequests();
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

    /** @param  list<array<string, mixed>>  $items */
    private function catalog(array $items): array
    {
        return ['data' => $items];
    }

    private function linkedProduct(int $erpId, float $stock = 10, float $price = 40, string $name = 'Crema'): InventoryItem
    {
        $item = InventoryItem::factory()->sellable($price)->withStock($stock)->create(['is_published' => true, 'name' => $name]);
        $item->forceFill(['erp_id' => $erpId])->save();

        return $item;
    }

    private function orderPlaced(): array
    {
        return ['data' => ['order' => ['id' => 1], 'sale' => ['id' => 1], 'stock' => []]];
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
    public function el_aviso_del_sistema_enlaza_la_ficha_sin_publicarla_ni_copiar_precio_o_stock(): void
    {
        $this->withHeader('X-Integration-Token', 'secreto-de-prueba')
            ->postJson('/erp/catalogo', ['items' => [
                $this->remote(7, ['stock' => 12, 'price' => 55]),
                $this->remote(8, ['type' => 'service', 'name' => 'Limpieza facial', 'category' => 'Faciales', 'price' => 120, 'stock' => null]),
            ]])
            ->assertOk()
            ->assertJson(['actualizados' => 2]);

        $product = InventoryItem::where('erp_id', 7)->firstOrFail();
        $this->assertSame('Producto 7', $product->name, 'El nombre queda como etiqueta para el panel.');
        $this->assertFalse($product->is_published, 'Lo nuevo llega sin publicar: lo decide la web.');
        $this->assertEquals(0, $product->stock, 'El stock no se copia: se lee del sistema.');
        $this->assertNull($product->sale_price, 'El precio no se copia: se lee del sistema.');
        $this->assertDatabaseHas('inventory_categories', ['name' => 'Suplementos']);

        $service = Service::where('erp_id', 8)->firstOrFail();
        $this->assertSame('Faciales', $service->category->name);
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
    public function lo_que_el_sistema_ya_no_tiene_deja_de_mostrarse(): void
    {
        $this->linkedProduct(7, name: 'Sigue');
        $borrado = $this->linkedProduct(9, name: 'Borrado');
        Http::fake([self::ERP.'/catalog*' => Http::response($this->catalog([$this->remote(7, ['name' => 'Sigue', 'stock' => 4])]))]);

        $this->artisan('erp:sincronizar')->assertSuccessful();

        $this->get('/productos')->assertOk()->assertSee('Sigue')->assertDontSee('Borrado');
        $this->assertModelExists($borrado);
        Http::assertSentCount(1);
    }

    // ------------------------------------------------- Catálogo en vivo

    #[Test]
    public function la_tienda_muestra_el_precio_y_el_stock_del_sistema_no_los_de_la_base(): void
    {
        // La base tiene datos viejos; manda lo que dice el sistema.
        $this->linkedProduct(7, stock: 10, price: 40, name: 'Crema');
        Http::fake([self::ERP.'/catalog*' => Http::response($this->catalog([$this->remote(7, ['name' => 'Crema hidratante', 'price' => 55, 'stock' => 0])]))]);

        $this->get('/productos')
            ->assertOk()
            ->assertSee('Crema hidratante')
            ->assertSee('S/ 55.00')
            ->assertDontSee('S/ 40.00')
            ->assertSee('Agotado');
    }

    #[Test]
    public function la_ficha_del_producto_muestra_lo_del_sistema(): void
    {
        $crema = $this->linkedProduct(7, stock: 10, price: 40, name: 'Crema');
        Http::fake([self::ERP.'/catalog*' => Http::response($this->catalog([$this->remote(7, [
            'name' => 'Crema hidratante', 'price' => 55, 'stock' => 3, 'image_url' => 'https://sistema.test/storage/crema.jpg',
        ])]))]);

        // El enlace con el nombre de la base lleva al nombre que dice el sistema.
        $this->get("/producto/{$crema->id}/crema")->assertStatus(301)->assertRedirect("/producto/{$crema->id}/crema-hidratante");

        $this->get("/producto/{$crema->id}/crema-hidratante")
            ->assertOk()
            ->assertSee('S/ 55.00')
            ->assertDontSee('S/ 40.00')
            ->assertSee('Últimas 3 unidades')
            ->assertSee('https://sistema.test/storage/crema.jpg', false);
    }

    #[Test]
    public function un_cambio_en_el_sistema_se_ve_en_cuanto_llega_su_aviso(): void
    {
        $this->linkedProduct(7);
        Http::fake([self::ERP.'/catalog*' => Http::sequence()
            ->push($this->catalog([$this->remote(7, ['price' => 40])]))
            ->push($this->catalog([$this->remote(7, ['price' => 60])])),
        ]);

        $this->get('/productos')->assertSee('S/ 40.00');
        $this->get('/productos')->assertSee('S/ 40.00');
        Http::assertSentCount(1);

        // El sistema avisa del cambio: la siguiente página lo vuelve a leer.
        $this->withHeader('X-Integration-Token', 'secreto-de-prueba')
            ->postJson('/erp/catalogo', ['items' => [$this->remote(7, ['price' => 60])]])
            ->assertOk();

        $this->get('/productos')->assertSee('S/ 60.00')->assertDontSee('S/ 40.00');
    }

    #[Test]
    public function el_carrito_y_los_servicios_usan_el_precio_del_sistema(): void
    {
        $facial = Service::factory()->create(['name' => 'Facial', 'price' => 100, 'is_published' => true]);
        $facial->forceFill(['erp_id' => 8])->save();
        Http::fake([self::ERP.'/catalog*' => Http::response($this->catalog([
            $this->remote(8, ['type' => 'service', 'name' => 'Facial', 'category' => 'Faciales', 'price' => 130, 'stock' => null]),
        ]))]);

        $this->get('/servicios')->assertOk()->assertSee('Faciales')->assertSee('S/ 130.00');

        $this->post('/carrito', ['type' => 'service', 'id' => $facial->id])->assertSessionHas('success');
        $this->get('/carrito')->assertSee('S/ 130.00')->assertDontSee('S/ 100.00');
    }

    #[Test]
    public function el_pedido_se_arma_con_el_stock_del_momento(): void
    {
        $crema = $this->linkedProduct(7);
        Http::fake([self::ERP.'/catalog*' => Http::sequence()
            ->push($this->catalog([$this->remote(7, ['stock' => 10])]))   // al agregar al carrito
            ->push($this->catalog([$this->remote(7, ['stock' => 2])])),   // al confirmar el pedido
        ]);
        $this->actingAs(ClientUser::factory()->create(), 'customer');

        $this->post('/carrito', ['type' => 'product', 'id' => $crema->id, 'quantity' => 3])->assertSessionHas('success');
        $this->post('/checkout', ['fulfillment' => 'pickup', 'recipient_name' => 'Ana Pérez', 'phone' => '987654321'])
            ->assertSessionHasErrors('cart');

        $this->assertDatabaseCount('online_orders', 0);
    }

    #[Test]
    public function si_se_agota_antes_de_pagar_no_se_muestra_el_cobro(): void
    {
        $crema = $this->linkedProduct(7, name: 'Crema');
        Http::fake([
            self::ERP.'/catalog*' => Http::sequence()
                ->push($this->catalog([$this->remote(7, ['name' => 'Crema', 'stock' => 5])]))   // carrito
                ->push($this->catalog([$this->remote(7, ['name' => 'Crema', 'stock' => 5])]))   // pedido
                ->push($this->catalog([$this->remote(7, ['name' => 'Crema', 'stock' => 0])])),  // pago: lo vendieron en el centro
            self::ERP.'/orders' => Http::response($this->orderPlaced()),
        ]);
        $this->actingAs(ClientUser::factory()->create(), 'customer');
        $this->post('/carrito', ['type' => 'product', 'id' => $crema->id, 'quantity' => 2]);
        $this->post('/checkout', ['fulfillment' => 'pickup', 'recipient_name' => 'Ana Pérez', 'phone' => '987654321'])->assertRedirect();
        $order = OnlineOrder::query()->latest('id')->firstOrFail();

        $this->get("/checkout/pagar/{$order->code}")
            ->assertOk()
            ->assertSee('Crema se agotó')
            ->assertSee('No se realizó ningún cargo')
            ->assertDontSee('Simular pago aprobado');
    }

    #[Test]
    public function si_el_sistema_no_contesta_la_tienda_sigue_con_lo_ultimo_pero_no_deja_comprar(): void
    {
        $crema = $this->linkedProduct(7, name: 'Crema');
        Http::fake([self::ERP.'/catalog*' => Http::sequence()
            ->push($this->catalog([$this->remote(7, ['name' => 'Crema', 'price' => 45])]))
            ->whenEmpty(Http::response(['message' => 'Caído'], 503)),
        ]);

        $this->get('/productos')->assertSee('S/ 45.00');

        // Un aviso obliga a volver a leer, pero el sistema ya no contesta.
        $this->withHeader('X-Integration-Token', 'secreto-de-prueba')
            ->postJson('/erp/catalogo', ['items' => [$this->remote(7, ['name' => 'Crema'])]])
            ->assertOk();
        $this->get('/productos')->assertOk()->assertSee('Crema')->assertSee('S/ 45.00');

        // Se puede armar el carrito, pero el pedido exige confirmar el stock.
        $this->actingAs(ClientUser::factory()->create(), 'customer');
        $this->post('/carrito', ['type' => 'product', 'id' => $crema->id, 'quantity' => 1])->assertSessionHas('success');
        $this->post('/checkout', ['fulfillment' => 'pickup', 'recipient_name' => 'Ana Pérez', 'phone' => '987654321'])
            ->assertRedirect('/carrito')
            ->assertSessionHas('error');

        $this->assertDatabaseCount('online_orders', 0);
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
        Http::fake([
            self::ERP.'/catalog*' => Http::response($this->catalog([$this->remote(7)])),
            self::ERP.'/orders' => Http::response($this->orderPlaced()),
        ]);

        $order = $this->paidOrder($crema, 2);

        $this->assertSame(OnlineOrderStatus::PAID, $order->status);
        $this->assertSame(OnlineOrder::ERP_REGISTERED, $order->erp_sale_status);
        $this->assertEquals(10, $crema->fresh()->stock, 'La web no lleva stock: lo descuenta la venta del sistema.');
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
        Http::fake([
            self::ERP.'/catalog*' => Http::response($this->catalog([$this->remote(7)])),
            self::ERP.'/orders' => Http::response($this->orderPlaced()),
        ]);
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
        Http::assertNotSent(fn (HttpRequest $r) => str_contains($r->url(), '/cancel'));
    }

    #[Test]
    public function el_aviso_de_estado_exige_el_token(): void
    {
        $crema = $this->linkedProduct(7);
        Http::fake([
            self::ERP.'/catalog*' => Http::response($this->catalog([$this->remote(7)])),
            self::ERP.'/orders' => Http::response(['data' => ['order' => ['id' => 1], 'sale' => null, 'stock' => []]]),
        ]);
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

        $this->assertEquals(100, $aguja->fresh()->stock, 'La web no lleva stock: lo descuenta el sistema.');
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
    public function el_panel_ya_no_edita_ni_publica_nada_del_catalogo(): void
    {
        $this->actingAsAdmin();
        $item = $this->linkedProduct(7);
        $item->update(['description' => 'Texto viejo']);
        $service = Service::factory()->create(['duration_minutes' => 60]);
        $service->forceFill(['erp_id' => 8])->save();

        $blocked = [
            ['PUT', "/api/inventory-items/{$item->id}", ['name' => 'Otro', 'unit' => 'caja', 'min_stock' => 0, 'cost' => 1, 'description' => 'Nueva']],
            ['PATCH', "/api/inventory-items/{$item->id}/publish", ['is_published' => false]],
            ['DELETE', "/api/inventory-items/{$item->id}/image", []],
            ['PUT', "/api/services/{$service->id}", ['name' => 'Otro', 'category_id' => ServiceCategory::factory()->create()->id, 'price' => 1, 'duration_minutes' => 90]],
            ['PATCH', "/api/services/{$service->id}/publish", ['is_published' => true]],
        ];

        foreach ($blocked as [$method, $uri, $data]) {
            $this->assertSame(409, $this->json($method, $uri, $data)->status(), "{$method} {$uri} debió quedar en el sistema.");
        }

        $this->assertSame('Texto viejo', $item->fresh()->description);
        $this->assertTrue($item->fresh()->is_published);
        $this->assertSame(60, $service->fresh()->duration_minutes);
    }

    // ------------------------------------------------- La web, un cascarón

    #[Test]
    public function la_web_muestra_lo_que_el_sistema_publica_con_su_foto_y_descripcion(): void
    {
        // Nuevo en el sistema y publicado allá: aunque el aviso se haya perdido, se muestra.
        Http::fake([self::ERP.'/catalog*' => Http::response($this->catalog([
            $this->remote(21, [
                'name' => 'Sérum vitamina C', 'description' => 'Ilumina la piel', 'price' => 89,
                'web_published' => true, 'image_url' => 'https://sistema.test/storage/products/21/serum.webp',
                'category_description' => 'Lo mejor para tu rutina',
            ]),
            $this->remote(22, ['type' => 'service', 'name' => 'Facial profundo', 'category' => 'Faciales', 'price' => 150,
                'stock' => null, 'web_published' => true, 'duration_minutes' => 75]),
            $this->remote(23, ['name' => 'Insumo interno', 'web_published' => false]),
        ]))]);

        $this->get('/productos')
            ->assertOk()
            ->assertSee('Sérum vitamina C')
            ->assertSee('Ilumina la piel')
            ->assertSee('Lo mejor para tu rutina')
            ->assertSee('https://sistema.test/storage/products/21/serum.webp', false)
            ->assertSee('S/ 89.00')
            ->assertDontSee('Insumo interno');

        $this->get('/servicios')->assertOk()->assertSee('Facial profundo')->assertSee('75 min');

        $this->assertSame(21, InventoryItem::where('erp_id', 21)->sole()->erp_id, 'Se enlazó en el momento para el carrito.');
        $this->assertDatabaseMissing('inventory_items', ['erp_id' => 23]);
    }

    #[Test]
    public function si_el_sistema_lo_retira_o_lo_elimina_deja_de_verse(): void
    {
        $this->linkedProduct(7, name: 'Retirada');   // publicada aquí desde antes
        $this->linkedProduct(9, name: 'Eliminada');
        Http::fake([self::ERP.'/catalog*' => Http::response($this->catalog([
            $this->remote(7, ['name' => 'Retirada', 'web_published' => false]),
            $this->remote(9, ['name' => 'Eliminada', 'web_published' => true, 'active' => false]),
        ]))]);

        $this->get('/productos')->assertOk()->assertDontSee('Retirada')->assertDontSee('Eliminada');
    }

    #[Test]
    public function fichas_pasa_al_sistema_lo_que_la_web_mostraba_una_sola_vez(): void
    {
        Storage::fake('public');
        $crema = $this->linkedProduct(7, name: 'Crema');
        $crema->update(['description' => 'Crema de la web', 'image_path' => 'catalog/products/crema.jpg']);
        Storage::disk('public')->put('catalog/products/crema.jpg', 'imagen');
        $yaPasado = $this->linkedProduct(9, name: 'Ya pasado');
        $facial = Service::factory()->create(['duration_minutes' => 45, 'is_published' => true, 'description' => 'Facial web']);
        $facial->forceFill(['erp_id' => 8])->save();
        ServiceCategory::query()->update(['description' => null]);
        $facial->category->update(['description' => 'Para el rostro']);

        Http::fake([
            self::ERP.'/catalog*' => Http::response($this->catalog([
                $this->remote(7, ['web_published' => null]),
                $this->remote(9, ['web_published' => false]),
                $this->remote(8, ['type' => 'service', 'stock' => null, 'web_published' => null]),
            ])),
            self::ERP.'/products/*/web' => Http::response(['data' => []]),
            self::ERP.'/categories/describe' => Http::response(['data' => ['updated' => true]]),
        ]);

        $this->artisan('erp:fichas')->assertSuccessful();

        Http::assertSent(fn (HttpRequest $r) => $r->url() === self::ERP.'/products/7/web'
            && $r->isMultipart()
            && collect($r->data())->contains(fn ($part) => $part['name'] === 'image' && $part['filename'] === 'crema.jpg')
            && collect($r->data())->contains(fn ($part) => $part['name'] === 'web_published' && $part['contents'] === '1')
            && collect($r->data())->contains(fn ($part) => $part['name'] === 'description' && $part['contents'] === 'Crema de la web'));
        Http::assertSent(fn (HttpRequest $r) => $r->url() === self::ERP.'/products/8/web'
            && $r['duration_minutes'] === 45 && $r['web_published'] === true && $r['description'] === 'Facial web');
        Http::assertNotSent(fn (HttpRequest $r) => $r->url() === self::ERP."/products/{$yaPasado->erp_id}/web");
        Http::assertSent(fn (HttpRequest $r) => str_ends_with($r->url(), '/categories/describe') && $r['description'] === 'Para el rostro');
    }

    #[Test]
    public function el_panel_muestra_y_filtra_lo_que_dice_el_sistema(): void
    {
        $this->actingAsAdmin();
        $this->linkedProduct(7, stock: 10, price: 40, name: 'Crema');
        $this->linkedProduct(9, stock: 0, price: 40, name: 'Sérum');
        Http::fake([self::ERP.'/catalog*' => Http::response($this->catalog([
            $this->remote(7, ['name' => 'Crema', 'price' => 55, 'stock' => 1, 'stock_min' => 2]),
            $this->remote(9, ['name' => 'Sérum', 'stock' => 30, 'stock_min' => 2]),
        ]))]);

        $this->getJson('/api/inventory-items')
            ->assertOk()
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('data.0.name', 'Crema')
            ->assertJsonPath('data.0.stock', 1)
            ->assertJsonPath('data.0.sale_price', 55)
            ->assertJsonPath('data.0.category.name', 'Suplementos');

        $this->getJson('/api/inventory-items?low_stock=1')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.name', 'Crema');
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
