<?php

namespace App\Repositories;

use App\Models\Comment;
use App\Models\Issue;
use App\Repositories\Contracts\CommentRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * @extends BaseRepository<Comment>
 */
class CommentRepository extends BaseRepository implements CommentRepositoryInterface
{
    protected function model(): string
    {
        return Comment::class;
    }

    public function paginateForIssue(Issue $issue, int $perPage): LengthAwarePaginator
    {
        return $issue->comments()->with('user')->paginate($perPage);
    }
}
