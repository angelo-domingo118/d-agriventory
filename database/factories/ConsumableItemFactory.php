<?php

namespace Database\Factories;

use App\Models\ConsumableItem;
use App\Models\ConsumableRecord;
use App\Models\ItemSpecification;
use Illuminate\Database\Eloquent\Factories\Factory;

class ConsumableItemFactory extends Factory
{
    protected $model = ConsumableItem::class;

    public function definition(): array
    {
        $initial_quantity = $this->faker->numberBetween(10, 100);
        return [
            'consumable_record_id' => ConsumableRecord::factory(),
            'item_specification_id' => ItemSpecification::factory(),
            'initial_quantity' => $initial_quantity,
            'current_quantity' => $this->faker->numberBetween(1, $initial_quantity),
        ];
    }
} 