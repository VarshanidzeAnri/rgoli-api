<?php

namespace App\Services;

use App\Models\Issue;
use App\Models\Project;
use App\Models\User;
use App\Repositories\Contracts\IssueRepositoryInterface;
use App\Repositories\Contracts\ProjectRepositoryInterface;

/**
 * Calendar events: task deadlines and project end milestones in a date range.
 */
class CalendarService
{
    public function __construct(
        private IssueRepositoryInterface $issues,
        private ProjectRepositoryInterface $projects,
    ) {}

    /**
     * @return list<array{type: string, id: int, date: string, title: string, key: string, status: string, priority: string|null, project_id: int}>
     */
    public function events(User $user, string $from, string $to): array
    {
        $events = [];

        if ($user->can('issues.view')) {
            foreach ($this->issues->dueBetween($user, $from, $to) as $issue) {
                /** @var Issue $issue */
                $events[] = [
                    'type' => 'task',
                    'id' => $issue->id,
                    'date' => (string) $issue->due_date?->toDateString(),
                    'title' => $issue->title,
                    'key' => $issue->key(),
                    'status' => $issue->status->value,
                    'priority' => $issue->priority->value,
                    'project_id' => $issue->project_id,
                ];
            }
        }

        foreach ($this->projects->endingBetween($user, $from, $to) as $project) {
            /** @var Project $project */
            $events[] = [
                'type' => 'milestone',
                'id' => $project->id,
                'date' => (string) $project->end_date?->toDateString(),
                'title' => $project->name,
                'key' => $project->key,
                'status' => $project->status->value,
                'priority' => null,
                'project_id' => $project->id,
            ];
        }

        usort($events, fn (array $a, array $b): int => [$a['date'], $a['type']] <=> [$b['date'], $b['type']]);

        return $events;
    }
}
