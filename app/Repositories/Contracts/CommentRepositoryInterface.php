<?php

namespace App\Repositories\Contracts;

use App\Models\Comment;
use App\Models\Issue;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * @extends BaseRepositoryInterface<Comment>
 */
interface CommentRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * @return LengthAwarePaginator<int, Comment>
     */
    public function paginateForIssue(Issue $issue, int $perPage): LengthAwarePaginator;
}
