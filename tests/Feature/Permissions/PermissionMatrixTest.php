<?php

use App\Enums\PaymentStatus;
use App\Models\Issue;
use App\Models\Project;
use App\Models\Role;
use App\Models\SalaryRequest;
use App\Models\User;

/*
 * Role × endpoint matrix from the product brief (section 3.2).
 * Each case authenticates as a seeded demo user (or a fresh Architect/Member)
 * and asserts the endpoint is allowed (2xx) or forbidden (403).
 */

beforeEach(fn () => seedDemo());

/**
 * @return array<string, User>
 */
function matrixUsers(): array
{
    return [
        'owner' => demoUser('owner'),
        'project_manager' => demoUser('project_manager'),
        'interior_designer' => demoUser('interior_designer'),
        'finance_manager' => demoUser('finance_manager'),
        'architect' => User::factory()->role('architect')->disciplines(['architecture'])->create(),
        'member' => User::factory()->role('member')->disciplines(['architecture'])->create(),
    ];
}

/**
 * name => [method, uri resolver, payload resolver, roles allowed]
 *
 * @return array<string, array{0: string, 1: Closure(): string, 2: Closure(): array<string, mixed>, 3: list<string>}>
 */
function matrixEndpoints(): array
{
    $urk = fn (): Project => Project::query()->where('key', 'URK')->firstOrFail();
    $issue = fn (): Issue => Issue::query()->where('project_id', $urk()->id)->where('number', 3)->firstOrFail();
    $pending = fn (): SalaryRequest => SalaryRequest::query()->where('status', PaymentStatus::Pending->value)->firstOrFail();
    $none = fn (): array => [];

    return [
        'list projects' => ['GET', fn () => '/api/v1/projects', $none, ['owner', 'project_manager', 'interior_designer', 'finance_manager', 'architect', 'member']],
        'create project' => ['POST', fn () => '/api/v1/projects', fn () => [
            'key' => 'NEW'.random_int(100, 999), 'name' => 'New', 'client_name' => 'Client',
            'discipline' => 'architecture', 'status' => 'todo', 'budget' => '1000',
        ], ['owner', 'project_manager']],
        'update project' => ['PATCH', fn () => '/api/v1/projects/'.$urk()->id, fn () => ['progress' => 50], ['owner', 'project_manager']],
        'delete project' => ['DELETE', fn () => '/api/v1/projects/'.$urk()->id, $none, ['owner']],
        'manage members' => ['PUT', fn () => '/api/v1/projects/'.$urk()->id.'/members', fn () => ['member_ids' => [demoUser('owner')->id]], ['owner', 'project_manager']],
        'manage phases' => ['POST', fn () => '/api/v1/projects/'.$urk()->id.'/phases', fn () => ['name' => 'Extra'], ['owner', 'project_manager', 'architect']],
        'view issues' => ['GET', fn () => '/api/v1/issues', $none, ['owner', 'project_manager', 'interior_designer', 'architect', 'member']],
        'create issue' => ['POST', fn () => '/api/v1/issues', fn () => ['project_id' => $urk()->id, 'title' => 'New task'], ['owner', 'project_manager', 'interior_designer', 'architect']],
        'move issue' => ['PATCH', fn () => '/api/v1/issues/'.$issue()->id.'/move', fn () => ['status' => 'progress', 'after_id' => null], ['owner', 'project_manager', 'interior_designer', 'architect']],
        'delete issue' => ['DELETE', fn () => '/api/v1/issues/'.$issue()->id, $none, ['owner', 'project_manager']],
        'comment issue' => ['POST', fn () => '/api/v1/issues/'.$issue()->id.'/comments', fn () => ['body' => 'Hi'], ['owner', 'project_manager', 'interior_designer', 'architect', 'member']],
        'manage sprints' => ['POST', fn () => '/api/v1/sprints', fn () => ['name' => 'S', 'start_date' => now()->toDateString(), 'end_date' => now()->addWeek()->toDateString()], ['owner', 'project_manager']],
        'view files' => ['GET', fn () => '/api/v1/files', $none, ['owner', 'project_manager', 'interior_designer', 'finance_manager', 'architect', 'member']],
        'view map' => ['GET', fn () => '/api/v1/map/projects', $none, ['owner', 'project_manager', 'interior_designer', 'architect', 'member']],
        'update location' => ['PATCH', fn () => '/api/v1/projects/'.$urk()->id.'/location', fn () => ['lat' => 41.6, 'lng' => 41.6], ['owner', 'project_manager']],
        'finance summary' => ['GET', fn () => '/api/v1/finance/summary', $none, ['owner', 'finance_manager']],
        'record payment' => ['POST', fn () => '/api/v1/projects/'.$urk()->id.'/payments', fn () => ['amount' => '100', 'paid_at' => now()->toDateString(), 'method' => 'cash'], ['owner', 'finance_manager']],
        'request salary' => ['POST', fn () => '/api/v1/salary-requests', fn () => ['project_id' => $urk()->id, 'title' => 'Work', 'amount' => '50'], ['owner', 'project_manager', 'interior_designer', 'finance_manager', 'architect', 'member']],
        'approve salary' => ['POST', fn () => '/api/v1/salary-requests/'.$pending()->id.'/approve', $none, ['owner', 'finance_manager']],
        'view reports' => ['GET', fn () => '/api/v1/reports/summary', $none, ['owner', 'project_manager', 'finance_manager']],
        'export reports' => ['GET', fn () => '/api/v1/reports/export?type=projects', $none, ['owner', 'finance_manager']],
        'view users' => ['GET', fn () => '/api/v1/users', $none, ['owner', 'project_manager']],
        'manage users' => ['POST', fn () => '/api/v1/users', fn () => [
            'name' => 'New', 'email' => 'new'.random_int(1, 99999).'@rgoli.ge', 'password' => 'secret123', 'role_id' => Role::query()->where('slug', 'member')->value('id'),
        ], ['owner']],
        'manage roles' => ['POST', fn () => '/api/v1/roles', fn () => ['name' => 'Role '.random_int(1, 99999)], ['owner']],
        'system export' => ['POST', fn () => '/api/v1/admin/export', $none, ['owner']],
    ];
}

$roles = ['owner', 'project_manager', 'interior_designer', 'finance_manager', 'architect', 'member'];
$cases = [];
foreach (array_keys(matrixEndpoints()) as $endpoint) {
    foreach ($roles as $role) {
        $cases["{$role} → {$endpoint}"] = [$role, $endpoint];
    }
}

it('enforces the role permission matrix', function (string $role, string $endpoint): void {
    [$method, $uri, $payload, $allowed] = matrixEndpoints()[$endpoint];
    $user = matrixUsers()[$role];
    signIn($user);

    // Make sure visibility isn't what blocks a permitted role: add everyone to URK.
    Project::query()->where('key', 'URK')->firstOrFail()->members()->syncWithoutDetaching([$user->id]);

    $response = $this->json($method, $uri(), $payload());

    if (in_array($role, $allowed, true)) {
        expect($response->getStatusCode())->toBeIn([200, 201, 202, 204], "{$role} should be allowed: {$endpoint} — got {$response->getStatusCode()} ".$response->getContent());
    } else {
        $response->assertForbidden();
    }
})->with($cases);

it('returns translated 403 messages', function (): void {
    actingAsRole('member');

    $this->withHeader('Accept-Language', 'ru')->getJson('/api/v1/finance/summary')
        ->assertForbidden()
        ->assertJsonPath('message', 'Нет доступа');

    $this->withHeader('Accept-Language', 'ka')->getJson('/api/v1/finance/summary')
        ->assertForbidden()
        ->assertJsonPath('message', 'წვდომა არ არის');
});

it('lets the owner do anything through Gate::before but never delete a system role', function (): void {
    signIn(demoUser('owner'));
    $ownerRole = Role::query()->where('slug', 'owner')->firstOrFail();

    $this->deleteJson('/api/v1/roles/'.$ownerRole->id)->assertForbidden();
});

it('grants extra direct permissions on top of the role', function (): void {
    $member = User::factory()->role('member')->create();
    $member->givePermissionTo('reports.view');
    signIn($member);

    $this->getJson('/api/v1/reports/summary')->assertOk();
    $this->getJson('/api/v1/reports/export?type=projects')->assertForbidden();
});
