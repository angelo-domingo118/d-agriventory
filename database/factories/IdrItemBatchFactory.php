<?php

namespace Database\Factories;

use App\Models\IdrItemBatch;
use App\Models\IdrNumber;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\IdrItemBatch>
 */
class IdrItemBatchFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = IdrItemBatch::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'idr_number_id' => IdrNumber::factory(),
            'identification_data' => $this->faker->optional()->sentence,
        ];
    }
}
