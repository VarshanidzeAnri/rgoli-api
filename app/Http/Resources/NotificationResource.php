<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Notifications\DatabaseNotification;

/**
 * @mixin DatabaseNotification
 */
class NotificationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var array{kind?: string, params?: array<string, scalar|null>, link?: string|null} $data */
        $data = $this->data;
        $params = array_map(fn ($v): string => (string) $v, $data['params'] ?? []);

        return [
            'id' => $this->id,
            'kind' => $data['kind'] ?? 'generic',
            'params' => $data['params'] ?? new \stdClass,
            'message' => __('messages.notifications.'.($data['kind'] ?? 'generic'), $params),
            'link' => $data['link'] ?? null,
            'read_at' => $this->read_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
