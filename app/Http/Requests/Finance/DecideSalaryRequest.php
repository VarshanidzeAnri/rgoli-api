<?php

namespace App\Http\Requests\Finance;

use App\Http\Requests\ApiRequest;

class DecideSalaryRequest extends ApiRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'note' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
