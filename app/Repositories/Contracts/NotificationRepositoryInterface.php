<?php

namespace App\Repositories\Contracts;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Notifications\DatabaseNotification;

interface NotificationRepositoryInterface
{
    /**
     * @return LengthAwarePaginator<int, DatabaseNotification>
     */
    public function paginateFor(User $user, int $perPage): LengthAwarePaginator;

    public function unreadCount(User $user): int;

    public function markRead(User $user, string $id): void;

    public function markAllRead(User $user): void;
}
