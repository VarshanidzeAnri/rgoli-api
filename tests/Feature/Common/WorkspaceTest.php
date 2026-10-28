<?php

use App\Notifications\AppNotification;

beforeEach(fn () => seedDemo());

it('builds the dashboard', function (): void {
    signIn(demoUser('owner'));

    $this->getJson('/api/v1/dashboard')
        ->assertOk()
        ->assertJsonPath('data.kpis.active_projects', 3)
        ->assertJsonPath('data.kpis.open_tasks', 4)
        ->assertJsonPath('data.kpis.critical_tasks', 1)
        ->assertJsonPath('data.kpis.budget.total', '20360.00')
        ->assertJsonPath('data.kpis.pending_salary_requests', 1)
        ->assertJsonStructure(['data' => ['project_status_distribution', 'projects', 'activity', 'workload', 'my_tasks_this_week']]);
});

it('searches across visible projects, tasks and files', function (): void {
    signIn(demoUser('owner'));

    $this->getJson('/api/v1/search?q=ბათუმი')->assertOk()->assertJsonCount(1, 'data.projects');
    $this->getJson('/api/v1/search?q=moodboard')->assertOk()->assertJsonCount(1, 'data.files');
    $this->getJson('/api/v1/search?q=ტოპოგრაფიული')->assertOk()->assertJsonCount(1, 'data.issues');
    $this->getJson('/api/v1/search')->assertUnprocessable();
});

it('lists and reads notifications in the viewer language', function (): void {
    $user = signIn(demoUser('interior_designer'));
    $user->notify(new AppNotification('project_created', ['project' => 'Villa']));

    $this->withHeader('Accept-Language', 'en')->getJson('/api/v1/notifications')
        ->assertOk()
        ->assertJsonPath('meta.unread', 1)
        ->assertJsonPath('data.0.message', 'You were added to project “Villa”');

    $this->withHeader('Accept-Language', 'ka')->getJson('/api/v1/notifications')
        ->assertJsonPath('data.0.message', 'თქვენ დაგამატეს პროექტში „Villa“');

    $this->postJson('/api/v1/notifications/read-all')->assertOk();
    $this->getJson('/api/v1/notifications')->assertJsonPath('meta.unread', 0);
});

it('falls back to Georgian for unsupported languages', function (): void {
    $this->withHeader('Accept-Language', 'fr-FR,fr;q=0.9')->getJson('/api/v1/me')
        ->assertUnauthorized()
        ->assertHeader('Content-Language', 'ka');

    $this->withHeader('Accept-Language', 'ru-RU,ru;q=0.9')->getJson('/api/v1/me')
        ->assertHeader('Content-Language', 'ru');
});

it('updates the profile and password', function (): void {
    $user = signIn(demoUser('interior_designer'));

    $this->patchJson('/api/v1/me', ['name' => 'Ana', 'job_title' => 'Lead designer'])->assertOk()->assertJsonPath('data.user.name', 'Ana');

    $this->putJson('/api/v1/me/password', ['current_password' => 'wrong', 'password' => 'newpass123', 'password_confirmation' => 'newpass123'])
        ->assertUnprocessable()->assertJsonValidationErrors('current_password');

    $this->putJson('/api/v1/me/password', ['current_password' => 'designer123', 'password' => 'newpass123', 'password_confirmation' => 'newpass123'])
        ->assertOk();

    expect(Hash::check('newpass123', (string) $user->fresh()?->password))->toBeTrue();
});

it('exposes public config and login stats', function (): void {
    $this->getJson('/api/v1/config')->assertOk()->assertJsonStructure(['data' => ['version', 'demo', 'uploads' => ['max_kb', 'extensions']]]);
    $this->getJson('/api/v1/public/stats')->assertOk()->assertJsonPath('data.active_projects', 3)->assertJsonPath('data.team_members', 4);
});
