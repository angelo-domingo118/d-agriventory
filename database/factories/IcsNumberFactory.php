<?php

namespace Database\Factories;

use App\Models\ContractItem;
use App\Models\Employee;
use App\Models\IcsNumber;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\IcsNumber>
 */
class IcsNumberFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = IcsNumber::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ics_number' => $this->faker->unique()->numerify('ICS-####-##-###'),
            'assigned_employee_id' => Employee::factory(),
            'contract_item_id' => ContractItem::factory(),
            'ics_type' => 'SPLV',
            'quantity' => $this->faker->numberBetween(1, 10),
            'estimated_useful_life' => $this->faker->numberBetween(1, 5),
            'date_prepared' => now(),
            'date_accepted' => now(),
            'remarks' => $this->faker->sentence(),
        ];
    }
}
