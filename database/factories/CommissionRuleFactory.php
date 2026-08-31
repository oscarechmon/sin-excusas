<?php

namespace Database\Factories;

use App\Enums\CommissionType;
use App\Models\CommissionRule;
use Illuminate\Database\Eloquent\Factories\Factory;

class CommissionRuleFactory extends Factory
{
    protected $model = CommissionRule::class;

    public function definition(): array
    {
        return [
            'employee_id' => null,
            'service_id' => null,
            'type' => CommissionType::PERCENTAGE,
            'value' => 10,
            'active' => true,
        ];
    }
}
