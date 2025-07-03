<?php

namespace Database\Factories;

use App\Models\ItemsCatalog;
use App\Models\ItemSpecification;
use Illuminate\Database\Eloquent\Factories\Factory;

class ItemSpecificationFactory extends Factory
{
    protected $model = ItemSpecification::class;

    public function definition(): array
    {
        return [
            'item_catalog_id' => ItemsCatalog::factory(),
            'brand' => $this->faker->company,
            'model' => $this->faker->word,
            'detailed_specifications' => $this->faker->text,
        ];
    }
}
