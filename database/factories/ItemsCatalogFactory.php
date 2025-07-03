<?php

namespace Database\Factories;

use App\Models\ItemsCatalog;
use App\Models\SecondaryCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

class ItemsCatalogFactory extends Factory
{
    protected $model = ItemsCatalog::class;

    public function definition(): array
    {
        return [
            'secondary_category_id' => SecondaryCategory::factory(),
            'name' => $this->faker->unique()->words(3, true),
            'unit' => $this->faker->randomElement(['piece', 'kg', 'meter', 'liter']),
            'code' => $this->faker->unique()->word,
        ];
    }
}
