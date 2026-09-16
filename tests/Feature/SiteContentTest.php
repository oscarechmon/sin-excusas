<?php

namespace Tests\Feature;

use App\Enums\RoleName;
use App\Models\SiteContent;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/** Contenido web: fotos y textos de Inicio y Nosotros editables desde el panel. */
class SiteContentTest extends TestCase
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

    #[Test]
    public function la_portada_usa_los_textos_por_defecto_mientras_no_se_editen(): void
    {
        $this->get('/')->assertOk()->assertSee('Limpieza, hidratación, peeling y firmeza.');
    }

    #[Test]
    public function el_administrador_sube_la_foto_de_una_linea_y_aparece_en_la_portada(): void
    {
        Storage::fake('public');
        $this->actingAsRole(RoleName::ADMINISTRADOR);

        $this->post('/api/site-contents/home.line.faciales/image', [
            'image' => UploadedFile::fake()->create('faciales.jpg', 200, 'image/jpeg'),
        ], ['Accept' => 'application/json'])->assertOk();

        $path = SiteContent::query()->findOrFail('home.line.faciales')->image_path;
        Storage::disk('public')->assertExists($path);

        // La portada deja de mostrar el marcador y usa la foto.
        $this->get('/')->assertOk()->assertSee($path)->assertDontSee('Foto: faciales');
    }

    #[Test]
    public function el_administrador_edita_titulo_y_texto_de_una_linea(): void
    {
        $this->actingAsRole(RoleName::ADMINISTRADOR);

        $this->patchJson('/api/site-contents/home.line.podologia', [
            'title' => 'Podología clínica',
            'text' => 'Atención especializada del pie.',
        ])->assertOk()->assertJsonPath('data.title', 'Podología clínica');

        $this->get('/')->assertOk()->assertSee('Atención especializada del pie.');
    }

    #[Test]
    public function la_foto_de_nosotros_reemplaza_al_marcador(): void
    {
        Storage::fake('public');
        $this->actingAsRole(RoleName::ADMINISTRADOR);

        $this->post('/api/site-contents/about.photo/image', [
            'image' => UploadedFile::fake()->create('equipo.png', 300, 'image/png'),
        ], ['Accept' => 'application/json'])->assertOk();

        $this->get('/nosotros')
            ->assertOk()
            ->assertSee(SiteContent::query()->findOrFail('about.photo')->image_path)
            ->assertDontSee('Foto amplia: equipo o recepción del centro');
    }

    #[Test]
    public function al_reemplazar_la_foto_se_borra_la_anterior(): void
    {
        Storage::fake('public');
        $this->actingAsRole(RoleName::ADMINISTRADOR);

        $this->post('/api/site-contents/home.hero/image', [
            'image' => UploadedFile::fake()->create('primera.jpg', 100, 'image/jpeg'),
        ], ['Accept' => 'application/json'])->assertOk();
        $first = SiteContent::query()->findOrFail('home.hero')->image_path;

        $this->post('/api/site-contents/home.hero/image', [
            'image' => UploadedFile::fake()->create('segunda.jpg', 100, 'image/jpeg'),
        ], ['Accept' => 'application/json'])->assertOk();

        Storage::disk('public')->assertMissing($first);
    }

    #[Test]
    public function un_espacio_inexistente_responde_404(): void
    {
        $this->actingAsRole(RoleName::ADMINISTRADOR);

        $this->patchJson('/api/site-contents/inventado.clave', ['title' => 'x'])->assertNotFound();
    }

    #[Test]
    public function recepcion_no_puede_editar_el_contenido_web(): void
    {
        $this->actingAsRole(RoleName::RECEPCION);

        $this->getJson('/api/site-contents')->assertForbidden();
        $this->patchJson('/api/site-contents/home.hero', ['title' => 'x'])->assertForbidden();
    }
}
