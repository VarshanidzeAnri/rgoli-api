<?php

namespace App\Http\Resources;

use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Project
 */
class ProjectResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $request->user();
        $canSeeFinance = $user instanceof User && $user->can('finance.view');

        return [
            'id' => $this->id,
            'key' => $this->key,
            'name' => $this->name,
            'client_name' => $this->client_name,
            'client_phone' => $this->client_phone,
            'client_email' => $this->client_email,
            'address' => $this->address,
            'discipline' => $this->discipline->value,
            'status' => $this->status->value,
            'progress' => $this->progress,
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'description' => $this->description,
            'lat' => $this->lat !== null ? (float) $this->lat : null,
            'lng' => $this->lng !== null ? (float) $this->lng : null,
            'current_phase' => $this->whenLoaded('currentPhase', fn () => $this->currentPhase !== null
                ? ['id' => $this->currentPhase->id, 'name' => $this->currentPhase->name]
                : null),
            'phases' => PhaseResource::collection($this->whenLoaded('phases')),
            'members' => UserSummaryResource::collection($this->whenLoaded('members')),
            'tasks' => $this->when($this->issues_count !== null, fn () => [
                'total' => (int) $this->issues_count,
                'done' => (int) $this->done_issues_count,
            ]),
            'finance' => $canSeeFinance ? [
                'budget' => $this->budget,
                'discount_price' => $this->discount_price,
                'agreed_price' => $this->agreedPrice(),
                'paid' => $this->paidAmount(),
                'remaining' => $this->remainingAmount(),
            ] : null,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
