<?php

namespace App\Http\Controllers\Issue;

use App\Actions\Comment\CreateCommentAction;
use App\Actions\Comment\DeleteCommentAction;
use App\Actions\Comment\UpdateCommentAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Issue\CommentRequest;
use App\Http\Resources\CommentResource;
use App\Models\Comment;
use App\Models\Issue;
use App\Repositories\Contracts\CommentRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group Issues
 */
class CommentController extends Controller
{
    /**
     * Comments of an issue, newest first.
     */
    public function index(Request $request, Issue $issue, CommentRepositoryInterface $comments): AnonymousResourceCollection
    {
        $this->authorize('view', $issue);

        return CommentResource::collection($comments->paginateForIssue($issue, $this->perPage($request, 50)));
    }

    /**
     * Add a comment (issues.comment).
     */
    public function store(CommentRequest $request, Issue $issue, CreateCommentAction $action): JsonResponse
    {
        $this->authorize('comment', $issue);

        return (new CommentResource($action->handle($this->actor($request), $issue, (string) $request->validated('body'))))
            ->additional(['meta' => ['message' => __('messages.saved')]])
            ->response()->setStatusCode(201);
    }

    /**
     * Edit own comment.
     */
    public function update(CommentRequest $request, Comment $comment, UpdateCommentAction $action): CommentResource
    {
        $this->authorize('update', $comment);

        return (new CommentResource($action->handle($comment, (string) $request->validated('body'))))
            ->additional(['meta' => ['message' => __('messages.saved')]]);
    }

    /**
     * Delete own comment.
     */
    public function destroy(Comment $comment, DeleteCommentAction $action): JsonResponse
    {
        $this->authorize('delete', $comment);
        $action->handle($comment);

        return $this->message('messages.deleted');
    }
}
