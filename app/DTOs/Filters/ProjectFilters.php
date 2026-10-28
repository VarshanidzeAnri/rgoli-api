<?php

namespace App\DTOs\Filters;

final readonly class ProjectFilters
{
    /**
     * @param  list<string>  $statuses
     */
    public function __construct(
        public array $statuses = [],
        public ?string $discipline = null,
        public ?string $search = null,
        public bool $onlyMine = false,
        public string $sort = 'created_at',
        public string $direction = 'desc',
        public int $perPage = 25,
    ) {}
}
