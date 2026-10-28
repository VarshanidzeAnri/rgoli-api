<?php

namespace App\Repositories;

use App\DTOs\Filters\FileFilters;
use App\Models\ProjectFile;
use App\Models\User;
use App\Repositories\Contracts\FileRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * @extends BaseRepository<ProjectFile>
 */
class FileRepository extends BaseRepository implements FileRepositoryInterface
{
    protected function model(): string
    {
        return ProjectFile::class;
    }

    public function paginateVisible(User $user, FileFilters $filters): LengthAwarePaginator
    {
        $query = $this->query()->visibleTo($user)->with(['project', 'uploader'])->latest()->latest('id');

        if ($filters->projectId !== null) {
            $query->where('project_id', $filters->projectId);
        }
        if ($filters->type !== null) {
            $query->where('type', $filters->type);
        }
        if ($filters->search !== null && $filters->search !== '') {
            $query->where('original_name', 'like', $this->like($filters->search));
        }

        return $query->paginate($filters->perPage)->withQueryString();
    }

    public function search(User $user, string $term, int $limit): Collection
    {
        return $this->query()->visibleTo($user)
            ->with('project')
            ->where('original_name', 'like', $this->like($term))
            ->limit($limit)
            ->get();
    }
}
