<?php

namespace Database\Factories;

use App\Models\ContractItem;
use App\Models\Employee;
use App\Models\IdrNumber;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\IdrItemBatch;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\IdrNumber>
 */
class IdrNumberFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = IdrNumber::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'number' => $this->faker->numberBetween(1000, 9999),
            'assigned_employee_id' => Employee::factory(),
            'approving_employee_id' => Employee::factory(),
            'received_by_id' => Employee::factory(),
            'received_from_id' => Employee::factory(),
            'contract_item_id' => ContractItem::factory(),
            'quantity' => $this->faker->numberBetween(1, 10),
            'inventory_code' => $this->faker->word(),
            'ors' => $this->faker->numerify('ORS-####'),
            'date_prepared' => now(),
            'date_accepted' => now(),
            'date' => now(),
            'remarks' => $this->faker->sentence(),
        ];
    }

    /**
     * Configure the model factory.
     *
     * @return $this
     */
    public function configure(): static
    {
        return $this->afterCreating(function (IdrNumber $idrNumber) {
            IdrItemBatch::factory()->count($idrNumber->quantity)->create([
                'idr_number_id' => $idrNumber->id,
            ]);
        });
    }
} 