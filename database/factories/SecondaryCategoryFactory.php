<?php

namespace Database\Factories;

use App\Models\PrimaryCategory;
use App\Models\SecondaryCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

class SecondaryCategoryFactory extends Factory
{
    protected $model = SecondaryCategory::class;

    public function definition(): array
    {
        return [
            'primary_category_id' => PrimaryCategory::factory(),
            'name' => $this->faker->unique()->word,
            'code' => $this->faker->unique()->word,
            'description' => $this->faker->sentence,
        ];
    }
}
