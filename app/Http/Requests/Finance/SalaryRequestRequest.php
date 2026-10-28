<?php

namespace App\Http\Requests\Finance;

use App\Http\Requests\ApiRequest;
use Illuminate\Validation\Rule;

class SalaryRequestRequest extends ApiRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $required = $this->isMethod('POST') ? 'required' : 'sometimes';

        return [
            'project_id' => [$this->isMethod('POST') ? 'required' : 'prohibited', 'integer', Rule::exists('projects', 'id')->whereNull('deleted_at')],
            'title' => [$required, 'string', 'max:200'],
            'amount' => [$required, 'decimal:0,2', 'gt:0', 'max:9999999999.99'],
            'note' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ];
    }
}
