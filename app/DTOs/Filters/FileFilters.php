<?php

namespace App\DTOs\Filters;

final readonly class FileFilters
{
    public function __construct(
        public ?int $projectId = null,
        public ?string $type = null,
        public ?string $search = null,
        public int $perPage = 24,
    ) {}
}
