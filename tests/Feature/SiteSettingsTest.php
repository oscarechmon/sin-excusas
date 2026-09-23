<?php

namespace Tests\Feature;

use App\Enums\RoleName;
use App\Models\StoreSetting;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/** Contacto, redes y scripts editables desde el panel. */
class SiteSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function actingAsRole(RoleName $role): void
    {
        $user = User::factory()->create(['active' => true]);
        $user->assignRole($role->value);
        Sanctum::actingAs($user);
    }

    /** @param array<string,string> $values */
    private function save(array $values): \Illuminate\Testing\TestResponse
    {
        return $this->putJson('/api/site-settings', ['values' => $values]);
    }

    #[Test]
    public function el_administrador_guarda_contacto_y_redes_y_se_ven_en_la_web(): void
    {
        $this->actingAsRole(RoleName::ADMINISTRADOR);

        $this->save([
            'contact_whatsapp' => '51987654321',
            'contact_email' => 'hola@sinexcusas.pe',
            'contact_address' => 'Av. Canaval y Moreyra 290, Lima',
            'contact_phone' => '(01) 555-1234',
            'contact_schedule' => 'Lun a Sáb · 9 a 20 h',
            'social_instagram' => 'https://instagram.com/sinexcusas',
        ])->assertOk()->assertJsonPath('data.values.contact_email', 'hola@sinexcusas.pe');

        $this->get('/')
            ->assertOk()
            ->assertSee('https://wa.me/51987654321')
            ->assertSee('hola@sinexcusas.pe')
            ->assertSee('Av. Canaval y Moreyra 290, Lima')
            ->assertSee('Lun a Sáb · 9 a 20 h')
            ->assertSee('https://instagram.com/sinexcusas');
    }

    #[Test]
    public function sin_whatsapp_configurado_no_se_muestra_el_boton(): void
    {
        $this->actingAsRole(RoleName::ADMINISTRADOR);

        $this->save(['contact_whatsapp' => ''])->assertOk();

        $this->get('/')->assertOk()->assertDontSee('wa.me');
    }

    #[Test]
    public function los_scripts_se_inyectan_en_la_web_publica(): void
    {
        $this->actingAsRole(RoleName::ADMINISTRADOR);

        $this->save([
            'scripts_head' => '<script>window.gtagListo = 1</script>',
            'scripts_body_start' => '<noscript>etiqueta-gtm</noscript>',
            'scripts_body_end' => '<script>window.chatListo = 1</script>',
        ])->assertOk();

        // Sin escapar: es HTML a propósito, y en el punto que corresponde.
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('<script>window.gtagListo = 1</script>', $html);
        $this->assertStringContainsString('<noscript>etiqueta-gtm</noscript>', $html);
        $this->assertStringContainsString('<script>window.chatListo = 1</script>', $html);

        $this->assertLessThan(strpos($html, '</head>'), strpos($html, 'gtagListo'), 'El script de <head> debe ir dentro del head.');
        $this->assertGreaterThan(strpos($html, '<body>'), strpos($html, 'etiqueta-gtm'), 'La etiqueta debe ir tras abrir el body.');
        $this->assertGreaterThan(strpos($html, 'etiqueta-gtm'), strpos($html, 'chatListo'), 'El último script debe ir al final.');
    }

    /** Los scripts de medición no deben cargarse dentro del ERP. */
    #[Test]
    public function los_scripts_no_llegan_al_panel_de_administracion(): void
    {
        $this->actingAsRole(RoleName::ADMINISTRADOR);
        $this->save(['scripts_head' => '<script>window.gtagListo = 1</script>'])->assertOk();

        $this->get('/admin')->assertOk()->assertDontSee('gtagListo', false);
        $this->get('/login')->assertOk()->assertDontSee('gtagListo', false);
    }

    #[Test]
    public function valida_el_numero_de_whatsapp_y_las_urls(): void
    {
        $this->actingAsRole(RoleName::ADMINISTRADOR);

        $this->save(['contact_whatsapp' => '+51 987 654 321'])->assertJsonValidationErrors('values.contact_whatsapp');
        $this->save(['social_instagram' => 'instagram/sinexcusas'])->assertJsonValidationErrors('values.social_instagram');
        $this->save(['contact_email' => 'no-es-un-correo'])->assertJsonValidationErrors('values.contact_email');

        $this->assertNull(StoreSetting::get('contact_whatsapp'));
    }

    #[Test]
    public function recepcion_no_puede_ver_ni_editar_los_ajustes(): void
    {
        $this->actingAsRole(RoleName::RECEPCION);

        $this->getJson('/api/site-settings')->assertForbidden();
        $this->save(['contact_whatsapp' => '51987654321'])->assertForbidden();
    }
}
