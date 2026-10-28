<?php

namespace App\Actions\SalaryRequest;

use App\Models\SalaryRequest;
use App\Repositories\Contracts\SalaryRequestRepositoryInterface;

/**
 * Edits a pending request (the policy limits this to the author).
 */
class UpdateSalaryRequestAction
{
    public function __construct(private SalaryRequestRepositoryInterface $requests) {}

    /**
     * @param  array{title?: string, amount?: string|int|float, note?: string|null}  $data
     */
    public function handle(SalaryRequest $request, array $data): SalaryRequest
    {
        return $this->requests->update($request, $data)->load(['project', 'user', 'decider']);
    }
}
