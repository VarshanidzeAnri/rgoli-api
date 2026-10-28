<?php

namespace App\Http\Resources;

use App\Models\SalaryRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin SalaryRequest
 */
class SalaryRequestResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $viewer = $request->user();

        return [
            'id' => $this->id,
            'project' => [
                'id' => $this->project->id,
                'key' => $this->project->key,
                'name' => $this->project->name,
            ],
            'user' => new UserSummaryResource($this->whenLoaded('user')),
            'title' => $this->title,
            'amount' => $this->amount,
            'note' => $this->note,
            'status' => $this->status->value,
            'decider' => new UserSummaryResource($this->whenLoaded('decider')),
            'decided_at' => $this->decided_at?->toIso8601String(),
            'decision_note' => $this->decision_note,
            'can' => [
                'decide' => $viewer?->can('decide', $this->resource) ?? false,
                'update' => $viewer?->can('update', $this->resource) ?? false,
            ],
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
