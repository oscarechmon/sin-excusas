<?php

namespace Database\Factories;

use App\Models\Package;
use Illuminate\Database\Eloquent\Factories\Factory;

class PackageFactory extends Factory
{
    protected $model = Package::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->words(3, true),
            'price' => 800,
            'total_sessions' => 10,
            'validity_days' => 180,
            'active' => true,
        ];
    }
}
