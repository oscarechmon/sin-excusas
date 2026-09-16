<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\ClientUser;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClientUserFactory extends Factory
{
    protected $model = ClientUser::class;

    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => (string) $this->faker->numberBetween(900000000, 999999999),
            'password' => 'password',
            'email_verified_at' => now(),
        ];
    }

    /** Recién registrada con correo: aún no ingresó el código. */
    public function unverified(): static
    {
        return $this->state(fn () => ['email_verified_at' => null]);
    }
}
