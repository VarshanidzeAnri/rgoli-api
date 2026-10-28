<?php

namespace App\Actions\SalaryRequest;

use App\Enums\PaymentStatus;
use App\Events\SalaryRequestDecided;
use App\Models\SalaryRequest;
use App\Models\User;
use App\Repositories\Contracts\SalaryRequestRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

/**
 * Approves or rejects a pending request and records who decided and when.
 * The row is locked so two approvers can't decide the same request twice.
 */
class DecideSalaryRequestAction
{
    public function __construct(private SalaryRequestRepositoryInterface $requests) {}

    public function handle(User $decider, SalaryRequest $request, PaymentStatus $decision, ?string $note = null): SalaryRequest
    {
        if ($decision === PaymentStatus::Pending) {
            throw new InvalidArgumentException('A decision must be approved or rejected.');
        }

        DB::transaction(function () use ($decider, $request, $decision, $note): void {
            if (! $this->requests->lockForDecision($request)->isPending()) {
                throw ValidationException::withMessages(['status' => __('messages.salary_already_decided')]);
            }

            $this->requests->update($request, [
                'status' => $decision,
                'decided_by' => $decider->id,
                'decided_at' => now(),
                'decision_note' => $note,
            ]);
        });

        SalaryRequestDecided::dispatch($request, $decider);

        return $request->load(['project', 'user', 'decider']);
    }
}
