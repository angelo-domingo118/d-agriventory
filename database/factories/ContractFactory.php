<?php

namespace Database\Factories;

use App\Models\Contract;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Contract>
 */
class ContractFactory extends Factory
{
    /**
     * Holds the generated unique contract numbers to avoid collisions.
     *
     * @var array
     */
    protected static $usedContractNumbers = [];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'supplier_id' => Supplier::factory(),
            'contract_po_ib_number' => $this->generateUniqueContractNumber(),
        ];
    }

    /**
     * Generates a unique contract number by mimicking real data patterns.
     */
    private function generateUniqueContractNumber(): string
    {
        do {
            $contractNumber = $this->generateContractNumber();
        } while (in_array($contractNumber, self::$usedContractNumbers));

        self::$usedContractNumbers[] = $contractNumber;

        return $contractNumber;
    }

    /**
     * Generates a random contract number based on a variety of observed formats.
     */
    private function generateContractNumber(): string
    {
        $faker = $this->faker;

        $format = $faker->randomElement(['###-##', '###-####', '####-##', '####-###', 'prefix-space-num']);

        switch ($format) {
            case 'prefix-space-num':
                // Mimics formats like 'RO 2024-140'
                return strtoupper($faker->lexify('??')).' '.$faker->numerify('####-###');
            default:
                // Handles formats like '256-34' or '2025-04'
                return $faker->numerify($format);
        }
    }
}
