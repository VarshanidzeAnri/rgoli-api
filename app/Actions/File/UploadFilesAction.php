<?php

namespace App\Actions\File;

use App\Enums\FileType;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\User;
use App\Repositories\Contracts\FileRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * Stores uploads on the private disk under random names. The client filename is kept only
 * as display text and the MIME type is detected on the server. All-or-nothing per request.
 */
class UploadFilesAction
{
    public function __construct(private FileRepositoryInterface $files) {}

    /**
     * @param  list<UploadedFile>  $uploads
     * @return list<ProjectFile>
     */
    public function handle(User $actor, Project $project, array $uploads): array
    {
        $disk = (string) config('rgoli.uploads.disk');
        $storedPaths = [];
        $records = [];

        try {
            foreach ($uploads as $upload) {
                $extension = strtolower($upload->getClientOriginalExtension());
                $mime = $upload->getMimeType() ?? 'application/octet-stream';
                $name = Str::uuid()->toString().($extension !== '' ? '.'.$extension : '');

                $path = $upload->storeAs('projects/'.$project->id, $name, ['disk' => $disk]);
                if ($path === false) {
                    throw new RuntimeException('Upload could not be stored.');
                }
                $storedPaths[] = $path;

                $records[] = $this->files->create([
                    'project_id' => $project->id,
                    'uploaded_by' => $actor->id,
                    'original_name' => $this->displayName($upload->getClientOriginalName()),
                    'disk' => $disk,
                    'path' => $path,
                    'mime' => $mime,
                    'size_bytes' => (int) $upload->getSize(),
                    'type' => FileType::detect($mime, $extension),
                ]);
            }
        } catch (Throwable $e) {
            Storage::disk($disk)->delete($storedPaths);
            foreach ($records as $record) {
                $record->forceDelete();
            }

            throw $e;
        }

        return $records;
    }

    /**
     * Keeps unicode (Georgian) letters, strips path parts and control characters.
     */
    private function displayName(string $name): string
    {
        $name = basename(str_replace('\\', '/', $name));
        $name = (string) preg_replace('/[\x00-\x1F\x7F]/u', '', $name);

        return mb_substr($name !== '' ? $name : 'file', 0, 255);
    }
}
