<?php

namespace App\Http\Requests\Issue;

use App\Http\Requests\ApiRequest;

class CommentRequest extends ApiRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:5000'],
        ];
    }
}
