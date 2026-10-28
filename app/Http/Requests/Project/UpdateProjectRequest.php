<?php

namespace App\Http\Requests\Project;

use App\Enums\Discipline;
use App\Enums\Status;
use App\Http\Requests\ApiRequest;
use App\Models\Project;
use Illuminate\Validation\Rule;

class UpdateProjectRequest extends ApiRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $project = $this->route('project');
        $id = $project instanceof Project ? $project->id : null;

        return [
            'key' => ['sometimes', 'string', 'max:10', 'regex:/^[A-Z][A-Z0-9]{1,9}$/', Rule::unique('projects', 'key')->ignore($id)],
            'name' => ['sometimes', 'string', 'max:200'],
            'client_name' => ['sometimes', 'string', 'max:160'],
            'client_phone' => ['sometimes', 'nullable', 'string', 'max:40'],
            'client_email' => ['sometimes', 'nullable', 'email', 'max:191'],
            'address' => ['sometimes', 'nullable', 'string', 'max:255'],
            'discipline' => ['sometimes', Rule::enum(Discipline::class)],
            'status' => ['sometimes', Rule::enum(Status::class)],
            'budget' => ['sometimes', 'decimal:0,2', 'min:0', 'max:9999999999.99'],
            'discount_price' => ['sometimes', 'nullable', 'decimal:0,2', 'min:0', 'max:9999999999.99'],
            'progress' => ['sometimes', 'integer', 'between:0,100'],
            'start_date' => ['sometimes', 'nullable', 'date'],
            'end_date' => ['sometimes', 'nullable', 'date', 'after_or_equal:start_date'],
            'description' => ['sometimes', 'nullable', 'string', 'max:10000'],
        ];
    }
}
