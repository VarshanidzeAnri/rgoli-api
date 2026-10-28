<?php

namespace App\Repositories;

use App\DTOs\Filters\FinanceFilters;
use App\Models\ClientPayment;
use App\Models\User;
use App\Repositories\Contracts\ClientPaymentRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * @extends BaseRepository<ClientPayment>
 */
class ClientPaymentRepository extends BaseRepository implements ClientPaymentRepositoryInterface
{
    protected function model(): string
    {
        return ClientPayment::class;
    }

    public function paginateVisible(User $user, FinanceFilters $filters): LengthAwarePaginator
    {
        return $this->visible($user, $filters->from, $filters->to)
            ->when($filters->projectId !== null, fn (Builder $q) => $q->where('project_id', $filters->projectId))
            ->with(['project', 'recorder'])
            ->orderByDesc('paid_at')
            ->orderByDesc('id')
            ->paginate($filters->perPage)
            ->withQueryString();
    }

    public function totalVisible(User $user, ?string $from = null, ?string $to = null): string
    {
        $sum = $this->visible($user, $from, $to)->sum('amount');

        return number_format((float) $sum, 2, '.', '');
    }

    /**
     * @return Builder<ClientPayment>
     */
    private function visible(User $user, ?string $from, ?string $to): Builder
    {
        return $this->query()
            ->whereHas('project', fn (Builder $p) => $p->visibleTo($user))
            ->when($from !== null, fn (Builder $q) => $q->whereDate('paid_at', '>=', (string) $from))
            ->when($to !== null, fn (Builder $q) => $q->whereDate('paid_at', '<=', (string) $to));
    }
}
