<?php

namespace Tests\Feature;

use App\Actions\Payments\RegisterPaymentAction;
use App\Actions\Sales\CreateSaleAction;
use App\Enums\SaleStatus;
use App\Exceptions\InsufficientStockException;
use App\Exceptions\InvalidPaymentAmountException;
use App\Models\Client;
use App\Models\InventoryItem;
use App\Models\Package;
use App\Models\PaymentMethod;
use App\Models\Sale;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SaleAndPaymentTest extends TestCase
{
    use RefreshDatabase;

    private CreateSaleAction $createSale;

    private RegisterPaymentAction $registerPayment;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createSale = app(CreateSaleAction::class);
        $this->registerPayment = app(RegisterPaymentAction::class);
    }

    #[Test]
    public function crea_una_venta_con_varios_conceptos_y_calcula_el_total(): void
    {
        $service = Service::factory()->create(['price' => 150]);
        $product = InventoryItem::factory()->sellable(50)->withStock(10)->create();

        $sale = $this->createSale->execute([
            'client_id' => Client::factory()->create()->id,
            'discount' => 20,
            'items' => [
                ['type' => 'service', 'id' => $service->id, 'quantity' => 1],
                ['type' => 'product', 'id' => $product->id, 'quantity' => 2],
            ],
        ]);

        // 150 + (50 * 2) = 250, menos 20 de descuento.
        $this->assertEquals(250, $sale->subtotal);
        $this->assertEquals(230, $sale->total);
        $this->assertSame(SaleStatus::PENDING, $sale->status);
    }

    #[Test]
    public function vender_un_producto_descuenta_inventario(): void
    {
        $product = InventoryItem::factory()->sellable(50)->withStock(10)->create();

        $this->createSale->execute([
            'items' => [['type' => 'product', 'id' => $product->id, 'quantity' => 3]],
        ]);

        $this->assertEquals(7, $product->fresh()->stock);
    }

    #[Test]
    public function no_permite_vender_un_producto_sin_stock(): void
    {
        $product = InventoryItem::factory()->sellable(50)->withStock(2)->create();

        $this->expectException(InsufficientStockException::class);

        $this->createSale->execute([
            'items' => [['type' => 'product', 'id' => $product->id, 'quantity' => 5]],
        ]);
    }

    #[Test]
    public function vender_un_paquete_crea_el_saldo_de_sesiones_del_cliente(): void
    {
        $client = Client::factory()->create();
        $package = Package::factory()->create(['price' => 900, 'total_sessions' => 6]);

        $this->createSale->execute([
            'client_id' => $client->id,
            'items' => [['type' => 'package', 'id' => $package->id, 'quantity' => 1]],
        ]);

        $this->assertDatabaseHas('client_packages', [
            'client_id' => $client->id,
            'total_sessions' => 6,
            'used_sessions' => 0,
        ]);
    }

    #[Test]
    public function admite_pago_mixto_y_actualiza_el_saldo(): void
    {
        $cash = PaymentMethod::factory()->create(['is_cash' => true]);
        $yape = PaymentMethod::factory()->create(['is_cash' => false]);
        $service = Service::factory()->create(['price' => 250]);

        $sale = $this->createSale->execute([
            'items' => [['type' => 'service', 'id' => $service->id]],
        ]);

        $this->registerPayment->execute($sale, ['payment_method_id' => $yape->id, 'amount' => 100]);
        $this->assertSame(SaleStatus::PARTIAL, $sale->fresh()->status);
        $this->assertEquals(150, $sale->fresh()->balance());

        $this->registerPayment->execute($sale, ['payment_method_id' => $cash->id, 'amount' => 150]);
        $this->assertSame(SaleStatus::PAID, $sale->fresh()->status);
        $this->assertEquals(0, $sale->fresh()->balance());
    }

    /** Caso crítico §47: "no registrar dos pagos por doble clic". */
    #[Test]
    public function no_permite_pagar_mas_que_el_saldo_pendiente(): void
    {
        $method = PaymentMethod::factory()->create();
        $service = Service::factory()->create(['price' => 100]);

        $sale = $this->createSale->execute([
            'items' => [['type' => 'service', 'id' => $service->id]],
        ]);

        $this->registerPayment->execute($sale, ['payment_method_id' => $method->id, 'amount' => 100]);

        // El segundo envío del mismo pago debe rechazarse.
        try {
            $this->registerPayment->execute($sale, ['payment_method_id' => $method->id, 'amount' => 100]);
            $this->fail('Debió rechazar el pago duplicado.');
        } catch (InvalidPaymentAmountException) {
            // esperado
        }

        $this->assertDatabaseCount('payments', 1);
        $this->assertEquals(100, $sale->fresh()->paid_amount);
    }

    #[Test]
    public function no_permite_pagar_una_venta_anulada(): void
    {
        $method = PaymentMethod::factory()->create();
        $service = Service::factory()->create(['price' => 100]);

        $sale = $this->createSale->execute([
            'items' => [['type' => 'service', 'id' => $service->id]],
        ]);
        $sale->update(['status' => SaleStatus::CANCELLED]);

        $this->expectException(InvalidPaymentAmountException::class);

        $this->registerPayment->execute($sale, ['payment_method_id' => $method->id, 'amount' => 50]);
    }

    #[Test]
    public function el_codigo_de_venta_es_correlativo_y_unico(): void
    {
        $service = Service::factory()->create();

        $first = $this->createSale->execute(['items' => [['type' => 'service', 'id' => $service->id]]]);
        $second = $this->createSale->execute(['items' => [['type' => 'service', 'id' => $service->id]]]);

        $this->assertNotSame($first->code, $second->code);
        $this->assertSame(2, Sale::whereIn('code', [$first->code, $second->code])->count());
    }
}
