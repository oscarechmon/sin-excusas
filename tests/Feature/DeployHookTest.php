<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/** Gancho de despliegue: migra y cachea tras la subida por FTP. */
class DeployHookTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function sin_token_configurado_la_ruta_no_existe(): void
    {
        config(['deploy.token' => null]);

        $this->post('/deploy/optimize', [], ['X-Deploy-Token' => 'lo-que-sea'])->assertNotFound();
    }

    #[Test]
    public function un_token_incorrecto_es_rechazado_sin_ejecutar_nada(): void
    {
        config(['deploy.token' => 'secreto-correcto']);
        Artisan::shouldReceive('call')->never();

        $this->post('/deploy/optimize', [], ['X-Deploy-Token' => 'otro'])->assertForbidden();
    }

    #[Test]
    public function con_el_token_correcto_limpia_migra_y_cachea_en_ese_orden(): void
    {
        config(['deploy.token' => 'secreto-correcto']);

        // Se simula Artisan: ejecutar `optimize` de verdad dejaría la
        // configuración en caché y contaminaría el resto de los tests.
        Artisan::shouldReceive('call')->once()->with('optimize:clear', [])->ordered();
        Artisan::shouldReceive('call')->once()->with('migrate', ['--force' => true])->ordered();
        Artisan::shouldReceive('call')->once()->with('optimize', [])->ordered();
        Artisan::shouldReceive('output')->times(3)->andReturn('hecho');

        $this->post('/deploy/optimize', [], ['X-Deploy-Token' => 'secreto-correcto'])
            ->assertOk()
            ->assertSee('php artisan migrate');
    }
}
