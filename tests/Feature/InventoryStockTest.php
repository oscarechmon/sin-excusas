<?php

namespace Tests\Feature;

use App\Enums\InventoryMovementType;
use App\Exceptions\InsufficientStockException;
use App\Models\InventoryItem;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InventoryStockTest extends TestCase
{
    use RefreshDatabase;

    private InventoryService $inventory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->inventory = app(InventoryService::class);
    }

    #[Test]
    public function una_entrada_suma_stock_y_registra_el_movimiento(): void
    {
        $item = InventoryItem::factory()->withStock(10)->create();

        $movement = $this->inventory->registerMovement($item, InventoryMovementType::PURCHASE, 15);

        $this->assertEquals(25, $item->fresh()->stock);
        $this->assertEquals(15, $movement->quantity);
        $this->assertEquals(25, $movement->stock_after);
    }

    #[Test]
    public function una_salida_resta_stock(): void
    {
        $item = InventoryItem::factory()->withStock(10)->create();

        $movement = $this->inventory->registerMovement($item, InventoryMovementType::MANUAL_OUT, 4);

        $this->assertEquals(6, $item->fresh()->stock);
        // La cantidad se guarda con signo para que el saldo sea una suma.
        $this->assertEquals(-4, $movement->quantity);
    }

    /** Caso crítico §47: "no permitir stock negativo cuando la regla lo prohíba". */
    #[Test]
    public function no_permite_dejar_el_stock_negativo(): void
    {
        $item = InventoryItem::factory()->withStock(3)->create();

        try {
            $this->inventory->registerMovement($item, InventoryMovementType::SERVICE_USAGE, 5);
            $this->fail('Debió rechazar el consumo por falta de stock.');
        } catch (InsufficientStockException) {
            // esperado
        }

        // Ni el saldo cambió ni quedó un movimiento huérfano.
        $this->assertEquals(3, $item->fresh()->stock);
        $this->assertDatabaseCount('inventory_movements', 0);
    }

    #[Test]
    public function el_ajuste_trata_la_cantidad_como_saldo_real_contado(): void
    {
        $item = InventoryItem::factory()->withStock(20)->create();

        // Se contaron 12 unidades: el delta debe ser -8, no -12.
        $movement = $this->inventory->registerMovement($item, InventoryMovementType::ADJUSTMENT, 12);

        $this->assertEquals(12, $item->fresh()->stock);
        $this->assertEquals(-8, $movement->quantity);
    }

    #[Test]
    public function el_ajuste_tambien_puede_subir_el_saldo(): void
    {
        $item = InventoryItem::factory()->withStock(5)->create();

        $movement = $this->inventory->registerMovement($item, InventoryMovementType::ADJUSTMENT, 9);

        $this->assertEquals(9, $item->fresh()->stock);
        $this->assertEquals(4, $movement->quantity);
    }

    #[Test]
    public function el_saldo_coincide_con_la_suma_de_los_movimientos(): void
    {
        $item = InventoryItem::factory()->withStock(0)->create();

        $this->inventory->registerMovement($item, InventoryMovementType::PURCHASE, 50);
        $this->inventory->registerMovement($item, InventoryMovementType::SERVICE_USAGE, 12);
        $this->inventory->registerMovement($item, InventoryMovementType::MANUAL_OUT, 3);

        $sumOfMovements = (float) $item->movements()->sum('quantity');

        $this->assertEquals(35, $item->fresh()->stock);
        $this->assertEquals(35, $sumOfMovements);
    }
    /** Al crear desde el panel, el stock inicial se acepta y queda como compra inicial. */
    #[Test]
    public function crear_un_producto_con_stock_inicial_registra_el_movimiento(): void
    {
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        $admin = \App\Models\User::factory()->create(['active' => true]);
        $admin->assignRole(\App\Enums\RoleName::ADMINISTRADOR->value);
        \Laravel\Sanctum\Sanctum::actingAs($admin);

        $response = $this->postJson('/api/inventory-items', [
            'name' => 'Omega 3', 'unit' => 'unidad', 'stock' => 20, 'min_stock' => 2, 'cost' => 30, 'is_sellable' => true, 'sale_price' => 55,
        ])->assertCreated();

        $item = InventoryItem::findOrFail($response->json('data.id'));
        $this->assertEquals(20, $item->stock);
        $this->assertSame(1, $item->movements()->count());

        // Al editar, el stock sigue bloqueado.
        $this->patchJson("/api/inventory-items/{$item->id}", ['name' => 'Omega 3', 'unit' => 'unidad', 'min_stock' => 2, 'cost' => 30, 'stock' => 99])
            ->assertUnprocessable()->assertJsonValidationErrors('stock');
    }
}
