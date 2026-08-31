<?php

namespace Database\Factories;

use App\Enums\PackageStatus;
use App\Models\Client;
use App\Models\ClientPackage;
use App\Models\Package;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClientPackageFactory extends Factory
{
    protected $model = ClientPackage::class;

    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'package_id' => Package::factory(),
            'package_name' => $this->faker->words(3, true),
            'price' => 800,
            'total_sessions' => 10,
            'used_sessions' => 0,
            'purchased_at' => now()->toDateString(),
            'expires_at' => now()->addMonths(6)->toDateString(),
            'status' => PackageStatus::ACTIVE,
        ];
    }

    public function withSessions(int $total, int $used = 0): static
    {
        return $this->state(fn () => ['total_sessions' => $total, 'used_sessions' => $used]);
    }

    public function expired(): static
    {
        return $this->state(fn () => ['expires_at' => now()->subDay()->toDateString()]);
    }
}
