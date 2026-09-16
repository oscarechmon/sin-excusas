<?php

namespace Tests\Feature;

use App\Enums\RoleName;
use App\Models\InventoryItem;
use App\Models\Package;
use App\Models\Service;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/** Catálogo administrable: switch de publicación, fotos y su efecto en la web pública. */
class CatalogPublicationTest extends TestCase
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
    public function las_paginas_publicas_responden_con_urls_limpias(): void
    {
        foreach (['/', '/nosotros', '/servicios', '/productos'] as $url) {
            $this->get($url)->assertOk();
        }
    }

    #[Test]
    public function las_urls_antiguas_redirigen_de_forma_permanente(): void
    {
        $this->get('/servicios.html')->assertStatus(301)->assertRedirect('/servicios');
        $this->get('/catalogo')->assertStatus(301)->assertRedirect('/servicios');
        $this->get('/suplementos')->assertStatus(301)->assertRedirect('/productos');
    }

    #[Test]
    public function servicios_solo_muestra_lo_publicado(): void
    {
        Service::factory()->create(['name' => 'Servicio visible', 'is_published' => true]);
        Service::factory()->create(['name' => 'Servicio oculto', 'is_published' => false]);
        // Publicado pero desactivado en el ERP: tampoco debe anunciarse.
        Service::factory()->create(['name' => 'Servicio inactivo', 'is_published' => true, 'active' => false]);

        $this->get('/servicios')
            ->assertOk()
            ->assertSee('Servicio visible')
            ->assertDontSee('Servicio oculto')
            ->assertDontSee('Servicio inactivo');
    }

    #[Test]
    public function servicios_sin_publicar_muestra_un_aviso(): void
    {
        $this->get('/servicios')->assertOk()->assertSee('Estamos actualizando nuestros servicios');
    }

    #[Test]
    public function productos_solo_muestra_vendibles_publicados(): void
    {
        InventoryItem::factory()->sellable(40)->create(['name' => 'Omega visible', 'is_published' => true]);
        InventoryItem::factory()->sellable(40)->create(['name' => 'Producto oculto', 'is_published' => false]);
        InventoryItem::factory()->create(['name' => 'Insumo interno', 'is_published' => true]);

        $this->get('/productos')
            ->assertOk()
            ->assertSee('Omega visible')
            ->assertDontSee('Producto oculto')
            ->assertDontSee('Insumo interno');
    }

    #[Test]
    public function el_administrador_publica_y_retira_un_servicio(): void
    {
        $this->actingAsRole(RoleName::ADMINISTRADOR);
        $service = Service::factory()->create(['is_published' => false]);

        $this->patchJson("/api/services/{$service->id}/publish", ['is_published' => true])
            ->assertOk()
            ->assertJsonPath('data.is_published', true);
        $this->assertTrue($service->fresh()->is_published);

        $this->patchJson("/api/services/{$service->id}/publish", ['is_published' => false])->assertOk();
        $this->assertFalse($service->fresh()->is_published);
    }

    #[Test]
    public function el_administrador_publica_un_paquete(): void
    {
        $this->actingAsRole(RoleName::ADMINISTRADOR);
        $package = Package::factory()->create(['is_published' => false]);

        $this->patchJson("/api/packages/{$package->id}/publish", ['is_published' => true])->assertOk();

        $this->assertTrue($package->fresh()->is_published);
    }

    #[Test]
    public function un_insumo_no_vendible_no_puede_publicarse(): void
    {
        $this->actingAsRole(RoleName::ADMINISTRADOR);
        $item = InventoryItem::factory()->create(['is_sellable' => false]);

        $this->patchJson("/api/inventory-items/{$item->id}/publish", ['is_published' => true])
            ->assertUnprocessable();

        $this->assertFalse($item->fresh()->is_published);
    }

    #[Test]
    public function recepcion_no_puede_cambiar_la_publicacion(): void
    {
        $this->actingAsRole(RoleName::RECEPCION);
        $service = Service::factory()->create(['is_published' => false]);

        $this->patchJson("/api/services/{$service->id}/publish", ['is_published' => true])
            ->assertForbidden();
    }

    #[Test]
    public function el_administrador_sube_y_reemplaza_la_foto_de_un_servicio(): void
    {
        Storage::fake('public');
        $this->actingAsRole(RoleName::ADMINISTRADOR);
        $service = Service::factory()->create();

        $this->post("/api/services/{$service->id}/image", [
            'image' => UploadedFile::fake()->create('primera.jpg', 200, 'image/jpeg'),
        ], ['Accept' => 'application/json'])->assertOk();
        $first = $service->fresh()->image_path;
        Storage::disk('public')->assertExists($first);

        $this->post("/api/services/{$service->id}/image", [
            'image' => UploadedFile::fake()->create('segunda.png', 200, 'image/png'),
        ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('data.image_url', asset('storage/'.$service->fresh()->image_path));

        // La foto anterior no queda huérfana en el disco.
        Storage::disk('public')->assertMissing($first);
    }

    #[Test]
    public function rechaza_archivos_que_no_son_imagen(): void
    {
        Storage::fake('public');
        $this->actingAsRole(RoleName::ADMINISTRADOR);
        $item = InventoryItem::factory()->sellable()->create();

        $this->post("/api/inventory-items/{$item->id}/image", [
            'image' => UploadedFile::fake()->create('virus.pdf', 100, 'application/pdf'),
        ], ['Accept' => 'application/json'])->assertUnprocessable();
    }
}
