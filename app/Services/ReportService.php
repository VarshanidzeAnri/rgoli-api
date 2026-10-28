<?php

namespace App\Services;

use App\Enums\Status;
use App\Models\Issue;
use App\Models\Project;
use App\Models\User;
use App\Repositories\Contracts\IssueRepositoryInterface;
use App\Repositories\Contracts\ProjectRepositoryInterface;
use App\Support\Money;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Reports page aggregates and CSV exports.
 */
class ReportService
{
    public function __construct(
        private ProjectRepositoryInterface $projects,
        private IssueRepositoryInterface $issues,
        private WorkloadService $workload,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function summary(User $user, ?string $from, ?string $to): array
    {
        $projects = $this->projects->allVisibleWithTotals($user);
        $counts = $this->issues->statusCounts($user, $from, $to);
        $totalTasks = array_sum($counts);
        $canFinance = $user->can('finance.view');

        $outstanding = Money::of(0);
        $planVsPaid = [];
        foreach ($projects as $project) {
            $agreed = $project->agreedPrice();
            $paid = $project->paidAmount();
            $outstanding = Money::add($outstanding, Money::sub($agreed, $paid));
            $planVsPaid[] = ['project' => $project->key, 'name' => $project->name, 'planned' => $agreed, 'paid' => $paid];
        }

        return [
            'task_completion_percent' => $totalTasks > 0 ? (int) round($counts[Status::Done->value] / $totalTasks * 100) : 0,
            'average_progress' => $projects->isEmpty() ? 0 : (int) round((float) $projects->avg('progress')),
            'outstanding_budget' => $canFinance ? $outstanding : null,
            'task_status' => $counts,
            'planned_vs_paid' => $canFinance ? $planVsPaid : [],
            'workload' => $this->workload->forUser($user),
        ];
    }

    public function exportCsv(User $user, string $type): StreamedResponse
    {
        $filename = 'rgoli-'.$type.'-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($user, $type): void {
            $out = fopen('php://output', 'wb');
            if ($out === false) {
                return;
            }
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel shows Georgian correctly

            if ($type === 'tasks') {
                fputcsv($out, ['key', 'title', 'project', 'status', 'priority', 'due_date', 'points', 'assignees']);
                $this->issues->dueBetween($user, '1970-01-01', '2999-12-31')
                    ->each(fn (Issue $i) => fputcsv($out, [
                        $i->key(), $i->title, $i->project->name, $i->status->label(), $i->priority->label(),
                        $i->due_date?->toDateString(), $i->points, $i->assignees->pluck('name')->implode(', '),
                    ]));
            } else {
                $finance = $user->can('finance.view');
                fputcsv($out, array_merge(['key', 'name', 'client', 'discipline', 'status', 'progress', 'start', 'end'], $finance ? ['agreed', 'paid', 'remaining'] : []));
                $this->projects->allVisibleWithTotals($user)->each(fn (Project $p) => fputcsv($out, array_merge(
                    [$p->key, $p->name, $p->client_name, $p->discipline->label(), $p->status->label(), $p->progress, $p->start_date?->toDateString(), $p->end_date?->toDateString()],
                    $finance ? [$p->agreedPrice(), $p->paidAmount(), $p->remainingAmount()] : [],
                )));
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
