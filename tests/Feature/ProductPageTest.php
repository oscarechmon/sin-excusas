<?php

namespace Tests\Feature;

use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Services\Shop\Cart;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/** Fichas de producto y de servicio ("Comprar ahora") y carrito lateral. */
class ProductPageTest extends TestCase
{
    use RefreshDatabase;

    private function product(array $attributes = [], float $price = 40, float $stock = 10): InventoryItem
    {
        return InventoryItem::factory()->sellable($price)->withStock($stock)->create($attributes + ['is_published' => true]);
    }

    #[Test]
    public function la_ficha_muestra_el_producto_con_su_compra(): void
    {
        $category = InventoryCategory::create(['name' => 'Suplementos', 'active' => true]);
        $product = $this->product(['name' => 'Omega 3', 'category_id' => $category->id, 'description' => "Primer párrafo.\n\nSegundo párrafo."], price: 89.9);
        $this->product(['name' => 'Inositol', 'category_id' => $category->id]);

        $this->get($product->webUrl())
            ->assertOk()
            ->assertSee('Omega 3')
            ->assertSee('S/ 89.90')
            ->assertSee('<p>Segundo párrafo.</p>', false)
            ->assertSee('Comprar ahora')
            ->assertSee('También te puede interesar')
            ->assertSee('Inositol');
    }

    #[Test]
    public function el_listado_enlaza_a_la_ficha(): void
    {
        $product = $this->product(['name' => 'Omega 3']);

        $this->get('/productos')->assertOk()->assertSee($product->webUrl(), false);
    }

    #[Test]
    public function la_ficha_tiene_una_sola_url_aunque_cambie_el_nombre(): void
    {
        $product = $this->product(['name' => 'Omega 3']);

        $this->get("/producto/{$product->id}")->assertStatus(301)->assertRedirect($product->webUrl());
        $this->get("/producto/{$product->id}/nombre-viejo")->assertStatus(301)->assertRedirect($product->webUrl());
    }

    #[Test]
    public function lo_no_publicado_no_tiene_ficha(): void
    {
        $oculto = $this->product(['name' => 'Oculto', 'is_published' => false]);
        $insumo = InventoryItem::factory()->create(['name' => 'Insumo', 'is_published' => true]);

        $this->get($oculto->webUrl())->assertNotFound();
        $this->get($insumo->webUrl())->assertNotFound();
        $this->get('/producto/999999/lo-que-sea')->assertNotFound();
    }

    #[Test]
    public function con_una_sola_foto_no_hay_miniaturas(): void
    {
        $product = $this->product(['name' => 'Omega 3', 'image_path' => 'catalog/products/omega.jpg']);

        $this->get($product->webUrl())
            ->assertOk()
            ->assertSee('catalog/products/omega.jpg', false)
            ->assertDontSee('data-gallery-thumb', false);
    }

    #[Test]
    public function la_ficha_de_un_servicio_muestra_el_servicio_con_su_compra(): void
    {
        $faciales = ServiceCategory::factory()->create(['name' => 'Faciales']);
        $service = Service::factory()->create([
            'name' => 'Limpieza facial premium', 'category_id' => $faciales->id, 'price' => 150,
            'duration_minutes' => 90, 'description' => "Limpieza profunda.\n\nPiel luminosa.", 'is_published' => true,
        ]);
        Service::factory()->create(['name' => 'Hidratación intensiva', 'category_id' => $faciales->id, 'is_published' => true]);

        $this->get('/servicios')->assertOk()->assertSee($service->webUrl(), false);

        $this->get($service->webUrl())
            ->assertOk()
            ->assertSee('Limpieza facial premium')
            ->assertSee('S/ 150.00')
            ->assertSee('90 min')
            ->assertSee('<p>Piel luminosa.</p>', false)
            ->assertSee('name="type" value="service"', false)
            ->assertSee('Comprar ahora')
            ->assertSee('También te puede interesar')
            ->assertSee('Hidratación intensiva');
    }

    #[Test]
    public function la_ficha_de_un_servicio_tiene_una_sola_url_y_solo_si_esta_publicado(): void
    {
        $service = Service::factory()->create(['name' => 'Dermapen', 'is_published' => true]);
        $oculto = Service::factory()->create(['name' => 'Oculto', 'is_published' => false]);

        $this->get("/servicio/{$service->id}/nombre-viejo")->assertStatus(301)->assertRedirect($service->webUrl());
        $this->get($oculto->webUrl())->assertNotFound();
        $this->get('/servicio/999999/lo-que-sea')->assertNotFound();
    }

    #[Test]
    public function un_producto_agotado_no_ofrece_comprarlo(): void
    {
        $product = $this->product(['name' => 'Omega 3'], stock: 0);

        $this->get($product->webUrl())
            ->assertOk()
            ->assertSee('Agotado')
            ->assertDontSee('Comprar ahora');
    }

    #[Test]
    public function comprar_ahora_agrega_y_lleva_a_finalizar_la_compra(): void
    {
        $product = $this->product();

        // Sin JavaScript: formulario normal.
        $this->post('/carrito', ['type' => 'product', 'id' => $product->id, 'quantity' => 2, 'buy_now' => 1])
            ->assertRedirect(route('shop.checkout'));
        $this->assertSame(2, app(Cart::class)->count());

        // Por fetch: el JSON dice adónde seguir.
        $this->postJson('/carrito', ['type' => 'product', 'id' => $product->id, 'buy_now' => 1])
            ->assertOk()
            ->assertJson(['success' => true, 'count' => 3, 'redirect' => route('shop.checkout')]);
    }

    #[Test]
    public function agregar_por_fetch_devuelve_el_carrito_lateral(): void
    {
        $product = $this->product(['name' => 'Omega 3'], price: 50);

        $drawer = $this->postJson('/carrito', ['type' => 'product', 'id' => $product->id, 'quantity' => 2])
            ->assertOk()
            ->assertJsonPath('redirect', null)
            ->json('drawer');

        $this->assertStringContainsString('Agregaste Omega 3 al carrito.', $drawer);
        $this->assertStringContainsString('S/ 100.00', $drawer);
        $this->assertStringContainsString('Ir al carrito', $drawer);
    }

    #[Test]
    public function el_carrito_lateral_muestra_lo_elegido_o_que_esta_vacio(): void
    {
        $this->get('/carrito/resumen')->assertOk()->assertSee('Tu carrito está vacío.');

        $product = $this->product(['name' => 'Omega 3']);
        $this->post('/carrito', ['type' => 'product', 'id' => $product->id]);

        $this->get('/carrito/resumen')
            ->assertOk()
            ->assertSee('Omega 3')
            ->assertSee(route('shop.cart.show'), false)
            ->assertDontSee('<html', false);
    }

    #[Test]
    public function el_carrito_lateral_cambia_cantidades_sin_pasarse_del_stock(): void
    {
        $product = $this->product(stock: 3);
        $key = Cart::key(Cart::PRODUCT, $product->id);
        $this->post('/carrito', ['type' => 'product', 'id' => $product->id]);

        $this->patchJson("/carrito/{$key}", ['quantity' => 2])
            ->assertOk()
            ->assertJson(['success' => true, 'count' => 2]);

        $this->patchJson("/carrito/{$key}", ['quantity' => 5])
            ->assertOk()
            ->assertJson(['success' => false, 'count' => 3]);
        $this->assertStringContainsString('Solo hay 3 unidad(es)', $this->patchJson("/carrito/{$key}", ['quantity' => 9])->json('drawer'));

        $this->deleteJson("/carrito/{$key}")->assertOk()->assertJson(['count' => 0]);
    }

    #[Test]
    public function si_se_agota_en_el_carrito_se_quita_en_vez_de_quedar_en_cero(): void
    {
        $product = $this->product(stock: 2);
        $key = Cart::key(Cart::PRODUCT, $product->id);
        $this->post('/carrito', ['type' => 'product', 'id' => $product->id, 'quantity' => 2]);
        $product->update(['stock' => 0]);

        $this->patch("/carrito/{$key}", ['quantity' => 1])->assertRedirect(route('shop.cart.show'));

        $this->assertSame([], app(Cart::class)->quantities());
    }
}
