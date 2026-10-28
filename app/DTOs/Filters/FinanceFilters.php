<?php

namespace App\DTOs\Filters;

/**
 * Filters shared by salary request and client payment lists.
 */
final readonly class FinanceFilters
{
    public function __construct(
        public ?int $projectId = null,
        public ?string $status = null,
        public ?int $userId = null,
        public ?string $from = null,
        public ?string $to = null,
        public int $perPage = 25,
    ) {}
}
