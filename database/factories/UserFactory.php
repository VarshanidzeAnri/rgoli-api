<?php

namespace Database\Factories;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'job_title' => fake()->jobTitle(),
            'is_active' => true,
            'remember_token' => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => ['email_verified_at' => null]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => ['is_active' => false]);
    }

    /**
     * Assign a seeded role by slug (run RolesAndPermissionsSeeder first).
     */
    public function role(string $slug): static
    {
        return $this->afterCreating(function (User $user) use ($slug): void {
            $user->assignRole(Role::query()->where('slug', $slug)->firstOrFail());
        });
    }

    /**
     * @param  list<string>  $disciplines
     */
    public function disciplines(array $disciplines): static
    {
        return $this->afterCreating(fn (User $user) => $user->syncDisciplines($disciplines));
    }
}
