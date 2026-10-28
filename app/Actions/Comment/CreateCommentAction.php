<?php

namespace App\Actions\Comment;

use App\Models\Comment;
use App\Models\Issue;
use App\Models\User;
use App\Repositories\Contracts\CommentRepositoryInterface;

class CreateCommentAction
{
    public function __construct(private CommentRepositoryInterface $comments) {}

    public function handle(User $author, Issue $issue, string $body): Comment
    {
        $comment = $this->comments->create([
            'issue_id' => $issue->id,
            'user_id' => $author->id,
            'body' => $body,
        ]);

        return $comment->load('user');
    }
}
