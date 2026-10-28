<?php

use App\Enums\PaymentStatus;
use App\Models\Project;
use App\Models\SalaryRequest;
use App\Notifications\AppNotification;
use Illuminate\Support\Facades\Notification;

beforeEach(fn () => seedDemo());

it('summarizes finance with decimal-safe totals', function (): void {
    signIn(demoUser('finance_manager'));

    $this->getJson('/api/v1/finance/summary')
        ->assertOk()
        ->assertJsonPath('data.total_budget', '20360.00')     // 9960 + 6200 + 4200
        ->assertJsonPath('data.paid_by_clients', '4700.00')   // 3500 + 1200
        ->assertJsonPath('data.remaining', '15660.00')
        ->assertJsonPath('data.approved_salaries', '300.00')
        ->assertJsonPath('data.pending_requests', 1)
        ->assertJsonPath('data.paid_percent', 23);
});

it('records client payments and updates paid', function (): void {
    signIn(demoUser('finance_manager'));
    $sup = Project::query()->where('key', 'SUP')->firstOrFail();

    $this->postJson('/api/v1/projects/'.$sup->id.'/payments', ['amount' => '1000.10', 'paid_at' => now()->toDateString(), 'method' => 'cash'])
        ->assertCreated()->assertJsonPath('data.amount', '1000.10');

    $this->getJson('/api/v1/projects/'.$sup->id)->assertJsonPath('data.finance.paid', '1000.10')->assertJsonPath('data.finance.remaining', '3199.90');

    $this->postJson('/api/v1/projects/'.$sup->id.'/payments', ['amount' => '-5', 'paid_at' => now()->addDay()->toDateString(), 'method' => 'crypto'])
        ->assertUnprocessable()->assertJsonValidationErrors(['amount', 'paid_at', 'method']);
});

it('submits a salary request and notifies approvers', function (): void {
    Notification::fake();
    signIn(demoUser('interior_designer'));
    $off = Project::query()->where('key', 'OFF')->firstOrFail();

    $this->postJson('/api/v1/salary-requests', ['project_id' => $off->id, 'title' => 'Moodboard v2', 'amount' => '250'])
        ->assertCreated()->assertJsonPath('data.status', 'pending');

    Notification::assertSentTo(demoUser('finance_manager'), AppNotification::class);
    Notification::assertSentTo(demoUser('owner'), AppNotification::class);
    Notification::assertNotSentTo(demoUser('interior_designer'), AppNotification::class);
});

it('approves a request recording who and when, exactly once', function (): void {
    $finance = signIn(demoUser('finance_manager'));
    $pending = SalaryRequest::query()->where('status', PaymentStatus::Pending->value)->firstOrFail();

    $this->postJson('/api/v1/salary-requests/'.$pending->id.'/approve', ['note' => 'OK'])
        ->assertOk()
        ->assertJsonPath('data.status', 'approved')
        ->assertJsonPath('data.decider.id', $finance->id);

    expect($pending->fresh()?->decided_at)->not->toBeNull();

    $this->postJson('/api/v1/salary-requests/'.$pending->id.'/reject')->assertForbidden();
});

it('never lets someone decide their own request', function (): void {
    $finance = signIn(demoUser('finance_manager'));
    $own = SalaryRequest::query()->create([
        'project_id' => Project::query()->where('key', 'URK')->value('id'),
        'user_id' => $finance->id,
        'title' => 'Mine',
        'amount' => '10',
        'status' => PaymentStatus::Pending,
    ]);

    $this->postJson('/api/v1/salary-requests/'.$own->id.'/approve')->assertForbidden();
});

it('lets the author edit or withdraw only while pending', function (): void {
    $manager = signIn(demoUser('project_manager'));
    $pending = SalaryRequest::query()->where('user_id', $manager->id)->firstOrFail();

    $this->patchJson('/api/v1/salary-requests/'.$pending->id, ['amount' => '550'])->assertOk()->assertJsonPath('data.amount', '550.00');

    signIn(demoUser('finance_manager'));
    $this->postJson('/api/v1/salary-requests/'.$pending->id.'/reject')->assertOk();

    signIn($manager);
    $this->patchJson('/api/v1/salary-requests/'.$pending->id, ['amount' => '1'])->assertForbidden();
    $this->deleteJson('/api/v1/salary-requests/'.$pending->id)->assertForbidden();
});

it('reports and exports CSV', function (): void {
    signIn(demoUser('owner'));

    $this->getJson('/api/v1/reports/summary')
        ->assertOk()
        ->assertJsonPath('data.task_completion_percent', 20)
        ->assertJsonPath('data.average_progress', 41)
        ->assertJsonPath('data.outstanding_budget', '15660.00');

    $csv = $this->get('/api/v1/reports/export?type=tasks')->assertOk()->assertDownload()->streamedContent();
    expect($csv)->toContain('URK-2')->toContain('ტოპოგრაფიული გეგმის მომზადება');
});
