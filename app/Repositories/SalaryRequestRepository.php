<?php

namespace App\Repositories;

use App\DTOs\Filters\FinanceFilters;
use App\Enums\PaymentStatus;
use App\Models\SalaryRequest;
use App\Models\User;
use App\Repositories\Contracts\SalaryRequestRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * @extends BaseRepository<SalaryRequest>
 */
class SalaryRequestRepository extends BaseRepository implements SalaryRequestRepositoryInterface
{
    protected function model(): string
    {
        return SalaryRequest::class;
    }

    public function paginateVisible(User $user, FinanceFilters $filters): LengthAwarePaginator
    {
        return $this->query()->visibleTo($user)
            ->with(['project', 'user', 'decider'])
            ->when($filters->status !== null, fn (Builder $q) => $q->where('status', $filters->status))
            ->when($filters->projectId !== null, fn (Builder $q) => $q->where('project_id', $filters->projectId))
            ->when($filters->userId !== null, fn (Builder $q) => $q->where('user_id', $filters->userId))
            ->orderByRaw('case status when ? then 0 else 1 end', [PaymentStatus::Pending->value])
            ->latest()
            ->latest('id')
            ->paginate($filters->perPage)
            ->withQueryString();
    }

    public function lockForDecision(SalaryRequest $request): SalaryRequest
    {
        return $this->query()->whereKey($request->id)->lockForUpdate()->firstOrFail();
    }

    public function totals(User $user): array
    {
        $base = fn () => $this->query()->visibleTo($user);

        return [
            'pending_count' => $base()->where('status', PaymentStatus::Pending->value)->count(),
            'pending_amount' => number_format((float) $base()->where('status', PaymentStatus::Pending->value)->sum('amount'), 2, '.', ''),
            'approved_amount' => number_format((float) $base()->where('status', PaymentStatus::Approved->value)->sum('amount'), 2, '.', ''),
        ];
    }
}
