<?php

namespace Tests\Unit;

use App\Enums\CommissionType;
use App\Enums\SaleStatus;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Cálculos puros: no tocan la base de datos, por eso son tests unitarios.
 */
class CommissionCalculationTest extends TestCase
{
    #[Test]
    public function calcula_comision_por_porcentaje(): void
    {
        $this->assertSame(30.0, CommissionType::PERCENTAGE->calculate(300, 10));
        $this->assertSame(45.5, CommissionType::PERCENTAGE->calculate(130, 35));
    }

    #[Test]
    public function calcula_comision_por_monto_fijo_ignorando_la_base(): void
    {
        $this->assertSame(25.0, CommissionType::FIXED->calculate(300, 25));
        $this->assertSame(25.0, CommissionType::FIXED->calculate(1000, 25));
    }

    #[Test]
    public function redondea_a_dos_decimales(): void
    {
        // 333.33 * 7% = 23.3331 -> debe quedar en 23.33 y no arrastrar decimales.
        $this->assertSame(23.33, CommissionType::PERCENTAGE->calculate(333.33, 7));
    }

    #[Test]
    public function nunca_devuelve_una_comision_negativa(): void
    {
        $this->assertSame(0.0, CommissionType::FIXED->calculate(100, -50));
        $this->assertSame(0.0, CommissionType::PERCENTAGE->calculate(-100, 10));
    }

    #[Test]
    public function el_estado_de_la_venta_se_deriva_de_los_montos(): void
    {
        $this->assertSame(SaleStatus::PENDING, SaleStatus::fromAmounts(200, 0));
        $this->assertSame(SaleStatus::PARTIAL, SaleStatus::fromAmounts(200, 120));
        $this->assertSame(SaleStatus::PAID, SaleStatus::fromAmounts(200, 200));
        // Un sobrepago no debe dejar la venta como "parcial".
        $this->assertSame(SaleStatus::PAID, SaleStatus::fromAmounts(200, 250));
    }
}
