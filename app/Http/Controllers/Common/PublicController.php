<?php

namespace App\Http\Controllers\Common;

use App\Enums\Status;
use App\Http\Controllers\Controller;
use App\Models\Issue;
use App\Models\Project;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

/**
 * @group Public
 *
 * @unauthenticated
 */
class PublicController extends Controller
{
    /**
     * App config: demo flag, upload limits, version.
     */
    public function config(): JsonResponse
    {
        return response()->json(['data' => [
            'version' => config('rgoli.version'),
            'demo' => (bool) config('rgoli.demo'),
            'uploads' => [
                'max_kb' => (int) config('rgoli.uploads.max_kb'),
                'max_files' => (int) config('rgoli.uploads.max_files'),
                'extensions' => config('rgoli.uploads.extensions'),
            ],
        ]]);
    }

    /**
     * Three aggregate numbers for the login page (cached for 5 minutes).
     */
    public function stats(UserRepositoryInterface $users): JsonResponse
    {
        $stats = Cache::remember('public-stats', 300, fn (): array => [
            'active_projects' => Project::query()->where('status', '!=', Status::Done->value)->count(),
            'open_tasks' => Issue::query()->where('status', '!=', Status::Done->value)->count(),
            'team_members' => $users->activeCount(),
        ]);

        return response()->json(['data' => $stats]);
    }

    /**
     * Demo accounts for quick login (only when APP_DEMO=true).
     */
    public function demoAccounts(): JsonResponse
    {
        abort_unless((bool) config('rgoli.demo'), 404);

        return response()->json(['data' => config('rgoli.demo_accounts')]);
    }
}
