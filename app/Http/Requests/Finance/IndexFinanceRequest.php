<?php

namespace App\Http\Requests\Finance;

use App\DTOs\Filters\FinanceFilters;
use App\Enums\PaymentStatus;
use App\Http\Requests\ApiRequest;
use App\Http\Requests\Concerns\ParsesListParams;
use Illuminate\Validation\Rule;

/**
 * Shared by GET /salary-requests and GET /client-payments.
 */
class IndexFinanceRequest extends ApiRequest
{
    use ParsesListParams;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            ...$this->listRules(),
            'project_id' => ['sometimes', 'nullable', 'integer'],
            'status' => ['sometimes', 'nullable', Rule::enum(PaymentStatus::class)],
            'user_id' => ['sometimes', 'nullable', 'integer'],
            'from' => ['sometimes', 'nullable', 'date'],
            'to' => ['sometimes', 'nullable', 'date', 'after_or_equal:from'],
        ];
    }

    public function toFilters(): FinanceFilters
    {
        return new FinanceFilters(
            projectId: $this->intOrNull('project_id'),
            status: $this->stringOrNull('status'),
            userId: $this->intOrNull('user_id'),
            from: $this->stringOrNull('from'),
            to: $this->stringOrNull('to'),
            perPage: $this->perPageParam(25),
        );
    }
}
