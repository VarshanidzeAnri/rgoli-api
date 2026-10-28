<?php

namespace App\Http\Requests\Role;

use App\Http\Requests\ApiRequest;
use App\Models\Role;
use App\Support\PermissionCatalog;
use Illuminate\Validation\Rule;

class RoleRequest extends ApiRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $role = $this->route('role');
        $id = $role instanceof Role ? $role->id : null;

        return [
            'name' => [$this->isMethod('POST') ? 'required' : 'sometimes', 'string', 'max:80', Rule::unique('roles', 'name')->ignore($id)],
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => ['string', Rule::in(PermissionCatalog::all())],
        ];
    }
}
