<?php

namespace App\Repositories\Contracts;

use App\DTOs\Filters\FileFilters;
use App\Models\ProjectFile;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * @extends BaseRepositoryInterface<ProjectFile>
 */
interface FileRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * @return LengthAwarePaginator<int, ProjectFile>
     */
    public function paginateVisible(User $user, FileFilters $filters): LengthAwarePaginator;

    /**
     * @return Collection<int, ProjectFile>
     */
    public function search(User $user, string $term, int $limit): Collection;
}
