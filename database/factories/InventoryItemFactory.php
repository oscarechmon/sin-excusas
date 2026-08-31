<?php

namespace Database\Factories;

use App\Models\InventoryItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class InventoryItemFactory extends Factory
{
    protected $model = InventoryItem::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->words(2, true),
            'category_id' => null,
            'unit' => 'unidad',
            'stock' => 100,
            'min_stock' => 10,
            'cost' => $this->faker->randomFloat(2, 1, 50),
            'sale_price' => null,
            'is_sellable' => false,
            'active' => true,
        ];
    }

    public function sellable(float $price = 50): static
    {
        return $this->state(fn () => ['is_sellable' => true, 'sale_price' => $price]);
    }

    public function withStock(float $stock): static
    {
        return $this->state(fn () => ['stock' => $stock]);
    }
}
