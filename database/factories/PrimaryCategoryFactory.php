<?php

namespace Database\Factories;

use App\Models\PrimaryCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

class PrimaryCategoryFactory extends Factory
{
    protected $model = PrimaryCategory::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->word,
            'code' => $this->faker->unique()->word,
            'description' => $this->faker->sentence,
        ];
    }
}
