<?php

namespace App\DTOs\Filters;

final readonly class UserFilters
{
    public function __construct(
        public ?string $search = null,
        public ?int $roleId = null,
        public ?bool $active = null,
        public int $perPage = 25,
    ) {}
}
