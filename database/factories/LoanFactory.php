<?php

namespace Database\Factories;

use App\Models\Item;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Loan>
 */
class LoanFactory extends Factory
{
    public function definition(): array
    {
        $loanDate = fake()->dateTimeBetween('-30 days', 'now');

        return [
            'user_id' => User::factory(),
            'item_id' => Item::factory(),
            'quantity' => fake()->numberBetween(1, 3),
            'loan_date' => $loanDate->format('Y-m-d'),
            'due_date' => (clone $loanDate)->modify('+7 days')->format('Y-m-d'),
            'status' => 'menunggu',
            'notes' => null,
        ];
    }

    /** Contoh: Loan::factory()->withStatus('dipinjam') */
    public function withStatus(string $status): static
    {
        return $this->state(fn () => ['status' => $status]);
    }
}
