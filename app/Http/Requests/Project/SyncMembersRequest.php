<?php

namespace App\Http\Requests\Project;

use App\Http\Requests\ApiRequest;
use Illuminate\Validation\Rule;

class SyncMembersRequest extends ApiRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'member_ids' => ['present', 'array'],
            'member_ids.*' => ['integer', Rule::exists('users', 'id')],
        ];
    }
}
