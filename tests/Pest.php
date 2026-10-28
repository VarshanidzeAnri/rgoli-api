<?php

use App\Models\User;
use Database\Seeders\DemoSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(function (): void {
        $this->seed(RolesAndPermissionsSeeder::class);
    })
    ->in('Feature', 'Unit');

/**
 * Seeds the demo workspace (4 demo users, 3 projects, 5 tasks, 2 sprints…).
 */
function seedDemo(): void
{
    test()->seed(DemoSeeder::class);
}

/**
 * The seeded demo user for a role: owner, project_manager, interior_designer, finance_manager.
 */
function demoUser(string $role): User
{
    $email = match ($role) {
        'owner' => 'admin@rgoli.ge',
        'project_manager' => 'manager@rgoli.ge',
        'interior_designer' => 'designer@rgoli.ge',
        'finance_manager' => 'finance@rgoli.ge',
        default => throw new InvalidArgumentException("No demo user for {$role}"),
    };

    return User::query()->where('email', $email)->firstOrFail();
}

/**
 * A fresh user with the given role and disciplines, authenticated through Sanctum.
 *
 * @param  list<string>  $disciplines
 */
function actingAsRole(string $role, array $disciplines = []): User
{
    $user = User::factory()->role($role)->disciplines($disciplines)->create();
    Sanctum::actingAs($user);

    return $user;
}

/**
 * Authenticates as a user through Sanctum and returns it.
 */
function signIn(User $user): User
{
    Sanctum::actingAs($user);

    return $user;
}
