<?php

use App\Jobs\ExportSystemDataJob;
use App\Models\Role;
use App\Models\User;
use App\Support\PermissionCatalog;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;

beforeEach(function (): void {
    seedDemo();
    signIn(demoUser('owner'));
});

it('creates a user with role, direct permissions and disciplines', function (): void {
    $role = Role::query()->where('slug', 'architect')->firstOrFail();

    $response = $this->postJson('/api/v1/users', [
        'name' => 'Nika',
        'email' => 'Nika@RGOLI.ge',
        'password' => 'secret123',
        'role_id' => $role->id,
        'direct_permissions' => ['reports.view'],
        'disciplines' => ['architecture', 'interior'],
    ])->assertCreated();

    $response->assertJsonPath('data.email', 'nika@rgoli.ge')
        ->assertJsonPath('data.roles.0.slug', 'architect')
        ->assertJsonPath('data.direct_permissions', ['reports.view'])
        ->assertJsonPath('data.disciplines', ['architecture', 'interior']);

    expect($response->json('data.permissions'))->toContain('phases.manage', 'reports.view');

    app('auth')->forgetGuards();
    $this->postJson('/api/v1/auth/login', ['email' => 'nika@rgoli.ge', 'password' => 'secret123'])->assertOk();
});

it('deactivates a user and revokes their tokens', function (): void {
    $designer = demoUser('interior_designer');
    $designer->createToken('spa');

    $this->patchJson('/api/v1/users/'.$designer->id, ['is_active' => false])->assertOk()->assertJsonPath('data.is_active', false);

    expect($designer->tokens()->count())->toBe(0);
});

it('cannot deactivate yourself', function (): void {
    $this->patchJson('/api/v1/users/'.demoUser('owner')->id, ['is_active' => false])->assertUnprocessable();
});

it('sends a password reset link', function (): void {
    Notification::fake();
    $designer = demoUser('interior_designer');

    $this->postJson('/api/v1/users/'.$designer->id.'/password-reset-link')->assertOk();

    Notification::assertSentTo($designer, ResetPassword::class);
});

it('creates and edits roles, keeping the owner role complete', function (): void {
    $id = $this->postJson('/api/v1/roles', ['name' => 'Intern', 'permissions' => ['projects.view', 'issues.view']])
        ->assertCreated()->assertJsonPath('data.permissions', ['issues.view', 'projects.view'])->json('data.id');

    $this->patchJson('/api/v1/roles/'.$id, ['name' => 'Junior', 'permissions' => ['projects.view']])
        ->assertOk()->assertJsonPath('data.name', 'Junior');

    $owner = Role::query()->where('slug', 'owner')->firstOrFail();
    $this->patchJson('/api/v1/roles/'.$owner->id, ['permissions' => []])->assertOk();
    expect($owner->permissions()->count())->toBe(count(PermissionCatalog::all()));

    $this->postJson('/api/v1/roles', ['name' => 'Bad', 'permissions' => ['everything']])->assertUnprocessable();
    $this->deleteJson('/api/v1/roles/'.$id)->assertOk();
});

it('refuses to delete a role in use', function (): void {
    $role = Role::query()->create(['name' => 'Temp', 'guard_name' => 'web']);
    User::factory()->create()->assignRole($role);

    $this->deleteJson('/api/v1/roles/'.$role->id)->assertUnprocessable();
});

it('returns the grouped permission catalog', function (): void {
    $this->getJson('/api/v1/permissions')->assertOk()
        ->assertJsonPath('data.finance', ['finance.view', 'client_payments.manage', 'salary.request', 'salary.approve']);
});

it('queues the JSON export and exposes system stats', function (): void {
    Queue::fake();

    $this->postJson('/api/v1/admin/export')->assertAccepted();
    Queue::assertPushed(ExportSystemDataJob::class);

    $this->getJson('/api/v1/admin/stats')->assertOk()->assertJsonPath('data.projects', 3);
});

it('only allows demo reset when demo mode is on', function (): void {
    config(['rgoli.demo' => false]);
    $this->postJson('/api/v1/admin/reset-demo')->assertNotFound();
    $this->getJson('/api/v1/auth/demo-accounts')->assertNotFound();

    config(['rgoli.demo' => true]);
    $this->getJson('/api/v1/auth/demo-accounts')->assertOk()->assertJsonCount(4, 'data');
});
