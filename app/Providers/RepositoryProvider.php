<?php

namespace App\Providers;

use App\Repositories\ActivityRepository;
use App\Repositories\ClientPaymentRepository;
use App\Repositories\CommentRepository;
use App\Repositories\Contracts\ActivityRepositoryInterface;
use App\Repositories\Contracts\ClientPaymentRepositoryInterface;
use App\Repositories\Contracts\CommentRepositoryInterface;
use App\Repositories\Contracts\FileRepositoryInterface;
use App\Repositories\Contracts\IssueRepositoryInterface;
use App\Repositories\Contracts\NotificationRepositoryInterface;
use App\Repositories\Contracts\PhaseRepositoryInterface;
use App\Repositories\Contracts\ProjectRepositoryInterface;
use App\Repositories\Contracts\RoleRepositoryInterface;
use App\Repositories\Contracts\SalaryRequestRepositoryInterface;
use App\Repositories\Contracts\SprintRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\FileRepository;
use App\Repositories\IssueRepository;
use App\Repositories\NotificationRepository;
use App\Repositories\PhaseRepository;
use App\Repositories\ProjectRepository;
use App\Repositories\RoleRepository;
use App\Repositories\SalaryRequestRepository;
use App\Repositories\SprintRepository;
use App\Repositories\UserRepository;
use Illuminate\Support\ServiceProvider;

/**
 * Binds every repository contract to its Eloquent implementation.
 */
class RepositoryProvider extends ServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    public array $singletons = [
        ProjectRepositoryInterface::class => ProjectRepository::class,
        PhaseRepositoryInterface::class => PhaseRepository::class,
        IssueRepositoryInterface::class => IssueRepository::class,
        SprintRepositoryInterface::class => SprintRepository::class,
        CommentRepositoryInterface::class => CommentRepository::class,
        FileRepositoryInterface::class => FileRepository::class,
        ClientPaymentRepositoryInterface::class => ClientPaymentRepository::class,
        SalaryRequestRepositoryInterface::class => SalaryRequestRepository::class,
        UserRepositoryInterface::class => UserRepository::class,
        RoleRepositoryInterface::class => RoleRepository::class,
        ActivityRepositoryInterface::class => ActivityRepository::class,
        NotificationRepositoryInterface::class => NotificationRepository::class,
    ];
}
