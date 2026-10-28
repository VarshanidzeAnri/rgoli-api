<?php

namespace App\Http\Requests\User;

use App\DTOs\Filters\UserFilters;
use App\Http\Requests\ApiRequest;
use App\Http\Requests\Concerns\ParsesListParams;

class IndexUserRequest extends ApiRequest
{
    use ParsesListParams;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            ...$this->listRules(),
            'role_id' => ['sometimes', 'nullable', 'integer'],
            'active' => ['sometimes', 'nullable', 'boolean'],
        ];
    }

    public function toFilters(): UserFilters
    {
        $active = $this->query('active');

        return new UserFilters(
            search: $this->stringOrNull('search'),
            roleId: $this->intOrNull('role_id'),
            active: $active === null || $active === '' ? null : filter_var($active, FILTER_VALIDATE_BOOLEAN),
            perPage: $this->perPageParam(25),
        );
    }
}
