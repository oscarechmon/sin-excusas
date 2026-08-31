<?php

namespace Tests\Feature;

use App\Actions\Cash\CloseCashSessionAction;
use App\Actions\Cash\OpenCashSessionAction;
use App\Actions\Cash\RegisterCashExpenseAction;
use App\Actions\Payments\RegisterPaymentAction;
use App\Actions\Sales\CreateSaleAction;
use App\Enums\CashSessionStatus;
use App\Exceptions\CashSessionAlreadyOpenException;
use App\Exceptions\CashSessionNotOpenException;
use App\Models\PaymentMethod;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CashSessionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    #[Test]
    public function abrir_caja_registra_el_monto_inicial_como_movimiento(): void
    {
        $session = app(OpenCashSessionAction::class)->execute(200, $this->user->id);

        $this->assertSame(CashSessionStatus::OPEN, $session->status);
        $this->assertEquals(200, $session->opening_amount);
        $this->assertDatabaseCount('cash_movements', 1);
    }

    #[Test]
    public function no_permite_dos_cajas_abiertas_a_la_vez(): void
    {
        app(OpenCashSessionAction::class)->execute(100, $this->user->id);

        $this->expectException(CashSessionAlreadyOpenException::class);

        app(OpenCashSessionAction::class)->execute(100, $this->user->id);
    }

    #[Test]
    public function no_permite_cerrar_si_no_hay_caja_abierta(): void
    {
        $this->expectException(CashSessionNotOpenException::class);

        app(CloseCashSessionAction::class)->execute(100, $this->user->id);
    }

    #[Test]
    public function un_pago_en_efectivo_entra_a_la_caja_abierta(): void
    {
        app(OpenCashSessionAction::class)->execute(100, $this->user->id);
        $cash = PaymentMethod::factory()->create(['is_cash' => true]);
        $service = Service::factory()->create(['price' => 180]);

        $sale = app(CreateSaleAction::class)->execute([
            'items' => [['type' => 'service', 'id' => $service->id]],
        ]);
        app(RegisterPaymentAction::class)->execute($sale, [
            'payment_method_id' => $cash->id,
            'amount' => 180,
        ], $this->user->id);

        $session = app(CloseCashSessionAction::class)->execute(280, $this->user->id);

        // 100 de apertura + 180 cobrados en efectivo.
        $this->assertEquals(280, $session->expected_amount);
        $this->assertEquals(0, $session->difference);
    }

    #[Test]
    public function un_cobro_por_yape_no_afecta_el_arqueo_en_efectivo(): void
    {
        app(OpenCashSessionAction::class)->execute(100, $this->user->id);
        $yape = PaymentMethod::factory()->create(['is_cash' => false]);
        $service = Service::factory()->create(['price' => 200]);

        $sale = app(CreateSaleAction::class)->execute([
            'items' => [['type' => 'service', 'id' => $service->id]],
        ]);
        app(RegisterPaymentAction::class)->execute($sale, [
            'payment_method_id' => $yape->id,
            'amount' => 200,
        ], $this->user->id);

        $session = app(CloseCashSessionAction::class)->execute(100, $this->user->id);

        // El dinero de Yape no está en el cajón: lo esperado sigue siendo 100.
        $this->assertEquals(100, $session->expected_amount);
        $this->assertEquals(0, $session->difference);
    }

    #[Test]
    public function un_egreso_reduce_lo_esperado_en_caja(): void
    {
        app(OpenCashSessionAction::class)->execute(500, $this->user->id);

        app(RegisterCashExpenseAction::class)->execute(120, 'Compra de insumos', $this->user->id);

        $session = app(CloseCashSessionAction::class)->execute(380, $this->user->id);

        $this->assertEquals(380, $session->expected_amount);
        $this->assertEquals(0, $session->difference);
    }

    #[Test]
    public function conserva_la_diferencia_cuando_el_arqueo_no_cuadra(): void
    {
        app(OpenCashSessionAction::class)->execute(300, $this->user->id);

        // Se contaron 280 donde debían haber 300: faltan 20.
        $session = app(CloseCashSessionAction::class)->execute(280, $this->user->id);

        $this->assertEquals(300, $session->expected_amount);
        $this->assertEquals(280, $session->counted_amount);
        $this->assertEquals(-20, $session->difference);
        $this->assertSame(CashSessionStatus::CLOSED, $session->status);
    }

    #[Test]
    public function no_permite_registrar_un_egreso_sin_caja_abierta(): void
    {
        $this->expectException(CashSessionNotOpenException::class);

        app(RegisterCashExpenseAction::class)->execute(50, 'Taxi', $this->user->id);
    }
}
