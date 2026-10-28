<?php

namespace App\Http\Requests\User;

use App\Enums\Discipline;
use App\Http\Requests\ApiRequest;
use App\Models\User;
use App\Support\PermissionCatalog;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserRequest extends ApiRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $creating = $this->isMethod('POST');
        $user = $this->route('user');
        $id = $user instanceof User ? $user->id : null;
        $required = $creating ? 'required' : 'sometimes';

        return [
            'name' => [$required, 'string', 'max:120'],
            'email' => [$required, 'email', 'max:191', Rule::unique('users', 'email')->ignore($id)],
            'password' => [$creating ? 'required' : 'sometimes', Password::min(8)],
            'job_title' => ['sometimes', 'nullable', 'string', 'max:120'],
            'is_active' => ['sometimes', 'boolean'],
            'role_id' => [$required, 'integer', Rule::exists('roles', 'id')],
            'direct_permissions' => ['sometimes', 'array'],
            'direct_permissions.*' => ['string', Rule::in(PermissionCatalog::all())],
            'disciplines' => ['sometimes', 'array'],
            'disciplines.*' => [Rule::enum(Discipline::class)],
        ];
    }
}
