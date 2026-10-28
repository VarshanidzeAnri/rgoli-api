<?php

use App\Enums\Status;
use App\Models\Issue;
use App\Models\Project;
use App\Notifications\AppNotification;
use Illuminate\Support\Facades\Notification;

beforeEach(fn () => seedDemo());

function urk(): Project
{
    return Project::query()->where('key', 'URK')->firstOrFail();
}

it('creates issues with sequential per-project keys', function (): void {
    signIn(demoUser('owner'));

    $this->postJson('/api/v1/issues', ['project_id' => urk()->id, 'title' => 'ფასადის ნახაზი', 'points' => 3])
        ->assertCreated()
        ->assertJsonPath('data.key', 'URK-4')
        ->assertJsonPath('data.status', 'todo')
        ->assertJsonPath('data.priority', 'medium');

    $this->postJson('/api/v1/issues', ['project_id' => urk()->id, 'title' => 'Second'])->assertJsonPath('data.key', 'URK-5');
});

it('validates issue payloads', function (): void {
    signIn(demoUser('owner'));

    $this->postJson('/api/v1/issues', ['points' => 0, 'priority' => 'urgent'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['project_id', 'title', 'points', 'priority']);
});

it('notifies new assignees', function (): void {
    Notification::fake();
    signIn(demoUser('owner'));
    $designer = demoUser('interior_designer');

    $this->postJson('/api/v1/issues', ['project_id' => urk()->id, 'title' => 'Render', 'assignee_ids' => [$designer->id]])->assertCreated();

    Notification::assertSentTo($designer, AppNotification::class, fn (AppNotification $n): bool => $n->kind === 'issue_assigned');
});

it('returns the board grouped by status and filtered', function (): void {
    signIn(demoUser('owner'));

    $board = $this->getJson('/api/v1/issues/board')->assertOk()->json('data');
    expect(array_keys($board))->toBe(Status::values())
        ->and(array_sum(array_map('count', $board)))->toBe(5);

    $mine = $this->getJson('/api/v1/issues/board?project_id='.urk()->id)->json('data');
    expect(array_sum(array_map('count', $mine)))->toBe(3);
});

it('moves an issue to another column below a given card and renumbers', function (): void {
    signIn(demoUser('owner'));
    $render = Issue::query()->where('title', 'ვიზუალიზაცია / რენდერი')->firstOrFail();
    $topo = Issue::query()->where('title', 'ტოპოგრაფიული გეგმის მომზადება')->firstOrFail();

    // Drop at the top of "progress".
    $this->patchJson('/api/v1/issues/'.$render->id.'/move', ['status' => 'progress', 'after_id' => null])
        ->assertOk()->assertJsonPath('data.status', 'progress');

    $column = Issue::query()->where('status', 'progress')->orderBy('position')->pluck('id')->all();
    expect($column)->toBe([$render->id, $topo->id]);

    // Then below the topo task.
    $this->patchJson('/api/v1/issues/'.$render->id.'/move', ['status' => 'progress', 'after_id' => $topo->id])->assertOk();
    expect(Issue::query()->where('status', 'progress')->orderBy('position')->pluck('id')->all())->toBe([$topo->id, $render->id]);
});

it('requires a valid status to move', function (): void {
    signIn(demoUser('owner'));
    $issue = Issue::query()->firstOrFail();

    $this->patchJson('/api/v1/issues/'.$issue->id.'/move', ['status' => 'archived', 'after_id' => null])->assertUnprocessable();
});

it('quick-edits fields without touching status', function (): void {
    signIn(demoUser('project_manager'));
    $issue = Issue::query()->where('project_id', urk()->id)->firstOrFail();

    $this->patchJson('/api/v1/issues/'.$issue->id, ['priority' => 'critical', 'points' => 13, 'status' => 'done'])
        ->assertOk()
        ->assertJsonPath('data.priority', 'critical')
        ->assertJsonPath('data.points', 13)
        ->assertJsonPath('data.status', $issue->status->value);
});

it('lets authors edit only their own comments', function (): void {
    $designer = signIn(demoUser('interior_designer'));
    $issue = Issue::query()->where('project_id', urk()->id)->firstOrFail();

    $commentId = $this->postJson('/api/v1/issues/'.$issue->id.'/comments', ['body' => 'კარგია'])->assertCreated()->json('data.id');
    $this->patchJson('/api/v1/comments/'.$commentId, ['body' => 'Edited'])->assertOk()->assertJsonPath('data.body', 'Edited');

    signIn(demoUser('project_manager'));
    $this->patchJson('/api/v1/comments/'.$commentId, ['body' => 'Hijack'])->assertForbidden();
    $this->getJson('/api/v1/issues/'.$issue->id.'/comments')->assertOk()->assertJsonPath('data.0.user.id', $designer->id);
});

it('serves calendar events for deadlines and milestones', function (): void {
    signIn(demoUser('owner'));

    $events = collect($this->getJson('/api/v1/calendar?from='.now()->subWeek()->toDateString().'&to='.now()->addMonths(3)->toDateString())->assertOk()->json('data'));

    expect($events->where('type', 'task'))->toHaveCount(5)
        ->and($events->where('type', 'milestone'))->toHaveCount(3);
});
