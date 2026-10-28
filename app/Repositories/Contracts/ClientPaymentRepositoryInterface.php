<?php

namespace App\Repositories\Contracts;

use App\DTOs\Filters\FinanceFilters;
use App\Models\ClientPayment;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * @extends BaseRepositoryInterface<ClientPayment>
 */
interface ClientPaymentRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Payments on projects visible to the user.
     *
     * @return LengthAwarePaginator<int, ClientPayment>
     */
    public function paginateVisible(User $user, FinanceFilters $filters): LengthAwarePaginator;

    /**
     * Sum of payments on visible projects inside an optional date range.
     */
    public function totalVisible(User $user, ?string $from = null, ?string $to = null): string;
}
