<?php

namespace App\Actions\ClientPayment;

use App\Models\ClientPayment;
use App\Models\Project;
use App\Models\User;
use App\Repositories\Contracts\ClientPaymentRepositoryInterface;
use Illuminate\Support\Facades\Log;

/**
 * Records a new client payment for a project, or edits an existing one.
 */
class SaveClientPaymentAction
{
    public function __construct(private ClientPaymentRepositoryInterface $payments) {}

    /**
     * @param  array{amount?: string|int|float, paid_at?: string, method?: string, note?: string|null}  $data
     */
    public function handle(User $actor, array $data, ?Project $project = null, ?ClientPayment $payment = null): ClientPayment
    {
        if ($payment === null) {
            if ($project === null) {
                throw new \InvalidArgumentException('A project is required for a new payment.');
            }
            $payment = $this->payments->create([...$data, 'project_id' => $project->id, 'recorded_by' => $actor->id]);
            Log::channel('finance')->info('client_payment.created', ['id' => $payment->id, 'project_id' => $project->id, 'amount' => $payment->amount, 'by' => $actor->id]);
        } else {
            $this->payments->update($payment, $data);
            Log::channel('finance')->info('client_payment.updated', ['id' => $payment->id, 'by' => $actor->id, 'fields' => array_keys($data)]);
        }

        return $payment->load(['project', 'recorder']);
    }
}
