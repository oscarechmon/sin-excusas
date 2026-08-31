<?php

namespace Tests\Feature;

use App\Enums\PackageStatus;
use App\Exceptions\PackageWithoutRemainingSessionsException;
use App\Models\ClientPackage;
use App\Services\PackageSessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PackageSessionTest extends TestCase
{
    use RefreshDatabase;

    private PackageSessionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(PackageSessionService::class);
    }

    #[Test]
    public function consumir_una_sesion_descuenta_del_saldo_y_deja_registro(): void
    {
        $package = ClientPackage::factory()->withSessions(10)->create();

        $session = $this->service->consumeSession($package);

        $this->assertSame(1, $session->session_number);
        $this->assertSame(9, $package->fresh()->remainingSessions());
        $this->assertDatabaseCount('client_package_sessions', 1);
    }

    #[Test]
    public function el_paquete_se_marca_terminado_al_consumir_la_ultima_sesion(): void
    {
        $package = ClientPackage::factory()->withSessions(2, 1)->create();

        $this->service->consumeSession($package);

        $this->assertSame(PackageStatus::COMPLETED, $package->fresh()->status);
        $this->assertSame(0, $package->fresh()->remainingSessions());
    }

    #[Test]
    public function no_permite_consumir_sin_sesiones_restantes(): void
    {
        $package = ClientPackage::factory()->withSessions(3, 3)->create();

        $this->expectException(PackageWithoutRemainingSessionsException::class);

        $this->service->consumeSession($package);
    }

    #[Test]
    public function no_permite_consumir_un_paquete_vencido(): void
    {
        $package = ClientPackage::factory()->withSessions(10)->expired()->create();

        $this->expectException(PackageWithoutRemainingSessionsException::class);

        $this->service->consumeSession($package);
    }

    /**
     * Caso crítico §47: "no consumir dos sesiones accidentalmente".
     *
     * Se consumen todas las sesiones y se intenta una más: el saldo no puede
     * quedar negativo ni crearse una sesión extra.
     */
    #[Test]
    public function no_consume_de_mas_al_agotar_el_paquete(): void
    {
        $package = ClientPackage::factory()->withSessions(3)->create();

        foreach (range(1, 3) as $ignored) {
            $this->service->consumeSession($package);
        }

        try {
            $this->service->consumeSession($package);
            $this->fail('Debió rechazar la cuarta sesión.');
        } catch (PackageWithoutRemainingSessionsException) {
            // esperado
        }

        $this->assertSame(3, $package->fresh()->used_sessions);
        $this->assertDatabaseCount('client_package_sessions', 3);
    }

    #[Test]
    public function devolver_una_sesion_reactiva_el_paquete(): void
    {
        $package = ClientPackage::factory()->withSessions(2, 1)->create();
        $session = $this->service->consumeSession($package);

        $this->assertSame(PackageStatus::COMPLETED, $package->fresh()->status);

        $this->service->restoreSession($session);

        $fresh = $package->fresh();
        $this->assertSame(PackageStatus::ACTIVE, $fresh->status);
        $this->assertSame(1, $fresh->used_sessions);
    }
}
