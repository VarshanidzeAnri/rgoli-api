<?php

namespace App\Services;

use App\Http\Resources\UserSummaryResource;
use App\Models\User;
use App\Repositories\Contracts\IssueRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;

/**
 * Team workload: open tasks and points per person. Load % is relative to a
 * capacity of CAPACITY_POINTS open points per person (above 60% shows a warning in the UI).
 */
class WorkloadService
{
    public const CAPACITY_POINTS = 20;

    public function __construct(
        private IssueRepositoryInterface $issues,
        private UserRepositoryInterface $users,
    ) {}

    /**
     * @return list<array{user: array<string, mixed>, open_count: int, open_points: int, load_percent: int}>
     */
    public function forUser(User $viewer): array
    {
        $rows = $this->issues->workload($viewer)->keyBy('user_id');
        $people = $this->users->options();

        $result = [];
        foreach ($people as $person) {
            $row = $rows->get($person->id);
            $points = $row->open_points ?? 0;
            $result[] = [
                'user' => (new UserSummaryResource($person))->resolve(),
                'open_count' => $row->open_count ?? 0,
                'open_points' => $points,
                'load_percent' => (int) min(100, round($points / self::CAPACITY_POINTS * 100)),
            ];
        }

        usort($result, fn (array $a, array $b): int => $b['open_points'] <=> $a['open_points']);

        return $result;
    }
}
