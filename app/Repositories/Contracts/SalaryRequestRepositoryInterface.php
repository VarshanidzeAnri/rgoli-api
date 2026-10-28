<?php

namespace App\Repositories\Contracts;

use App\DTOs\Filters\FinanceFilters;
use App\Models\SalaryRequest;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * @extends BaseRepositoryInterface<SalaryRequest>
 */
interface SalaryRequestRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Without finance.view only the user's own requests are returned.
     *
     * @return LengthAwarePaginator<int, SalaryRequest>
     */
    public function paginateVisible(User $user, FinanceFilters $filters): LengthAwarePaginator;

    /**
     * Re-reads the request with a row lock. Must run inside a transaction.
     */
    public function lockForDecision(SalaryRequest $request): SalaryRequest;

    /**
     * @return array{pending_count: int, pending_amount: string, approved_amount: string}
     */
    public function totals(User $user): array;
}
