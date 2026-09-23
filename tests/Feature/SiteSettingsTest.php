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

    /** @param array<string,array<string,mixed>> $days */
    private function hours(array $days): array
    {
        $base = ['open' => false, 'from' => '09:00', 'to' => '20:00'];

        return collect(['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'])
            ->mapWithKeys(fn (string $day) => [$day => array_merge($base, $days[$day] ?? [])])
            ->all();
    }

    #[Test]
    public function el_horario_se_guarda_por_dia_y_se_resume_en_la_web(): void
    {
        $this->actingAsRole(RoleName::ADMINISTRADOR);

        $this->save([
            'contact_schedule' => 'Atención con cita previa',
            'contact_hours' => $this->hours([
                'mon' => ['open' => true, 'from' => '09:00', 'to' => '20:00'],
                'tue' => ['open' => true, 'from' => '09:00', 'to' => '20:00'],
                'wed' => ['open' => true, 'from' => '09:00', 'to' => '20:00'],
                'thu' => ['open' => true, 'from' => '09:00', 'to' => '20:00'],
                'fri' => ['open' => true, 'from' => '09:00', 'to' => '20:00'],
                'sat' => ['open' => true, 'from' => '10:00', 'to' => '14:00'],
            ]),
        ])->assertOk()->assertJsonPath('data.values.contact_hours.sun.open', false);

        // Los días seguidos con el mismo horario se juntan; el domingo no sale.
        $this->get('/')
            ->assertOk()
            ->assertSee('Atención con cita previa · Lun a Vie 9:00 a 20:00 · Sábado 10:00 a 14:00')
            ->assertSee('Lun a Vie')
            ->assertDontSee('Domingo');
    }

    #[Test]
    public function sin_dias_abiertos_no_se_muestra_horario(): void
    {
        $this->actingAsRole(RoleName::ADMINISTRADOR);

        $this->save(['contact_schedule' => '', 'contact_hours' => $this->hours([])])->assertOk();

        $this->get('/')->assertOk()->assertDontSee('Horario');
    }

    #[Test]
    public function el_cierre_no_puede_ser_anterior_a_la_apertura(): void
    {
        $this->actingAsRole(RoleName::ADMINISTRADOR);

        $this->save(['contact_hours' => $this->hours(['mon' => ['open' => true, 'from' => '18:00', 'to' => '09:00']])])
            ->assertJsonValidationErrors('values.contact_hours.mon.to');

        $this->save(['contact_hours' => $this->hours(['mon' => ['open' => true, 'from' => '9am', 'to' => '20:00']])])
            ->assertJsonValidationErrors('values.contact_hours.mon.from');
    }

    #[Test]
    public function las_palabras_clave_salen_en_la_etiqueta_meta(): void
    {
        $this->actingAsRole(RoleName::ADMINISTRADOR);

        $this->save(['seo_keywords' => ' centro estético Lima ,, tratamientos faciales ,centro estético Lima '])->assertOk();

        // Se limpian espacios y repetidas antes de publicarlas.
        $this->get('/')
            ->assertOk()
            ->assertSee('<meta name="keywords" content="centro estético Lima, tratamientos faciales">', false);

        $this->save(['seo_keywords' => ''])->assertOk();
        $this->get('/')->assertOk()->assertDontSee('name="keywords"', false);
    }

    #[Test]
    public function recepcion_no_puede_ver_ni_editar_los_ajustes(): void
    {
        $this->actingAsRole(RoleName::RECEPCION);

        $this->getJson('/api/site-settings')->assertForbidden();
        $this->save(['contact_whatsapp' => '51987654321'])->assertForbidden();
    }
}
