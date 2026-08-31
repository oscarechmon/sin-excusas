<?php

namespace Database\Factories;

use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'position' => 'Especialista',
            'phone' => (string) $this->faker->numberBetween(900000000, 999999999),
            'user_id' => null,
            'active' => true,
        ];
    }
}
