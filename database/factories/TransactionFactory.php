<?php

namespace Database\Factories;

use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => $this->faker->randomElement(['deposit', 'withdraw', 'transfer_in']),
            'reference_no' => 'TF-' . strtoupper(Str::random(12)),
            'amount' => $this->faker->randomFloat(2, 1000, 10000000),
            'balance_before' => $this->faker->randomFloat(2, 0, 10000000),
            'balance_after' => $this->faker->randomFloat(2, 0, 10000000),
        ];
    }
}
