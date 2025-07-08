<?php

namespace Database\Factories;

use App\Models\ConsumableRecord;
use App\Models\Division;
use Illuminate\Database\Eloquent\Factories\Factory;

class ConsumableRecordFactory extends Factory
{
    protected $model = ConsumableRecord::class;

    public function definition(): array
    {
        return [
            'record_number' => $this->faker->unique()->numerify('CR-#####'),
            'division_id' => Division::factory(),
            'date_received' => $this->faker->date(),
            'remarks' => $this->faker->sentence,
        ];
    }
} 