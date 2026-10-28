<?php

namespace App\Actions\ClientPayment;

use App\Models\ClientPayment;
use App\Models\User;
use App\Repositories\Contracts\ClientPaymentRepositoryInterface;
use Illuminate\Support\Facades\Log;

class DeleteClientPaymentAction
{
    public function __construct(private ClientPaymentRepositoryInterface $payments) {}

    public function handle(User $actor, ClientPayment $payment): void
    {
        $this->payments->delete($payment);
        Log::channel('finance')->info('client_payment.deleted', ['id' => $payment->id, 'by' => $actor->id]);
    }
}
