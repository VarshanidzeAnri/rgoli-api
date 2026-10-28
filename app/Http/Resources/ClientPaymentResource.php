<?php

namespace App\Http\Resources;

use App\Models\ClientPayment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ClientPayment
 */
class ClientPaymentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'project' => $this->whenLoaded('project', fn () => [
                'id' => $this->project->id,
                'key' => $this->project->key,
                'name' => $this->project->name,
            ]),
            'project_id' => $this->project_id,
            'amount' => $this->amount,
            'paid_at' => $this->paid_at->toDateString(),
            'method' => $this->method->value,
            'note' => $this->note,
            'recorder' => new UserSummaryResource($this->whenLoaded('recorder')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
