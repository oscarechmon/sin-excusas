<?php

namespace Database\Factories;

use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

class ServiceFactory extends Factory
{
    protected $model = Service::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->words(3, true),
            'category_id' => ServiceCategory::factory(),
            'price' => $this->faker->randomFloat(2, 50, 400),
            'duration_minutes' => 60,
            'active' => true,
        ];
    }
}
