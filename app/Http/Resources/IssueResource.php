<?php

namespace App\Http\Resources;

use App\Enums\SprintState;
use App\Models\Issue;
use App\Models\Sprint;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Issue
 */
class IssueResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'key' => $this->key(),
            'number' => $this->number,
            'project' => [
                'id' => $this->project->id,
                'key' => $this->project->key,
                'name' => $this->project->name,
            ],
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status->value,
            'priority' => $this->priority->value,
            'due_date' => $this->due_date?->toDateString(),
            'points' => $this->points,
            'position' => $this->position,
            'assignees' => UserSummaryResource::collection($this->whenLoaded('assignees')),
            'sprint_id' => $this->whenLoaded('sprints', function (): ?int {
                $open = $this->sprints->first(fn (Sprint $s): bool => $s->state !== SprintState::Completed);

                return $open?->id;
            }),
            'comments_count' => $this->whenCounted('comments'),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
