<?php

namespace Database\Factories;

use App\Enums\SprintState;
use App\Models\Sprint;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sprint>
 */
class SprintFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Sprint '.fake()->unique()->numberBetween(1, 999),
            'goal' => fake()->sentence(),
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(14)->toDateString(),
            'state' => SprintState::Planned,
        ];
    }

    public function active(): static
    {
        return $this->state(fn () => ['state' => SprintState::Active, 'started_at' => now()]);
    }
}
