<?php

namespace App\Actions\SalaryRequest;

use App\Enums\PaymentStatus;
use App\Events\SalaryRequestSubmitted;
use App\Models\SalaryRequest;
use App\Models\User;
use App\Repositories\Contracts\SalaryRequestRepositoryInterface;

class SubmitSalaryRequestAction
{
    public function __construct(private SalaryRequestRepositoryInterface $requests) {}

    /**
     * @param  array{project_id: int, title: string, amount: string|int|float, note?: string|null}  $data
     */
    public function handle(User $actor, array $data): SalaryRequest
    {
        $request = $this->requests->create([
            'project_id' => $data['project_id'],
            'user_id' => $actor->id,
            'title' => $data['title'],
            'amount' => $data['amount'],
            'note' => $data['note'] ?? null,
            'status' => PaymentStatus::Pending,
        ]);

        SalaryRequestSubmitted::dispatch($request);

        return $request->load(['project', 'user', 'decider']);
    }
}
