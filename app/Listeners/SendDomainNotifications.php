<?php

namespace App\Listeners;

use App\Enums\PaymentStatus;
use App\Events\IssueAssigned;
use App\Events\IssueMoved;
use App\Events\ProjectCreated;
use App\Events\SalaryRequestDecided;
use App\Events\SalaryRequestSubmitted;
use App\Models\User;
use App\Notifications\AppNotification;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * Turns domain events into database notifications (queued) and finance/audit log lines.
 */
class SendDomainNotifications
{
    public function __construct(private UserRepositoryInterface $users) {}

    public function onProjectCreated(ProjectCreated $event): void
    {
        $project = $event->project->loadMissing('members');
        $this->notify(
            $project->members->reject(fn (User $u): bool => $u->id === $event->actor?->id),
            new AppNotification('project_created', ['project' => $project->name], '/projects/'.$project->id),
        );
    }

    public function onIssueAssigned(IssueAssigned $event): void
    {
        $issue = $event->issue->loadMissing('project');
        $users = $this->users->findMany($event->userIds)->reject(fn (User $u): bool => $u->id === $event->actor?->id);
        $this->notify($users, new AppNotification('issue_assigned', [
            'key' => $issue->key(),
            'title' => $issue->title,
        ], '/board?issue='.$issue->id));
    }

    public function onIssueMoved(IssueMoved $event): void
    {
        $issue = $event->issue->loadMissing(['project', 'assignees']);
        $this->notify(
            $issue->assignees->reject(fn (User $u): bool => $u->id === $event->actor?->id),
            new AppNotification('issue_moved', [
                'key' => $issue->key(),
                'title' => $issue->title,
                'status' => $event->to->value,
            ], '/board?issue='.$issue->id),
        );
    }

    public function onSalaryRequestSubmitted(SalaryRequestSubmitted $event): void
    {
        $request = $event->request->loadMissing('user');
        $approvers = $this->users->withPermission('salary.approve', $request->user_id);
        $this->notify($approvers, new AppNotification('salary_submitted', [
            'name' => $request->user->name,
            'title' => $request->title,
            'amount' => $request->amount,
        ], '/finance'));

        Log::channel('finance')->info('salary_request.submitted', [
            'id' => $request->id, 'user_id' => $request->user_id, 'amount' => $request->amount,
        ]);
    }

    public function onSalaryRequestDecided(SalaryRequestDecided $event): void
    {
        $request = $event->request->loadMissing('user');
        $kind = $request->status === PaymentStatus::Approved ? 'salary_approved' : 'salary_rejected';
        $this->notify(collect([$request->user]), new AppNotification($kind, [
            'title' => $request->title,
            'amount' => $request->amount,
        ], '/finance'));

        Log::channel('finance')->info('salary_request.decided', [
            'id' => $request->id, 'status' => $request->status->value, 'decided_by' => $event->decider->id,
        ]);
    }

    /**
     * @return array<class-string, string>
     */
    public function subscribe(Dispatcher $events): array
    {
        return [
            ProjectCreated::class => 'onProjectCreated',
            IssueAssigned::class => 'onIssueAssigned',
            IssueMoved::class => 'onIssueMoved',
            SalaryRequestSubmitted::class => 'onSalaryRequestSubmitted',
            SalaryRequestDecided::class => 'onSalaryRequestDecided',
        ];
    }

    /**
     * @param  Collection<int, User>  $users
     */
    private function notify(Collection $users, AppNotification $notification): void
    {
        if ($users->isNotEmpty()) {
            Notification::send($users, $notification);
        }
    }
}
