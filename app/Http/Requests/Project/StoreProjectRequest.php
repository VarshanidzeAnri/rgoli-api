<?php

namespace App\Http\Requests\Project;

use App\Enums\Discipline;
use App\Enums\Status;
use App\Http\Requests\ApiRequest;
use Illuminate\Validation\Rule;

class StoreProjectRequest extends ApiRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'key' => ['required', 'string', 'max:10', 'regex:/^[A-Z][A-Z0-9]{1,9}$/', Rule::unique('projects', 'key')],
            'name' => ['required', 'string', 'max:200'],
            'client_name' => ['required', 'string', 'max:160'],
            'client_phone' => ['nullable', 'string', 'max:40'],
            'client_email' => ['nullable', 'email', 'max:191'],
            'address' => ['nullable', 'string', 'max:255'],
            'discipline' => ['required', Rule::enum(Discipline::class)],
            'status' => ['required', Rule::enum(Status::class)],
            'budget' => ['required', 'decimal:0,2', 'min:0', 'max:9999999999.99'],
            'discount_price' => ['nullable', 'decimal:0,2', 'min:0', 'max:9999999999.99'],
            'lat' => ['nullable', 'numeric', 'between:-90,90', 'required_with:lng'],
            'lng' => ['nullable', 'numeric', 'between:-180,180', 'required_with:lat'],
            'progress' => ['nullable', 'integer', 'between:0,100'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'description' => ['nullable', 'string', 'max:10000'],
            'member_ids' => ['sometimes', 'array'],
            'member_ids.*' => ['integer', Rule::exists('users', 'id')],
        ];
    }
}
