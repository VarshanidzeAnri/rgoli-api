<?php

namespace App\Http\Resources;

use App\Enums\FileType;
use App\Models\ProjectFile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ProjectFile
 */
class FileResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'original_name' => $this->original_name,
            'mime' => $this->mime,
            'size_bytes' => $this->size_bytes,
            'type' => $this->type->value,
            'project' => [
                'id' => $this->project->id,
                'key' => $this->project->key,
                'name' => $this->project->name,
            ],
            'uploader' => new UserSummaryResource($this->whenLoaded('uploader')),
            'download_url' => url('/api/v1/files/'.$this->id.'/download'),
            'preview_url' => $this->type === FileType::Image ? url('/api/v1/files/'.$this->id.'/preview') : null,
            'can_delete' => $request->user()?->can('delete', $this->resource) ?? false,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
