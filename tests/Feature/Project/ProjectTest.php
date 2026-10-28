<?php

use App\Models\ActivityLog;
use App\Models\ClientPayment;
use App\Models\Issue;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\SalaryRequest;

beforeEach(fn () => seedDemo());

it('lists projects with status counts and filters', function (): void {
    signIn(demoUser('owner'));

    $this->getJson('/api/v1/projects?status=progress,review')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('meta.status_counts.todo', 1)
        ->assertJsonPath('meta.status_counts.progress', 1)
        ->assertJsonPath('meta.status_counts.review', 1);

    $this->getJson('/api/v1/projects?search=Office')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.key', 'OFF');
    $this->getJson('/api/v1/projects?discipline=construction')->assertJsonPath('data.0.key', 'SUP');
});

it('shows the current phase and task totals', function (): void {
    signIn(demoUser('owner'));
    $urk = Project::query()->where('key', 'URK')->firstOrFail();

    $this->getJson('/api/v1/projects/'.$urk->id)
        ->assertOk()
        ->assertJsonPath('data.current_phase.name', 'ფაზა 2 — არქიტექტურული პროექტირება')
        ->assertJsonPath('data.tasks.total', 3)
        ->assertJsonPath('data.tasks.done', 1)
        ->assertJsonCount(3, 'data.phases');
});

it('creates a project, adds the creator as member and logs activity', function (): void {
    $manager = signIn(demoUser('project_manager'));

    $response = $this->postJson('/api/v1/projects', [
        'key' => 'BTM',
        'name' => 'ბათუმის ვილა',
        'client_name' => 'ნინო',
        'discipline' => 'architecture',
        'status' => 'todo',
        'budget' => '25000.50',
        'start_date' => now()->toDateString(),
        'end_date' => now()->addMonth()->toDateString(),
    ])->assertCreated()->assertJsonPath('data.key', 'BTM');

    $project = Project::query()->findOrFail($response->json('data.id'));
    expect($project->members->pluck('id')->all())->toContain($manager->id)
        ->and($project->budget)->toBe('25000.50')
        ->and(ActivityLog::query()->where('subject_type', 'project')->where('subject_id', $project->id)->where('event', 'created')->exists())->toBeTrue();
});

it('validates projects with translated attribute names', function (): void {
    signIn(demoUser('owner'));

    $this->withHeader('Accept-Language', 'en')
        ->postJson('/api/v1/projects', ['key' => 'URK', 'budget' => 'abc', 'end_date' => '2020-01-01', 'start_date' => '2021-01-01'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['key', 'name', 'client_name', 'discipline', 'status', 'budget', 'end_date']);

    $message = $this->withHeader('Accept-Language', 'ka')->postJson('/api/v1/projects', [])->json('errors.client_name.0');
    expect($message)->toContain('კლიენტი');
});

it('soft-deletes a project together with its tasks, files, requests and payments', function (): void {
    signIn(demoUser('owner'));
    $urk = Project::query()->where('key', 'URK')->firstOrFail();

    $this->deleteJson('/api/v1/projects/'.$urk->id)->assertOk();

    expect(Project::query()->find($urk->id))->toBeNull()
        ->and(Project::withTrashed()->find($urk->id))->not->toBeNull()
        ->and(Issue::query()->where('project_id', $urk->id)->count())->toBe(0)
        ->and(Issue::withTrashed()->where('project_id', $urk->id)->count())->toBe(3)
        ->and(ProjectFile::query()->where('project_id', $urk->id)->count())->toBe(0)
        ->and(SalaryRequest::query()->where('project_id', $urk->id)->count())->toBe(0)
        ->and(ClientPayment::query()->where('project_id', $urk->id)->count())->toBe(0);

    $this->getJson('/api/v1/projects/'.$urk->id)->assertNotFound();
});

it('updates the location and syncs members', function (): void {
    signIn(demoUser('owner'));
    $sup = Project::query()->where('key', 'SUP')->firstOrFail();
    $designer = demoUser('interior_designer');

    $this->patchJson('/api/v1/projects/'.$sup->id.'/location', ['lat' => 41.6, 'lng' => 41.62])
        ->assertOk()->assertJsonPath('data.lat', 41.6);

    $this->putJson('/api/v1/projects/'.$sup->id.'/members', ['member_ids' => [$designer->id]])
        ->assertOk()->assertJsonCount(1, 'data.members');
});

it('manages and reorders phases', function (): void {
    signIn(demoUser('owner'));
    $sup = Project::query()->where('key', 'SUP')->firstOrFail();

    $id = $this->postJson('/api/v1/projects/'.$sup->id.'/phases', ['name' => 'Handover'])->assertCreated()->json('data.id');
    $ids = $sup->phases()->pluck('id')->all();

    $this->putJson('/api/v1/projects/'.$sup->id.'/phases/order', ['phase_ids' => array_reverse($ids)])
        ->assertOk()->assertJsonPath('data.0.id', $id);

    $this->putJson('/api/v1/projects/'.$sup->id.'/phases/order', ['phase_ids' => [$id]])->assertUnprocessable();
});

it('serves timeline and map data', function (): void {
    signIn(demoUser('owner'));

    $this->getJson('/api/v1/timeline?from='.now()->subMonth()->toDateString().'&to='.now()->addMonths(3)->toDateString())
        ->assertOk()->assertJsonCount(3, 'data');
    $this->getJson('/api/v1/map/projects')->assertOk()->assertJsonCount(3, 'data');
    $this->getJson('/api/v1/timeline')->assertUnprocessable();
});
