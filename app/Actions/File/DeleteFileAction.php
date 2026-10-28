<?php

namespace App\Actions\File;

use App\Models\ProjectFile;
use App\Repositories\Contracts\FileRepositoryInterface;

/**
 * Soft-deletes the record. The stored blob is kept so a soft delete stays reversible;
 * a scheduled prune can remove blobs of records deleted long ago.
 */
class DeleteFileAction
{
    public function __construct(private FileRepositoryInterface $files) {}

    public function handle(ProjectFile $file): void
    {
        $this->files->delete($file);
    }
}
