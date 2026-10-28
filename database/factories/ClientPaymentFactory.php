<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Models\ClientPayment;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClientPayment>
 */
class ClientPaymentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'amount' => fake()->numberBetween(100, 5000),
            'paid_at' => now()->subDays(fake()->numberBetween(0, 30))->toDateString(),
            'method' => fake()->randomElement(PaymentMethod::cases()),
            'note' => null,
        ];
    }
}
