<?php

namespace Database\Factories;

use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClientFactory extends Factory
{
    protected $model = Client::class;

    public function definition(): array
    {
        return [
            'code' => 'CLI-'.str_pad((string) $this->faker->unique()->numberBetween(1, 999999), 6, '0', STR_PAD_LEFT),
            'full_name' => $this->faker->name(),
            'document_number' => (string) $this->faker->unique()->numberBetween(10000000, 99999999),
            'phone' => (string) $this->faker->numberBetween(900000000, 999999999),
            'active' => true,
        ];
    }
}
