<?php

namespace App\Http\Requests\Sprint;

use App\Http\Requests\ApiRequest;
use Illuminate\Validation\Rule;

class SprintIssuesRequest extends ApiRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'issue_ids' => ['required', 'array', 'min:1'],
            'issue_ids.*' => ['integer', Rule::exists('issues', 'id')->whereNull('deleted_at')],
        ];
    }
}
