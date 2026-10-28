<?php

namespace App\Http\Resources;

use App\Enums\Status;
use App\Models\Issue;
use App\Models\Sprint;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Sprint
 */
class SprintResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'goal' => $this->goal,
            'start_date' => $this->start_date->toDateString(),
            'end_date' => $this->end_date->toDateString(),
            'state' => $this->state->value,
            'started_at' => $this->started_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'issues' => IssueResource::collection($this->whenLoaded('issues')),
            'stats' => $this->whenLoaded('issues', fn () => [
                'total' => $this->issues->count(),
                'done' => $this->issues->filter(fn (Issue $i): bool => $i->status === Status::Done)->count(),
                'points_total' => (int) $this->issues->sum('points'),
                'points_done' => (int) $this->issues->filter(fn (Issue $i): bool => $i->status === Status::Done)->sum('points'),
            ]),
        ];
    }
}
