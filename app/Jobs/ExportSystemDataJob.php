<?php

namespace App\Jobs;

use App\Models\ActivityLog;
use App\Models\ClientPayment;
use App\Models\Comment;
use App\Models\Issue;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\ProjectPhase;
use App\Models\Role;
use App\Models\SalaryRequest;
use App\Models\Sprint;
use App\Models\User;
use App\Notifications\AppNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

/**
 * Writes a JSON snapshot of all data to the private disk and notifies the requester
 * with a temporary signed download link.
 */
class ExportSystemDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $requestedBy) {}

    public function handle(): void
    {
        $data = [
            'exported_at' => now()->toIso8601String(),
            'version' => config('rgoli.version'),
            'users' => User::query()->with(['roles:id,slug,name'])->get()->map(fn (User $u): array => [
                ...$u->only(['id', 'name', 'email', 'job_title', 'is_active']),
                'roles' => $u->roles->pluck('slug'),
                'disciplines' => $u->disciplineValues(),
            ]),
            'roles' => Role::query()->with('permissions:id,name')->get()->map(fn (Role $r): array => [
                ...$r->only(['id', 'slug', 'name', 'is_system']),
                'permissions' => $r->permissions->pluck('name'),
            ]),
            'projects' => Project::withTrashed()->with('members:id')->get()->map(fn (Project $p): array => [
                ...$p->toArray(),
                'member_ids' => $p->members->pluck('id'),
            ]),
            'phases' => ProjectPhase::query()->get(),
            'issues' => Issue::withTrashed()->with(['assignees:id', 'sprints:id'])->get(),
            'sprints' => Sprint::query()->get(),
            'comments' => Comment::withTrashed()->get(),
            'files' => ProjectFile::withTrashed()->get(),
            'client_payments' => ClientPayment::withTrashed()->get(),
            'salary_requests' => SalaryRequest::withTrashed()->get(),
            'activity_logs' => ActivityLog::query()->latest('id')->limit(5000)->get(),
        ];

        $path = 'exports/rgoli-export-'.now()->format('Ymd-His').'.json';
        Storage::disk('local')->put($path, (string) json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

        $user = User::query()->find($this->requestedBy);
        $user?->notify(new AppNotification('export_ready', [], URL::temporarySignedRoute('exports.download', now()->addDay(), ['file' => basename($path)])));
    }
}
