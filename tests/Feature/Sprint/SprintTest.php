<?php

use App\Enums\SprintState;
use App\Models\Issue;
use App\Models\Project;
use App\Models\Sprint;

beforeEach(function (): void {
    seedDemo();
    signIn(demoUser('owner'));
});

it('lists sprints with stats, active first', function (): void {
    $this->getJson('/api/v1/sprints')
        ->assertOk()
        ->assertJsonPath('data.0.state', 'active')
        ->assertJsonPath('data.0.stats.total', 2)
        ->assertJsonPath('data.0.stats.done', 1)
        ->assertJsonPath('data.0.stats.points_total', 11);
});

it('allows only one active sprint', function (): void {
    $planned = Sprint::query()->where('state', SprintState::Planned->value)->firstOrFail();

    $this->postJson('/api/v1/sprints/'.$planned->id.'/start')->assertUnprocessable()->assertJsonValidationErrors('sprint');
});

it('completes a sprint and carries unfinished issues into a planned sprint', function (): void {
    $active = Sprint::query()->where('state', SprintState::Active->value)->firstOrFail();
    $planned = Sprint::query()->where('state', SprintState::Planned->value)->firstOrFail();
    $topo = Issue::query()->where('title', 'ტოპოგრაფიული გეგმის მომზადება')->firstOrFail();

    $this->postJson('/api/v1/sprints/'.$active->id.'/complete', ['move_open_to' => $planned->id])
        ->assertOk()->assertJsonPath('data.state', 'completed');

    expect($planned->issues()->pluck('issues.id')->all())->toContain($topo->id);

    $this->postJson('/api/v1/sprints/'.$planned->id.'/start')->assertOk()->assertJsonPath('data.state', 'active');
});

it('keeps an issue in at most one open sprint', function (): void {
    $active = Sprint::query()->where('state', SprintState::Active->value)->firstOrFail();
    $planned = Sprint::query()->where('state', SprintState::Planned->value)->firstOrFail();
    $topo = Issue::query()->where('title', 'ტოპოგრაფიული გეგმის მომზადება')->firstOrFail();

    $this->postJson('/api/v1/sprints/'.$planned->id.'/issues', ['issue_ids' => [$topo->id]])->assertOk();

    expect($active->issues()->whereKey($topo->id)->exists())->toBeFalse()
        ->and($planned->issues()->whereKey($topo->id)->exists())->toBeTrue();

    $backlog = collect($this->getJson('/api/v1/issues?backlog=1')->json('data'))->pluck('id');
    expect($backlog)->not->toContain($topo->id);

    $this->deleteJson('/api/v1/sprints/'.$planned->id.'/issues/'.$topo->id)->assertOk();
    expect(collect($this->getJson('/api/v1/issues?backlog=1')->json('data'))->pluck('id'))->toContain($topo->id);
});

it('refuses to add invisible issues to a sprint', function (): void {
    signIn(demoUser('project_manager'));
    $off = Project::query()->where('key', 'OFF')->firstOrFail();
    $hidden = Issue::query()->where('project_id', $off->id)->firstOrFail();
    $planned = Sprint::query()->where('state', SprintState::Planned->value)->firstOrFail();

    $this->postJson('/api/v1/sprints/'.$planned->id.'/issues', ['issue_ids' => [$hidden->id]])->assertForbidden();
});

it('creates, edits and deletes a sprint', function (): void {
    $id = $this->postJson('/api/v1/sprints', [
        'name' => 'Sprint 03',
        'start_date' => now()->addDays(23)->toDateString(),
        'end_date' => now()->addDays(37)->toDateString(),
    ])->assertCreated()->assertJsonPath('data.state', 'planned')->json('data.id');

    $this->patchJson('/api/v1/sprints/'.$id, ['goal' => 'Handover'])->assertOk()->assertJsonPath('data.goal', 'Handover');
    $this->deleteJson('/api/v1/sprints/'.$id)->assertOk();

    $active = Sprint::query()->where('state', SprintState::Active->value)->firstOrFail();
    $this->deleteJson('/api/v1/sprints/'.$active->id)->assertUnprocessable();
});
