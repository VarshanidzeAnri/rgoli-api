<?php

namespace App\Actions\Comment;

use App\Models\Comment;
use App\Repositories\Contracts\CommentRepositoryInterface;

class DeleteCommentAction
{
    public function __construct(private CommentRepositoryInterface $comments) {}

    public function handle(Comment $comment): void
    {
        $this->comments->delete($comment);
    }
}
