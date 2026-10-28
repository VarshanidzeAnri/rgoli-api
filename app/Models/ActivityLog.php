<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $user_id
 * @property int|null $project_id
 * @property string $subject_type
 * @property int $subject_id
 * @property string $event
 * @property array<string, mixed>|null $meta
 * @property Carbon $created_at
 * @property-read User|null $user
 * @property-read Project|null $project
 */
#[Fillable(['user_id', 'project_id', 'subject_type', 'subject_id', 'event', 'meta', 'created_at'])]
class ActivityLog extends Model
{
    public const UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Activity on visible projects plus project-less entries by the user.
     *
     * @param  Builder<ActivityLog>  $query
     * @return Builder<ActivityLog>
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->can('projects.view_all')) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($user): void {
            $q->whereIn('project_id', Project::query()->visibleTo($user)->select('id'))
                ->orWhere(fn (Builder $o) => $o->whereNull('project_id')->where('user_id', $user->id));
        });
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class)->withTrashed();
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
