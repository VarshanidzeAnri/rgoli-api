<?php

use App\Models\ClientPayment;
use App\Models\Comment;
use App\Models\Issue;
use App\Models\Project;
use App\Models\ProjectPhase;
use App\Models\Role;
use App\Notifications\AppNotification;

/*
 * Happy paths for endpoints not covered by the feature-specific tests.
 */

beforeEach(function (): void {
    seedDemo();
    signIn(demoUser('owner'));
});

it('updates and deletes phases', function (): void {
    $phase = ProjectPhase::query()->firstOrFail();

    $this->patchJson('/api/v1/phases/'.$phase->id, ['status' => 'done', 'order' => 9])
        ->assertOk()->assertJsonPath('data.status', 'done')->assertJsonPath('data.order', 9);
    $this->patchJson('/api/v1/phases/'.$phase->id, ['status' => 'archived'])->assertUnprocessable();
    $this->getJson('/api/v1/projects/'.$phase->project_id.'/phases')->assertOk();
    $this->deleteJson('/api/v1/phases/'.$phase->id)->assertOk();

    expect(ProjectPhase::query()->find($phase->id))->toBeNull();
});

it('deletes issues and own comments', function (): void {
    $issue = Issue::query()->firstOrFail();
    $comment = Comment::query()->create(['issue_id' => $issue->id, 'user_id' => demoUser('owner')->id, 'body' => 'x']);

    $this->deleteJson('/api/v1/comments/'.$comment->id)->assertOk();
    $this->getJson('/api/v1/issues/'.$issue->id)->assertOk()->assertJsonPath('data.comments_count', 0);
    $this->deleteJson('/api/v1/issues/'.$issue->id)->assertOk();
    $this->getJson('/api/v1/issues/'.$issue->id)->assertNotFound();
});

it('lists, edits and deletes client payments', function (): void {
    $payment = ClientPayment::query()->firstOrFail();

    $this->getJson('/api/v1/client-payments')->assertOk()->assertJsonCount(3, 'data');
    $this->getJson('/api/v1/projects/'.$payment->project_id.'/payments')->assertOk();
    $this->patchJson('/api/v1/client-payments/'.$payment->id, ['amount' => '2500.50'])->assertOk()->assertJsonPath('data.amount', '2500.50');
    $this->deleteJson('/api/v1/client-payments/'.$payment->id)->assertOk();

    expect(ClientPayment::query()->find($payment->id))->toBeNull();
});

it('lists and filters users and roles', function (): void {
    $designer = demoUser('interior_designer');
    $role = Role::query()->where('slug', 'interior_designer')->firstOrFail();

    $this->getJson('/api/v1/users?search=designer')->assertOk()->assertJsonCount(1, 'data');
    $this->getJson('/api/v1/users?role_id='.$role->id)->assertOk()->assertJsonPath('data.0.id', $designer->id);
    $this->getJson('/api/v1/users?active=0')->assertOk()->assertJsonCount(0, 'data');
    $this->getJson('/api/v1/users/'.$designer->id)->assertOk()->assertJsonPath('data.disciplines', ['interior']);
    $this->getJson('/api/v1/roles')->assertOk()->assertJsonCount(6, 'data')->assertJsonPath('data.0.is_system', true);
    $this->getJson('/api/v1/people')->assertOk()->assertJsonCount(4, 'data');
});

it('reads a single notification and activity feeds', function (): void {
    $owner = demoUser('owner');
    $owner->notify(new AppNotification('generic'));
    $id = (string) $owner->notifications()->value('id');
    $urk = Project::query()->where('key', 'URK')->firstOrFail();

    $this->postJson('/api/v1/notifications/'.$id.'/read')->assertOk();
    expect($owner->unreadNotifications()->count())->toBe(0);

    $this->getJson('/api/v1/activity')->assertOk()->assertJsonStructure(['data' => [['id', 'event', 'subject_type', 'meta', 'created_at']]]);
    $this->getJson('/api/v1/projects/'.$urk->id.'/activity')->assertOk();
});

it('signs out everywhere', function (): void {
    $owner = demoUser('owner');
    $owner->createToken('a');
    $owner->createToken('b');
    app('auth')->forgetGuards();

    $token = $this->postJson('/api/v1/auth/login', ['email' => 'admin@rgoli.ge', 'password' => 'admin123'])->json('data.token');
    $this->withToken((string) $token)->postJson('/api/v1/auth/logout?everywhere=1')->assertNoContent();

    expect($owner->tokens()->count())->toBe(0);
});
