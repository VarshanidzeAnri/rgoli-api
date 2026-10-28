<?php

namespace App\Http\Requests\Finance;

use App\Enums\PaymentMethod;
use App\Http\Requests\ApiRequest;
use Illuminate\Validation\Rule;

class ClientPaymentRequest extends ApiRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $required = $this->isMethod('POST') ? 'required' : 'sometimes';

        return [
            'amount' => [$required, 'decimal:0,2', 'gt:0', 'max:9999999999.99'],
            'paid_at' => [$required, 'date', 'before_or_equal:today'],
            'method' => [$required, Rule::enum(PaymentMethod::class)],
            'note' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ];
    }
}
