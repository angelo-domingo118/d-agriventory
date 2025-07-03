<?php

namespace Database\Factories;

use App\Models\Contract;
use App\Models\ContractItem;
use App\Models\ItemSpecification;
use Illuminate\Database\Eloquent\Factories\Factory;

class ContractItemFactory extends Factory
{
    protected $model = ContractItem::class;

    public function definition(): array
    {
        return [
            'contract_id' => Contract::factory(),
            'item_specification_id' => ItemSpecification::factory(),
            'unit_price' => $this->faker->randomFloat(2, 100, 10000),
            'item_type' => $this->faker->randomElement(['ICS', 'PAR', 'IDR']),
        ];
    }
}
