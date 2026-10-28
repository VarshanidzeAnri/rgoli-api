<?php

namespace App\Http\Requests\Project;

use App\Http\Requests\ApiRequest;

class UpdateLocationRequest extends ApiRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
            'address' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }
}
