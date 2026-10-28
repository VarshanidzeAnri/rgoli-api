<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Generic database notification. The text is stored as a translation key plus params,
 * so the SPA renders it in whatever language the viewer currently uses.
 */
class AppNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<string, scalar|null>  $params
     */
    public function __construct(
        public string $kind,
        public array $params = [],
        public ?string $link = null,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array{kind: string, params: array<string, scalar|null>, link: string|null, message: string}
     */
    public function toArray(object $notifiable): array
    {
        return [
            'kind' => $this->kind,
            'params' => $this->params,
            'link' => $this->link,
            'message' => __('messages.notifications.'.$this->kind, array_map(fn ($v) => (string) $v, $this->params)),
        ];
    }
}
