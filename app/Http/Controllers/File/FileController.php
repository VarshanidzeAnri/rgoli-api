<?php

namespace App\Http\Controllers\File;

use App\Actions\File\DeleteFileAction;
use App\Actions\File\UploadFilesAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\File\IndexFileRequest;
use App\Http\Requests\File\UploadFilesRequest;
use App\Http\Resources\FileResource;
use App\Models\ProjectFile;
use App\Repositories\Contracts\FileRepositoryInterface;
use App\Repositories\Contracts\ProjectRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @group Files
 */
class FileController extends Controller
{
    /**
     * Files on visible projects.
     *
     * @queryParam project_id integer
     * @queryParam type string pdf, dwg, image, doc, zip, other.
     * @queryParam search string
     */
    public function index(IndexFileRequest $request, FileRepositoryInterface $files): AnonymousResourceCollection
    {
        $this->authorize('viewAny', ProjectFile::class);

        return FileResource::collection($files->paginateVisible($this->actor($request), $request->toFilters()));
    }

    /**
     * Upload one or more files (multipart: project_id, files[]). Rate-limited.
     */
    public function store(UploadFilesRequest $request, ProjectRepositoryInterface $projects, UploadFilesAction $action): JsonResponse
    {
        $project = $projects->findOrFail((int) $request->validated('project_id'));
        $this->authorize('uploadFile', $project);

        /** @var list<UploadedFile> $uploads */
        $uploads = array_values(array_filter((array) $request->file('files'), fn ($f): bool => $f instanceof UploadedFile));
        $stored = $action->handle($this->actor($request), $project, $uploads);

        foreach ($stored as $file) {
            $file->load(['project', 'uploader']);
        }

        return FileResource::collection($stored)
            ->additional(['meta' => ['message' => __('messages.uploaded')]])
            ->response()->setStatusCode(201);
    }

    /**
     * Download (attachment). Authorized per request; files are never public.
     */
    public function download(ProjectFile $file): StreamedResponse
    {
        $this->authorize('view', $file);

        return Storage::disk($file->disk)->download($file->path, $file->original_name);
    }

    /**
     * Inline preview for images.
     */
    public function preview(ProjectFile $file): StreamedResponse
    {
        $this->authorize('view', $file);

        return Storage::disk($file->disk)->response($file->path, $file->original_name, [
            'Content-Type' => $file->mime,
            'Cache-Control' => 'private, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * Delete (own with files.delete_own, any with files.delete_any).
     */
    public function destroy(ProjectFile $file, DeleteFileAction $action): JsonResponse
    {
        $this->authorize('delete', $file);
        $action->handle($file);

        return $this->message('messages.deleted');
    }
}
