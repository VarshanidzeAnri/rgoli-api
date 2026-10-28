<?php

namespace Database\Factories;

use App\Enums\Priority;
use App\Enums\Status;
use App\Models\Issue;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Issue>
 */
class IssueFactory extends Factory
{
    private static int $sequence = 0;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'number' => ++self::$sequence,
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'status' => Status::Todo,
            'priority' => fake()->randomElement(Priority::cases()),
            'due_date' => now()->addDays(fake()->numberBetween(-5, 20))->toDateString(),
            'points' => fake()->numberBetween(1, 8),
            'position' => self::$sequence,
        ];
    }

    public function status(Status $status): static
    {
        return $this->state(fn () => ['status' => $status]);
    }
}
