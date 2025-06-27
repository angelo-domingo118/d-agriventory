<?php

namespace Database\Factories;

use App\Models\Division;
use App\Models\DivisionInventoryManager;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DivisionInventoryManager>
 */
class DivisionInventoryManagerFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = DivisionInventoryManager::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'division_id' => Division::factory(),
        ];
    }
}
