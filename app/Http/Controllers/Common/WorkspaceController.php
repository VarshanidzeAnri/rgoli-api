<?php

namespace App\Http\Controllers\Common;

use App\Http\Controllers\Controller;
use App\Http\Resources\ActivityResource;
use App\Http\Resources\NotificationResource;
use App\Http\Resources\UserSummaryResource;
use App\Repositories\Contracts\ActivityRepositoryInterface;
use App\Repositories\Contracts\NotificationRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\CalendarService;
use App\Services\DashboardService;
use App\Services\SearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group Workspace
 *
 * Cross-feature read endpoints: dashboard, calendar, search, activity, notifications, team pickers.
 */
class WorkspaceController extends Controller
{
    /**
     * Dashboard KPIs, projects, activity, workload and my tasks this week.
     */
    public function dashboard(Request $request, DashboardService $dashboard): JsonResponse
    {
        return response()->json(['data' => $dashboard->build($this->actor($request), $request)]);
    }

    /**
     * Calendar events (task deadlines, project end milestones).
     *
     * @queryParam from string required Y-m-d. Example: 2026-10-27
     * @queryParam to string required Y-m-d. Example: 2026-12-07
     */
    public function calendar(Request $request, CalendarService $calendar): JsonResponse
    {
        $range = $request->validate([
            'from' => ['required', 'date'],
            'to' => ['required', 'date', 'after_or_equal:from'],
        ]);

        return response()->json(['data' => $calendar->events($this->actor($request), (string) $range['from'], (string) $range['to'])]);
    }

    /**
     * Global search, grouped by projects / issues / files.
     *
     * @queryParam q string required At least 2 characters. Example: ბათუმი
     */
    public function search(Request $request, SearchService $search): JsonResponse
    {
        $request->validate(['q' => ['required', 'string', 'max:120']]);

        return response()->json(['data' => $search->search($this->actor($request), (string) $request->query('q'))]);
    }

    /**
     * Activity on visible projects.
     */
    public function activity(Request $request, ActivityRepositoryInterface $activity): AnonymousResourceCollection
    {
        return ActivityResource::collection($activity->paginateVisible($this->actor($request), $this->perPage($request, 30)));
    }

    /**
     * Active users for assignee and member pickers.
     */
    public function people(UserRepositoryInterface $users): AnonymousResourceCollection
    {
        return UserSummaryResource::collection($users->options());
    }

    /**
     * My notifications (unread count in meta).
     */
    public function notifications(Request $request, NotificationRepositoryInterface $notifications): AnonymousResourceCollection
    {
        $user = $this->actor($request);

        return NotificationResource::collection($notifications->paginateFor($user, $this->perPage($request, 20)))
            ->additional(['meta' => ['unread' => $notifications->unreadCount($user)]]);
    }

    /**
     * Mark one notification read.
     */
    public function readNotification(Request $request, string $id, NotificationRepositoryInterface $notifications): JsonResponse
    {
        $notifications->markRead($this->actor($request), $id);

        return response()->json(['data' => null]);
    }

    /**
     * Mark all notifications read.
     */
    public function readAllNotifications(Request $request, NotificationRepositoryInterface $notifications): JsonResponse
    {
        $notifications->markAllRead($this->actor($request));

        return response()->json(['data' => null]);
    }
}
