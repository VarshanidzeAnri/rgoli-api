<?php

namespace App\Http\Requests\Issue;

use App\Enums\Status;
use App\Http\Requests\ApiRequest;
use Illuminate\Validation\Rule;

/**
 * after_id: the issue the card was dropped directly below (null = top of the column).
 * Relative placement keeps ordering correct even when the board is filtered.
 */
class MoveIssueRequest extends ApiRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(Status::class)],
            'after_id' => ['present', 'nullable', 'integer', Rule::exists('issues', 'id')->whereNull('deleted_at')],
        ];
    }
}
