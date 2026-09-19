<?php

namespace Tests\Feature;

use App\Enums\OnlineOrderStatus;
use App\Enums\RoleName;
use App\Models\ClientUser;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\OnlineOrder;
use App\Models\Service;
use App\Models\StoreSetting;
use App\Models\User;
use App\Services\Payments\FakeGateway;
use App\Services\Shop\Cart;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/** Tienda online: cuenta web, carrito, delivery, pago con Izipay y seguimiento. */
class OnlineStoreTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        config(['izipay.driver' => 'fake']);
    }

    private function product(float $price = 40, float $stock = 10): InventoryItem
    {
        return InventoryItem::factory()->sellable($price)->withStock($stock)->create(['is_published' => true]);
    }

    private function placeOrder(ClientUser $customer, InventoryItem $product, int $quantity = 2, string $fulfillment = 'delivery'): OnlineOrder
    {
        $this->actingAs($customer, 'customer');

        $this->post('/carrito', ['type' => 'product', 'id' => $product->id, 'quantity' => $quantity]);
        $this->post('/checkout', [
            'fulfillment' => $fulfillment,
            'recipient_name' => 'Ana Pérez',
            'phone' => '987654321',
            'address' => 'Av. Larco 123',
            'district' => 'Miraflores',
        ])->assertRedirect();

        return OnlineOrder::query()->latest('id')->firstOrFail();
    }

    private function payWithFakeGateway(OnlineOrder $order, string $result = 'approved'): TestResponse
    {
        $checkout = app(FakeGateway::class)->checkout($order);

        return $this->post('/checkout/resultado', [
            'order' => $order->code,
            'result' => $result,
            'signature' => $checkout[$result === 'approved' ? 'approve' : 'refuse'],
        ]);
    }

    private function actingAsAdmin(): void
    {
        $admin = User::factory()->create(['active' => true]);
        $admin->assignRole(RoleName::ADMINISTRADOR->value);
        Sanctum::actingAs($admin);
    }

    #[Test]
    public function el_registro_crea_la_cuenta_web_y_la_ficha_de_cliente(): void
    {
        $this->post('/cuenta/registro', [
            'name' => 'Ana Pérez',
            'email' => 'ana@example.com',
            'phone' => '987654321',
            'document_number' => '45678912',
            'password' => 'secreto123',
            'password_confirmation' => 'secreto123',
        ])->assertRedirect();

        $this->assertAuthenticated('customer');
        $account = ClientUser::query()->where('email', 'ana@example.com')->firstOrFail();
        $this->assertSame('45678912', $account->client->document_number);
    }

    #[Test]
    public function el_carrito_no_acepta_mas_unidades_que_el_stock(): void
    {
        $product = $this->product(stock: 2);

        $this->post('/carrito', ['type' => 'product', 'id' => $product->id, 'quantity' => 3])
            ->assertSessionHas('error');

        $this->assertSame(0, app(Cart::class)->count());
    }

    #[Test]
    public function el_checkout_con_delivery_suma_el_costo_configurado(): void
    {
        StoreSetting::put('delivery_fee', '12.50');

        $order = $this->placeOrder(ClientUser::factory()->create(), $this->product(price: 40), 2);

        $this->assertEquals(80, $order->subtotal);
        $this->assertEquals(12.5, $order->delivery_fee);
        $this->assertEquals(92.5, $order->total);
        $this->assertSame(OnlineOrderStatus::PENDING_PAYMENT, $order->status);
        $this->assertSame(0, app(Cart::class)->count());
    }

    #[Test]
    public function el_recojo_en_el_centro_no_cobra_delivery(): void
    {
        StoreSetting::put('delivery_fee', '12.50');

        $order = $this->placeOrder(ClientUser::factory()->create(), $this->product(price: 40), 1, 'pickup');

        $this->assertEquals(0, $order->delivery_fee);
        $this->assertEquals(40, $order->total);
        $this->assertNull($order->address);
    }

    #[Test]
    public function un_pedido_solo_de_servicios_no_admite_delivery(): void
    {
        $service = Service::factory()->create(['price' => 100, 'is_published' => true]);
        $this->actingAs(ClientUser::factory()->create(), 'customer');

        $this->post('/carrito', ['type' => 'service', 'id' => $service->id]);
        $this->post('/checkout', [
            'fulfillment' => 'delivery',
            'recipient_name' => 'Ana',
            'phone' => '987654321',
            'address' => 'Av. Larco 123',
            'district' => 'Miraflores',
        ])->assertSessionHasErrors('fulfillment');

        $this->assertSame(0, OnlineOrder::count());
    }

    #[Test]
    public function el_pago_aprobado_confirma_el_pedido_y_descuenta_stock_una_sola_vez(): void
    {
        $product = $this->product(stock: 10);
        $order = $this->placeOrder(ClientUser::factory()->create(), $product, 2);

        $this->payWithFakeGateway($order)->assertRedirect(route('shop.account.order', $order->code));
        // El retorno del navegador y la IPN llegan ambos: no debe descontar dos veces.
        $this->payWithFakeGateway($order);

        $this->assertSame(OnlineOrderStatus::PAID, $order->fresh()->status);
        $this->assertEquals(8, $product->fresh()->stock);
        $this->assertSame(1, InventoryMovement::query()
            ->where('source_type', $order->getMorphClass())
            ->where('source_id', $order->id)
            ->count());
    }

    #[Test]
    public function una_respuesta_con_firma_invalida_no_confirma_el_pago(): void
    {
        $order = $this->placeOrder(ClientUser::factory()->create(), $this->product());

        $this->post('/checkout/resultado', [
            'order' => $order->code,
            'result' => 'approved',
            'signature' => 'manipulada',
        ])->assertRedirect(route('site.home'));

        $this->assertSame(OnlineOrderStatus::PENDING_PAYMENT, $order->fresh()->status);
    }

    #[Test]
    public function un_pago_rechazado_permite_reintentar(): void
    {
        $order = $this->placeOrder(ClientUser::factory()->create(), $this->product());

        $this->payWithFakeGateway($order, 'refused');

        $this->assertSame(OnlineOrderStatus::PAYMENT_FAILED, $order->fresh()->status);
        $this->get("/checkout/pagar/{$order->code}")->assertOk()->assertSee('Simular pago aprobado');
    }

    #[Test]
    public function la_notificacion_de_izipay_valida_la_firma_antes_de_confirmar(): void
    {
        $order = $this->placeOrder(ClientUser::factory()->create(), $this->product());
        config(['izipay.driver' => 'izipay', 'izipay.password' => 'clave-ipn']);

        $answer = json_encode([
            'orderStatus' => 'PAID',
            'orderDetails' => ['orderId' => $order->code, 'orderTotalAmount' => $order->totalInCents()],
            'transactions' => [['uuid' => 'tx-123']],
        ]);

        $this->post('/pagos/izipay/notificacion', ['kr-answer' => $answer, 'kr-hash' => 'falsa'])->assertStatus(400);
        $this->assertSame(OnlineOrderStatus::PENDING_PAYMENT, $order->fresh()->status);

        $this->post('/pagos/izipay/notificacion', [
            'kr-answer' => $answer,
            'kr-hash' => hash_hmac('sha256', $answer, 'clave-ipn'),
        ])->assertOk();

        $order->refresh();
        $this->assertSame(OnlineOrderStatus::PAID, $order->status);
        $this->assertSame('tx-123', $order->payment_reference);
    }

    #[Test]
    public function izipay_no_confirma_si_el_monto_no_coincide(): void
    {
        $order = $this->placeOrder(ClientUser::factory()->create(), $this->product());
        config(['izipay.driver' => 'izipay', 'izipay.password' => 'clave-ipn']);

        $answer = json_encode([
            'orderStatus' => 'PAID',
            'orderDetails' => ['orderId' => $order->code, 'orderTotalAmount' => 100],
            'transactions' => [['uuid' => 'tx-barato']],
        ]);

        $this->post('/pagos/izipay/notificacion', [
            'kr-answer' => $answer,
            'kr-hash' => hash_hmac('sha256', $answer, 'clave-ipn'),
        ])->assertOk();

        $this->assertSame(OnlineOrderStatus::PAYMENT_FAILED, $order->fresh()->status);
    }

    #[Test]
    public function un_cliente_no_puede_ver_pedidos_de_otro(): void
    {
        $order = $this->placeOrder(ClientUser::factory()->create(), $this->product());

        $this->actingAs(ClientUser::factory()->create(), 'customer')
            ->get("/cuenta/pedidos/{$order->code}")
            ->assertNotFound();
    }

    #[Test]
    public function el_personal_avanza_el_estado_y_el_cliente_ve_el_seguimiento(): void
    {
        $customer = ClientUser::factory()->create();
        $order = $this->placeOrder($customer, $this->product());
        $this->payWithFakeGateway($order);

        $this->actingAsAdmin();
        $this->postJson("/api/online-orders/{$order->id}/status", [
            'status' => 'preparing',
            'note' => 'Estamos empaquetando tu pedido',
        ])->assertOk()->assertJsonPath('data.status', 'preparing');

        $this->actingAs($customer, 'customer')
            ->get("/cuenta/pedidos/{$order->code}")
            ->assertOk()
            ->assertSee('En preparación')
            ->assertSee('Estamos empaquetando tu pedido');
    }

    #[Test]
    public function no_se_pueden_saltar_estados_del_seguimiento(): void
    {
        $order = $this->placeOrder(ClientUser::factory()->create(), $this->product());
        $this->payWithFakeGateway($order);

        $this->actingAsAdmin();
        $this->postJson("/api/online-orders/{$order->id}/status", ['status' => 'delivered'])
            ->assertUnprocessable();
    }

    #[Test]
    public function anular_un_pedido_pagado_devuelve_el_stock(): void
    {
        $product = $this->product(stock: 10);
        $order = $this->placeOrder(ClientUser::factory()->create(), $product, 3);
        $this->payWithFakeGateway($order);
        $this->assertEquals(7, $product->fresh()->stock);

        $this->actingAsAdmin();
        $this->postJson("/api/online-orders/{$order->id}/status", ['status' => 'cancelled'])->assertOk();

        $this->assertEquals(10, $product->fresh()->stock);
    }

    #[Test]
    public function recepcion_ve_las_ventas_online_pero_no_cambia_el_costo_de_delivery(): void
    {
        $user = User::factory()->create(['active' => true]);
        $user->assignRole(RoleName::RECEPCION->value);
        Sanctum::actingAs($user);

        $this->getJson('/api/online-orders')->assertOk();
        $this->putJson('/api/store-settings', ['delivery_enabled' => true, 'delivery_fee' => 1])->assertForbidden();
    }
    /** La web agrega por fetch: una sola petición, sin recargar la página. */
    #[Test]
    public function agregar_al_carrito_por_fetch_responde_json_con_el_contador(): void
    {
        $product = $this->product(stock: 5);

        $this->postJson('/carrito', ['type' => 'product', 'id' => $product->id, 'quantity' => 2])
            ->assertOk()
            ->assertJson(['success' => true, 'count' => 2]);

        $this->postJson('/carrito', ['type' => 'product', 'id' => $product->id])
            ->assertOk()
            ->assertJsonPath('count', 3);
    }

    #[Test]
    public function agregar_por_fetch_sin_stock_responde_422_y_no_cambia_el_carrito(): void
    {
        $product = $this->product(stock: 1);

        $this->postJson('/carrito', ['type' => 'product', 'id' => $product->id, 'quantity' => 3])
            ->assertStatus(422)
            ->assertJson(['success' => false, 'count' => 0]);
    }
}
