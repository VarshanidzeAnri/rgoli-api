<?php

namespace App\Providers;

use App\Listeners\SendDomainNotifications;
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
use App\Observers\ActivityObserver;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Model::preventLazyLoading(! $this->app->isProduction());

        Relation::enforceMorphMap([
            'user' => User::class,
            'project' => Project::class,
            'phase' => ProjectPhase::class,
            'issue' => Issue::class,
            'sprint' => Sprint::class,
            'comment' => Comment::class,
            'file' => ProjectFile::class,
            'client_payment' => ClientPayment::class,
            'salary_request' => SalaryRequest::class,
            'role' => Role::class,
        ]);

        /*
         * The Owner role bypasses every check, except deleting a system role.
         */
        Gate::before(function (User $user, string $ability, array $arguments): ?bool {
            if (! $user->isOwner()) {
                return null;
            }

            $target = $arguments[0] ?? null;
            if ($ability === 'delete' && $target instanceof Role && $target->is_system) {
                return false;
            }

            return true;
        });

        Event::subscribe(SendDomainNotifications::class);

        foreach ([Project::class, ProjectPhase::class, Issue::class, Sprint::class, Comment::class, ProjectFile::class, ClientPayment::class, SalaryRequest::class] as $model) {
            $model::observe(ActivityObserver::class);
        }

        RateLimiter::for('login', function (Request $request): Limit {
            $email = mb_strtolower((string) $request->input('email'));

            return Limit::perMinute(5)->by($email.'|'.$request->ip());
        });

        RateLimiter::for('password-reset', fn (Request $request): Limit => Limit::perMinute(3)->by((string) $request->ip()));

        RateLimiter::for('uploads', function (Request $request): Limit {
            $user = $request->user();

            return Limit::perMinute(30)->by($user instanceof User ? (string) $user->id : (string) $request->ip());
        });

        RateLimiter::for('api', function (Request $request): Limit {
            $user = $request->user();

            return Limit::perMinute(240)->by($user instanceof User ? (string) $user->id : (string) $request->ip());
        });

        ResetPassword::createUrlUsing(function (object $notifiable, string $token): string {
            $email = $notifiable instanceof User ? $notifiable->email : '';

            return rtrim((string) config('rgoli.frontend_url'), '/').'/reset-password?token='.$token.'&email='.urlencode($email);
        });
    }
}
