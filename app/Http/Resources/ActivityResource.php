<?php

namespace App\Http\Resources;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ActivityLog
 */
class ActivityResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'event' => $this->event,
            'subject_type' => $this->subject_type,
            'subject_id' => $this->subject_id,
            'meta' => $this->meta ?? new \stdClass,
            'user' => new UserSummaryResource($this->whenLoaded('user')),
            'project' => $this->whenLoaded('project', fn () => $this->project !== null
                ? ['id' => $this->project->id, 'key' => $this->project->key, 'name' => $this->project->name]
                : null),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
