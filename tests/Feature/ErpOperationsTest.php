<?php

namespace Tests\Feature;

use App\Enums\OnlineOrderStatus;
use App\Enums\RoleName;
use App\Models\Client;
use App\Models\ClientPackage;
use App\Models\ClientUser;
use App\Models\Employee;
use App\Models\InventoryItem;
use App\Models\OnlineOrder;
use App\Models\Package;
use App\Models\PaymentMethod;
use App\Models\Sale;
use App\Models\Service;
use App\Models\User;
use App\Services\Erp\OnlineOrderRegistrar;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Con el sistema conectado, toda la operación pasa allá: este panel queda para
 * la web, los clientes de la tienda se enlazan, los paquetes llegan del
 * sistema y el historial completo se lleva con `erp:migrar`.
 */
class ErpOperationsTest extends TestCase
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

    private function actingAsAdmin(): User
    {
        $admin = User::factory()->create(['active' => true]);
        $admin->assignRole(RoleName::ADMINISTRADOR->value);
        Sanctum::actingAs($admin);

        return $admin;
    }

    private function linkedService(int $erpId, float $price = 150): Service
    {
        $service = Service::factory()->create(['price' => $price]);
        $service->forceFill(['erp_id' => $erpId])->save();

        return $service;
    }

    // ------------------------------------------------------------- Panel

    #[Test]
    public function el_panel_ya_no_opera_lo_que_paso_al_sistema(): void
    {
        $this->actingAsAdmin();
        $client = Client::factory()->create();
        $service = $this->linkedService(8);
        $employee = Employee::factory()->create();
        $package = Package::factory()->create();

        $blocked = [
            ['POST', '/api/clients', ['full_name' => 'Nueva']],
            ['PUT', "/api/clients/{$client->id}", ['full_name' => 'Otro']],
            ['DELETE', "/api/clients/{$client->id}", []],
            ['POST', '/api/appointments', ['client_id' => $client->id]],
            ['POST', '/api/attendances', ['client_id' => $client->id]],
            ['POST', '/api/employees', ['name' => 'Rosa']],
            ['DELETE', "/api/employees/{$employee->id}", []],
            ['POST', '/api/packages', ['name' => 'Nuevo']],
            ['DELETE', "/api/packages/{$package->id}", []],
            ['POST', '/api/packages/sell', ['client_id' => $client->id, 'package_id' => $package->id]],
            ['POST', '/api/cash-sessions/open', ['opening_amount' => 0]],
            ['POST', '/api/cash-sessions/expenses', ['amount' => 5, 'description' => 'x']],
            ['POST', '/api/commission-rules', ['type' => 'fixed', 'value' => 5]],
            ['POST', '/api/commissions/pay', ['ids' => [1]]],
            // También lo que antes era de la web: qué se publica y la descripción.
            ['PATCH', "/api/packages/{$package->id}/publish", ['is_published' => true]],
            ['PUT', "/api/packages/{$package->id}", ['name' => 'Otro', 'price' => 1, 'total_sessions' => 99, 'description' => 'Web', 'service_ids' => [$service->id]]],
        ];

        foreach ($blocked as [$method, $uri, $data]) {
            $this->assertSame(409, $this->json($method, $uri, $data)->status(), "{$method} {$uri} debió quedar en el sistema.");
        }

        $this->assertSame('Los clientes se administran en el sistema: https://sistema.test',
            $this->postJson('/api/clients', [])->json('message'));

        $this->assertSame(10, $package->fresh()->total_sessions);
    }

    #[Test]
    public function sin_sistema_el_panel_funciona_como_antes(): void
    {
        config(['erp.token' => null]);
        $this->actingAsAdmin();

        $this->postJson('/api/clients', ['full_name' => 'Ana Pérez'])->assertCreated();
    }

    // ------------------------------------------------- Clientes de la tienda

    #[Test]
    public function quien_se_registra_en_la_tienda_queda_enlazado_con_su_ficha_del_sistema(): void
    {
        Http::fake([self::ERP.'/customers' => Http::response(['data' => ['id' => 41, 'code' => 'CLI-000041']])]);

        $this->post('/cuenta/registro', [
            'name' => 'Lucía Ramos', 'email' => 'lucia@example.com', 'phone' => '987654321',
            'document_number' => '45678912', 'password' => 'secreta-123', 'password_confirmation' => 'secreta-123',
        ])->assertRedirect();

        $client = Client::where('email', 'lucia@example.com')->sole();
        $this->assertSame(41, $client->erp_id);
        Http::assertSent(fn (HttpRequest $r) => $r->url() === self::ERP.'/customers'
            && $r['web_id'] === $client->id && $r['document_number'] === '45678912');
    }

    #[Test]
    public function si_el_sistema_no_contesta_el_cliente_se_enlaza_al_sincronizar(): void
    {
        Http::fake([
            self::ERP.'/customers' => Http::sequence()->pushStatus(503)->push(['data' => ['id' => 41, 'code' => 'CLI-000041']]),
            self::ERP.'/catalog*' => Http::response(['data' => []]),
            self::ERP.'/orders/statuses*' => Http::response(['data' => []]),
        ]);

        $this->post('/cuenta/registro', [
            'name' => 'Lucía Ramos', 'email' => 'lucia@example.com', 'phone' => '987654321',
            'password' => 'secreta-123', 'password_confirmation' => 'secreta-123',
        ])->assertRedirect();

        $client = Client::where('email', 'lucia@example.com')->sole();
        $this->assertNull($client->erp_id, 'El registro no falla si el sistema no contesta.');

        $this->artisan('erp:sincronizar')->assertSuccessful();

        $this->assertSame(41, $client->fresh()->erp_id);
    }

    // -------------------------------------------------------------- Paquetes

    #[Test]
    public function los_paquetes_llegan_del_sistema_sin_publicar_y_conservan_lo_de_la_web(): void
    {
        $facial = $this->linkedService(8);
        $original = Package::factory()->create(['name' => 'Viejo', 'is_published' => true]);

        $item = fn (int $id, string $code, array $extra = []) => $extra + [
            'id' => $id, 'type' => 'package', 'code' => $code, 'name' => '6 faciales', 'description' => 'Del sistema',
            'price' => 500, 'active' => true, 'package' => ['total_sessions' => 6, 'validity_days' => 90, 'service_ids' => [8]],
        ];

        $this->withHeader('X-Integration-Token', 'secreto-de-prueba')->postJson('/erp/catalogo', ['items' => [
            $item(30, 'PKG-000001'),
            // Nacido aquí e importado: su código apunta a la fila original.
            $item(31, "WEB-K-{$original->id}", ['name' => 'Viejo renombrado']),
        ]])->assertOk();

        $nuevo = Package::where('erp_id', 30)->sole();
        $this->assertFalse($nuevo->is_published, 'Lo nuevo llega sin publicar.');

        // Sesiones, vigencia y servicios no se copian: se leen del sistema.
        Http::fake([self::ERP.'/catalog*' => Http::response(['data' => [
            $item(30, 'PKG-000001'),
            ['id' => 8, 'type' => 'service', 'code' => 'SRV-8', 'name' => 'Facial', 'category' => 'Faciales', 'price' => 150, 'active' => true],
        ]])]);
        $this->actingAsAdmin();
        $this->getJson("/api/packages/{$nuevo->id}")
            ->assertOk()
            ->assertJsonPath('data.total_sessions', 6)
            ->assertJsonPath('data.validity_days', 90)
            ->assertJsonPath('data.price', 500)
            ->assertJsonPath('data.services.0.id', $facial->id);

        $original->refresh();
        $this->assertSame(31, $original->erp_id);
        $this->assertTrue($original->is_published, 'Lo publicado sigue publicado.');
        $this->assertSame('Viejo renombrado', $original->name);
        $this->assertSame(2, Package::count(), 'No se duplica el paquete original.');
    }

    // --------------------------------------------------- Seguimiento de pedidos

    #[Test]
    public function sincronizar_trae_el_seguimiento_que_no_llego(): void
    {
        $order = OnlineOrder::create([
            'code' => 'W-000017', 'client_user_id' => ClientUser::factory()->create()->id,
            'status' => OnlineOrderStatus::PAID, 'fulfillment' => 'delivery', 'recipient_name' => 'Ana', 'phone' => '987654321',
            'address' => 'Av. 1', 'district' => 'Miraflores', 'subtotal' => 100, 'delivery_fee' => 10, 'total' => 110, 'paid_at' => now(),
        ]);
        $order->forceFill(['erp_sale_status' => OnlineOrder::ERP_REGISTERED])->save();

        Http::fake([
            self::ERP.'/catalog*' => Http::response(['data' => []]),
            self::ERP.'/customers' => Http::response(['data' => ['id' => 1, 'code' => 'CLI-000001']]),
            self::ERP.'/orders/statuses*' => Http::response(['data' => [[
                'code' => 'W-000017', 'status' => 'shipped', 'history' => [
                    ['status' => 'preparing', 'note' => null, 'user_name' => 'Rosa', 'happened_at' => now()->subHour()->toIso8601String()],
                    ['status' => 'shipped', 'note' => 'Va con Juan', 'user_name' => 'Rosa', 'happened_at' => now()->toIso8601String()],
                ],
            ]]]),
        ]);

        $this->artisan('erp:sincronizar')->assertSuccessful();
        $this->artisan('erp:sincronizar')->assertSuccessful();

        $order->refresh();
        $this->assertSame(OnlineOrderStatus::SHIPPED, $order->status);
        $this->assertSame(2, $order->histories()->where('from_erp', true)->count(), 'Sincronizar dos veces no duplica los pasos.');
    }

    // ------------------------------------------------------------- Migración

    #[Test]
    public function migrar_lleva_todo_el_historial_al_sistema_en_orden(): void
    {
        $facial = $this->linkedService(8, 150);
        $gel = InventoryItem::factory()->sellable(40)->withStock(5)->create();
        $gel->forceFill(['erp_id' => 9])->save();

        $user = User::factory()->create(['active' => true]);
        $user->assignRole(RoleName::ADMINISTRADOR->value);
        $client = Client::factory()->create(['allergies' => 'Látex']);
        $package = Package::factory()->create();
        $package->services()->sync([$facial->id]);
        ClientPackage::factory()->create(['client_id' => $client->id, 'package_id' => $package->id]);
        $sale = Sale::create(['code' => 'V-000001', 'client_id' => $client->id, 'subtotal' => 150, 'discount' => 0, 'total' => 150, 'paid_amount' => 100, 'status' => 'partial', 'created_by' => $user->id]);
        $sale->items()->create(['itemable_type' => Service::class, 'itemable_id' => $facial->id, 'description' => $facial->name, 'unit_price' => 150, 'quantity' => 1, 'discount' => 0, 'subtotal' => 150]);
        $sale->payments()->create(['payment_method_id' => PaymentMethod::factory()->create(['code' => 'yape'])->id, 'amount' => 100, 'paid_at' => now()]);
        OnlineOrder::create([
            'code' => 'W-000001', 'client_user_id' => ClientUser::factory()->create(['client_id' => $client->id])->id, 'client_id' => $client->id,
            'status' => OnlineOrderStatus::DELIVERED, 'fulfillment' => 'pickup', 'recipient_name' => 'Ana', 'phone' => '987654321',
            'subtotal' => 40, 'delivery_fee' => 0, 'total' => 40, 'paid_at' => now(),
        ])->items()->create(['itemable_type' => InventoryItem::class, 'itemable_id' => $gel->id, 'item_type' => 'product', 'name' => 'Gel', 'unit_price' => 40, 'quantity' => 1, 'subtotal' => 40]);

        $kinds = [];
        Http::fake(function (HttpRequest $r) use (&$kinds, $client, $package) {
            if (preg_match('#/import/([a-z_]+)$#', $r->url(), $m)) {
                $kinds[] = $m[1];
                $links = match ($m[1]) {
                    'customers' => [$client->id => 77],
                    'packages' => [$package->id => 300],
                    default => collect($r['records'])->mapWithKeys(fn ($rec) => [$rec['id'] => $rec['id'] + 1000])->all(),
                };

                return Http::response(['data' => ['links' => $links, 'created' => count($r['records']), 'existing' => 0]]);
            }

            return Http::response(['data' => ['order' => ['id' => 1], 'sale' => ['id' => 5], 'stock' => []]]);
        });

        $this->artisan('erp:migrar')->assertSuccessful();

        // En orden de dependencia; lo que no tiene registros no se manda.
        $this->assertSame(['users', 'customers', 'packages', 'sales', 'customer_packages'], $kinds);

        $this->assertSame(77, $client->fresh()->erp_id);
        $this->assertSame(300, $package->fresh()->erp_id);

        Http::assertSent(fn (HttpRequest $r) => str_ends_with($r->url(), '/import/users')
            && $r['records'][0]['email'] === $user->email
            && str_starts_with($r['records'][0]['password_hash'], '$2y$')
            && $r['records'][0]['roles'] === [RoleName::ADMINISTRADOR->value]);
        Http::assertSent(fn (HttpRequest $r) => str_ends_with($r->url(), '/import/customers')
            && $r['records'][0]['allergies'] === 'Látex');
        Http::assertSent(fn (HttpRequest $r) => str_ends_with($r->url(), '/import/sales')
            && $r['records'][0]['code'] === 'V-000001'
            && $r['records'][0]['items'][0]['product_id'] === 8
            && $r['records'][0]['payments'][0]['method'] === 'yape');
        Http::assertSent(fn (HttpRequest $r) => str_ends_with($r->url(), '/orders')
            && $r['code'] === 'W-000001' && $r['historical'] === true && $r['items'][0]['product_id'] === 9);
    }

    #[Test]
    public function migrar_exige_el_catalogo_enlazado(): void
    {
        Service::factory()->create();
        Http::fake();

        $this->artisan('erp:migrar')->assertFailed();

        Http::assertNothingSent();
    }

    #[Test]
    public function el_pedido_que_se_manda_no_devuelve_al_sistema_su_propio_seguimiento(): void
    {
        $order = OnlineOrder::create([
            'code' => 'W-000002', 'client_user_id' => ClientUser::factory()->create()->id,
            'status' => OnlineOrderStatus::SHIPPED, 'fulfillment' => 'delivery', 'recipient_name' => 'Ana', 'phone' => '987654321',
            'subtotal' => 100, 'delivery_fee' => 10, 'total' => 110, 'paid_at' => now(),
        ]);
        $order->recordStatus(OnlineOrderStatus::PAID, 'Pago confirmado.');
        $order->histories()->create(['status' => OnlineOrderStatus::SHIPPED, 'from_erp' => true, 'actor_name' => 'Rosa']);

        $snapshot = app(OnlineOrderRegistrar::class)->snapshot($order->fresh());

        $this->assertSame(['paid'], array_column($snapshot['history'], 'status'));
    }
}
