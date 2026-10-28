<?php

namespace Database\Factories;

use App\Enums\PaymentStatus;
use App\Models\Project;
use App\Models\SalaryRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SalaryRequest>
 */
class SalaryRequestFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'user_id' => User::factory(),
            'title' => fake()->words(3, true),
            'amount' => fake()->numberBetween(100, 2000),
            'note' => null,
            'status' => PaymentStatus::Pending,
        ];
    }
}
