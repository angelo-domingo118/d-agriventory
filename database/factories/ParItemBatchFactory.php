<?php

namespace Database\Factories;

use App\Models\ParItemBatch;
use App\Models\ParNumber;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ParItemBatch>
 */
class ParItemBatchFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ParItemBatch::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'par_number_id' => ParNumber::factory(),
            'identification_data' => $this->faker->optional()->sentence,
        ];
    }
}
