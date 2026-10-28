<?php

namespace App\Http\Controllers\System;

use App\Actions\System\ResetDemoDataAction;
use App\Http\Controllers\Controller;
use App\Jobs\ExportSystemDataJob;
use App\Models\ActivityLog;
use App\Models\ClientPayment;
use App\Models\Issue;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\SalaryRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @group Admin · System
 *
 * Gated by permission middleware on the routes.
 */
class SystemController extends Controller
{
    /**
     * Record counts for the admin panel.
     */
    public function stats(): JsonResponse
    {
        return response()->json(['data' => [
            'users' => User::query()->count(),
            'active_users' => User::query()->active()->count(),
            'projects' => Project::query()->count(),
            'issues' => Issue::query()->count(),
            'files' => ProjectFile::query()->count(),
            'storage_bytes' => (int) ProjectFile::query()->sum('size_bytes'),
            'client_payments' => ClientPayment::query()->count(),
            'salary_requests' => SalaryRequest::query()->count(),
            'activity_logs' => ActivityLog::query()->count(),
            'demo' => (bool) config('rgoli.demo'),
        ]]);
    }

    /**
     * Queue a full JSON export; a notification with a signed link arrives when it's ready.
     */
    public function export(Request $request): JsonResponse
    {
        ExportSystemDataJob::dispatch($this->actor($request)->id);

        return $this->message('messages.export_queued', 202);
    }

    /**
     * Signed, expiring download of an export file.
     *
     * @unauthenticated
     */
    public function downloadExport(string $file): StreamedResponse
    {
        abort_unless(preg_match('/^rgoli-export-\d{8}-\d{6}\.json$/', $file) === 1, 404);
        abort_unless(Storage::disk('local')->exists('exports/'.$file), 404);

        return Storage::disk('local')->download('exports/'.$file);
    }

    /**
     * Wipe and re-seed demo data (APP_DEMO=true only).
     */
    public function resetDemo(Request $request, ResetDemoDataAction $action): JsonResponse
    {
        abort_unless((bool) config('rgoli.demo'), 404);
        $action->handle($this->actor($request)->id);

        return $this->message('messages.demo_reset');
    }
}
