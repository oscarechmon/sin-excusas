<?php

namespace Database\Seeders;

use App\Enums\InventoryMovementType;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use Illuminate\Database\Seeder;

/**
 * Insumos y productos de arranque.
 *
 * El stock inicial entra como movimiento de compra y no como un valor escrito
 * a mano, para que el saldo tenga origen desde el primer día (§24).
 */
class InventorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Insumos médicos' => 'Material descartable de uso clínico',
            'Cosmética' => 'Cremas, geles y productos de tratamiento',
            'Productos de venta' => 'Artículos que se venden al cliente',
        ];

        $created = [];

        foreach ($categories as $name => $description) {
            $created[$name] = InventoryCategory::firstOrCreate(
                ['name' => $name],
                ['description' => $description, 'active' => true]
            );
        }

        $items = [
            ['name' => 'Aguja 30G', 'category' => 'Insumos médicos', 'unit' => 'unidad', 'stock' => 200, 'min_stock' => 50, 'cost' => 0.80],
            ['name' => 'Jeringa 5ml', 'category' => 'Insumos médicos', 'unit' => 'unidad', 'stock' => 120, 'min_stock' => 30, 'cost' => 1.20],
            ['name' => 'Gasa estéril', 'category' => 'Insumos médicos', 'unit' => 'unidad', 'stock' => 300, 'min_stock' => 80, 'cost' => 0.50],
            ['name' => 'Guantes de nitrilo', 'category' => 'Insumos médicos', 'unit' => 'par', 'stock' => 40, 'min_stock' => 60, 'cost' => 1.00],
            ['name' => 'Gel conductor', 'category' => 'Cosmética', 'unit' => 'ml', 'stock' => 5000, 'min_stock' => 1000, 'cost' => 0.02],
            ['name' => 'Crema hidratante 250ml', 'category' => 'Productos de venta', 'unit' => 'unidad', 'stock' => 25, 'min_stock' => 5, 'cost' => 28.00, 'sale_price' => 55.00, 'is_sellable' => true],
            ['name' => 'Protector solar SPF50', 'category' => 'Productos de venta', 'unit' => 'unidad', 'stock' => 18, 'min_stock' => 5, 'cost' => 32.00, 'sale_price' => 65.00, 'is_sellable' => true],
        ];

        foreach ($items as $data) {
            $stock = $data['stock'];
            $categoryName = $data['category'];
            unset($data['stock'], $data['category']);

            $item = InventoryItem::firstOrCreate(
                ['name' => $data['name']],
                $data + [
                    'category_id' => $created[$categoryName]->id,
                    'stock' => 0,
                    'is_sellable' => $data['is_sellable'] ?? false,
                    'active' => true,
                ]
            );

            if ($item->movements()->doesntExist()) {
                $item->movements()->create([
                    'type' => InventoryMovementType::PURCHASE,
                    'quantity' => $stock,
                    'stock_after' => $stock,
                    'unit_cost' => $item->cost,
                    'notes' => 'Stock inicial',
                ]);

                $item->update(['stock' => $stock]);
            }
        }
    }
}
