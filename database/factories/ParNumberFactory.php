<?php

namespace Database\Factories;

use App\Models\ContractItem;
use App\Models\Employee;
use App\Models\ParNumber;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ParNumber>
 */
class ParNumberFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ParNumber::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'par_number' => $this->faker->unique()->numerify('##########'),
            'assigned_employee_id' => Employee::factory(),
            'contract_item_id' => ContractItem::factory(),
            'quantity' => $this->faker->numberBetween(1, 10),
            'area_code' => $this->faker->word,
            'building_code' => $this->faker->word,
            'account_code' => $this->faker->word,
            'date_prepared' => now(),
            'date_accepted' => now(),
            'remarks' => $this->faker->sentence(),
            'inventory_code' => $this->faker->word,
            'date_acquired' => now(),
        ];
    }
} 