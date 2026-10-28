<?php

namespace App\Services;

use App\Enums\Status;
use App\Http\Resources\ActivityResource;
use App\Http\Resources\IssueResource;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use App\Models\User;
use App\Repositories\Contracts\ActivityRepositoryInterface;
use App\Repositories\Contracts\IssueRepositoryInterface;
use App\Repositories\Contracts\ProjectRepositoryInterface;
use App\Repositories\Contracts\SalaryRequestRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Support\Money;
use Illuminate\Http\Request;

/**
 * Builds the dashboard payload. Budget numbers appear only with finance.view.
 */
class DashboardService
{
    public function __construct(
        private ProjectRepositoryInterface $projects,
        private IssueRepositoryInterface $issues,
        private SalaryRequestRepositoryInterface $salaries,
        private ActivityRepositoryInterface $activity,
        private UserRepositoryInterface $users,
        private WorkloadService $workload,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function build(User $user, Request $request): array
    {
        $projects = $this->projects->allVisibleWithTotals($user);
        $issueCounts = $this->issues->statusCounts($user);
        $canFinance = $user->can('finance.view');

        $statusDistribution = [];
        foreach (Status::values() as $status) {
            $statusDistribution[$status] = $projects->filter(fn (Project $p): bool => $p->status->value === $status)->count();
        }

        $budget = null;
        if ($canFinance) {
            $agreed = Money::of(0);
            $paid = Money::of(0);
            foreach ($projects as $project) {
                $agreed = Money::add($agreed, $project->agreedPrice());
                $paid = Money::add($paid, $project->paidAmount());
            }
            $budget = ['total' => $agreed, 'paid' => $paid, 'remaining' => Money::sub($agreed, $paid)];
        }

        $salaryTotals = $this->salaries->totals($user);
        $weekStart = now()->startOfWeek()->toDateString();
        $weekEnd = now()->endOfWeek()->toDateString();

        return [
            'kpis' => [
                'active_projects' => $projects->filter(fn (Project $p): bool => $p->status !== Status::Done)->count(),
                'open_tasks' => array_sum($issueCounts) - $issueCounts[Status::Done->value],
                'critical_tasks' => $this->issues->openCriticalCount($user),
                'budget' => $budget,
                'pending_salary_requests' => $salaryTotals['pending_count'],
            ],
            'project_status_distribution' => $statusDistribution,
            'projects' => ProjectResource::collection($projects->take(8))->resolve($request),
            'activity' => ActivityResource::collection($this->activity->latestVisible($user, 12))->resolve($request),
            'workload' => $this->workload->forUser($user),
            'my_tasks_this_week' => IssueResource::collection(
                $this->issues->dueBetween($user, $weekStart, $weekEnd, onlyMine: true)
                    ->filter(fn ($issue): bool => $issue->status !== Status::Done)
                    ->values(),
            )->resolve($request),
            'team_size' => $this->users->activeCount(),
        ];
    }
}
