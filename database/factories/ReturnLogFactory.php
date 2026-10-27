<?php

namespace Database\Factories;

use App\Models\Loan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\ReturnLog>
 */
class ReturnLogFactory extends Factory
{
    public function definition(): array
    {
        return [
            'loan_id' => Loan::factory()->withStatus('dikembalikan'),
            'returned_at' => now()->toDateString(),
            'condition_on_return' => 'baik',
            'notes' => null,
        ];
    }
}
