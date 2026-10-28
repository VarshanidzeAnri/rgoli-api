<?php

use App\Models\Issue;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\User;

/*
 * Project::visibleTo(): member OR discipline match, unless projects.view_all.
 * Demo: URK (architecture; Admin, Designer, Manager), OFF (interior; Admin, Designer), SUP (construction; Admin, Manager).
 */

beforeEach(fn () => seedDemo());

function projectKeysFor(User $user): array
{
    signIn($user);

    return collect(test()->getJson('/api/v1/projects')->assertOk()->json('data'))->pluck('key')->sort()->values()->all();
}

it('shows every project with projects.view_all', function (): void {
    expect(projectKeysFor(demoUser('finance_manager')))->toBe(['OFF', 'SUP', 'URK']);
    expect(projectKeysFor(demoUser('owner')))->toBe(['OFF', 'SUP', 'URK']);
});

it('shows member projects and discipline projects otherwise', function (): void {
    expect(projectKeysFor(demoUser('interior_designer')))->toBe(['OFF', 'URK']);
    expect(projectKeysFor(demoUser('project_manager')))->toBe(['SUP', 'URK']);
});

it('shows discipline projects to a non-member', function (): void {
    $architect = User::factory()->role('architect')->disciplines(['architecture'])->create();

    expect(projectKeysFor($architect))->toBe(['URK']);
});

it('shows nothing to a member without disciplines or memberships', function (): void {
    expect(projectKeysFor(User::factory()->role('member')->create()))->toBe([]);
});

it('returns 403 for an invisible project and its children', function (): void {
    signIn(demoUser('project_manager'));
    $off = Project::query()->where('key', 'OFF')->firstOrFail();
    $issue = Issue::query()->where('project_id', $off->id)->firstOrFail();
    $file = ProjectFile::query()->where('project_id', $off->id)->firstOrFail();

    $this->getJson('/api/v1/projects/'.$off->id)->assertForbidden();
    $this->getJson('/api/v1/issues/'.$issue->id)->assertForbidden();
    $this->getJson('/api/v1/files/'.$file->id.'/download')->assertForbidden();
});

it('filters issues, files, activity and search by visible projects', function (): void {
    signIn(demoUser('project_manager'));

    $issueProjects = collect($this->getJson('/api/v1/issues')->json('data'))->pluck('project.key')->unique()->sort()->values()->all();
    $fileProjects = collect($this->getJson('/api/v1/files')->json('data'))->pluck('project.key')->unique()->sort()->values()->all();
    $activityProjects = collect($this->getJson('/api/v1/activity?per_page=100')->json('data'))->pluck('project.key')->filter()->unique()->values()->all();

    expect($issueProjects)->toBe(['SUP', 'URK'])
        ->and($fileProjects)->toBe(['URK'])
        ->and($activityProjects)->not->toContain('OFF');

    $search = $this->getJson('/api/v1/search?q=RGOLI')->json('data.projects');
    expect($search)->toBe([]);
});

it('hides budget numbers without finance.view', function (): void {
    signIn(demoUser('project_manager'));
    $urk = Project::query()->where('key', 'URK')->firstOrFail();

    $this->getJson('/api/v1/projects/'.$urk->id)->assertOk()->assertJsonPath('data.finance', null);
    $this->getJson('/api/v1/dashboard')->assertOk()->assertJsonPath('data.kpis.budget', null);
});

it('shows budget with paid computed from client payments', function (): void {
    signIn(demoUser('finance_manager'));
    $urk = Project::query()->where('key', 'URK')->firstOrFail();

    $this->getJson('/api/v1/projects/'.$urk->id)->assertOk()
        ->assertJsonPath('data.finance.budget', '13800.00')
        ->assertJsonPath('data.finance.agreed_price', '9960.00')
        ->assertJsonPath('data.finance.paid', '3500.00')
        ->assertJsonPath('data.finance.remaining', '6460.00');
});

it('shows only own salary requests without finance.view', function (): void {
    signIn(demoUser('interior_designer'));
    $owners = collect($this->getJson('/api/v1/salary-requests')->assertOk()->json('data'))->pluck('user.id')->unique()->values()->all();

    expect($owners)->toBe([demoUser('interior_designer')->id]);

    signIn(demoUser('finance_manager'));
    expect($this->getJson('/api/v1/salary-requests')->json('data'))->toHaveCount(2);
});
