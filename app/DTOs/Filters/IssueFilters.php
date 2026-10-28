<?php

namespace App\DTOs\Filters;

final readonly class IssueFilters
{
    /**
     * @param  list<string>  $statuses
     */
    public function __construct(
        public ?int $projectId = null,
        public array $statuses = [],
        public ?string $priority = null,
        public ?int $assigneeId = null,
        public bool $onlyMine = false,
        public ?int $sprintId = null,
        public bool $backlog = false,
        public ?string $dueFrom = null,
        public ?string $dueTo = null,
        public ?string $search = null,
        public string $sort = 'position',
        public string $direction = 'asc',
        public int $perPage = 50,
    ) {}
}
