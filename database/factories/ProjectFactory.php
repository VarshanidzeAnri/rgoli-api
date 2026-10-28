<?php

namespace Database\Factories;

use App\Enums\Discipline;
use App\Enums\Status;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $budget = fake()->numberBetween(2000, 40000);

        return [
            'key' => strtoupper(Str::random(2)).fake()->unique()->numberBetween(10, 99999),
            'name' => fake()->sentence(3),
            'client_name' => fake()->name(),
            'client_phone' => fake()->phoneNumber(),
            'client_email' => fake()->safeEmail(),
            'address' => fake()->address(),
            'discipline' => fake()->randomElement(Discipline::cases()),
            'status' => fake()->randomElement(Status::cases()),
            'budget' => $budget,
            'discount_price' => null,
            'lat' => fake()->latitude(41.60, 41.67),
            'lng' => fake()->longitude(41.58, 41.68),
            'progress' => fake()->numberBetween(0, 100),
            'start_date' => now()->subDays(10)->toDateString(),
            'end_date' => now()->addDays(60)->toDateString(),
            'description' => fake()->paragraph(),
        ];
    }

    public function discipline(Discipline $discipline): static
    {
        return $this->state(fn () => ['discipline' => $discipline]);
    }
}
