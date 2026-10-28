<?php

namespace App\Actions\Comment;

use App\Models\Comment;
use App\Repositories\Contracts\CommentRepositoryInterface;

class UpdateCommentAction
{
    public function __construct(private CommentRepositoryInterface $comments) {}

    public function handle(Comment $comment, string $body): Comment
    {
        return $this->comments->update($comment, ['body' => $body])->load('user');
    }
}
