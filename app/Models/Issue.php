<?php

namespace App\Models;

use App\Enums\Priority;
use App\Enums\SprintState;
use App\Enums\Status;
use Database\Factories\IssueFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $project_id
 * @property int $number
 * @property string $title
 * @property string|null $description
 * @property Status $status
 * @property Priority $priority
 * @property Carbon|null $due_date
 * @property int $points
 * @property int $position
 * @property int|null $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property int|null $comments_count
 * @property-read Project $project
 * @property-read Collection<int, User> $assignees
 * @property-read Collection<int, Sprint> $sprints
 * @property-read Collection<int, Comment> $comments
 * @property-read User|null $creator
 */
#[Fillable(['project_id', 'number', 'title', 'description', 'status', 'priority', 'due_date', 'points', 'position', 'created_by'])]
class Issue extends Model
{
    /** @use HasFactory<IssueFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => Status::class,
            'priority' => Priority::class,
            'due_date' => 'date',
            'points' => 'integer',
            'position' => 'integer',
            'number' => 'integer',
        ];
    }

    /**
     * Issues whose project is visible to the user.
     *
     * @param  Builder<Issue>  $query
     * @return Builder<Issue>
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $query->whereHas('project', fn (Builder $p) => $p->visibleTo($user));
    }

    /**
     * Issues not in a planned or active sprint.
     *
     * @param  Builder<Issue>  $query
     * @return Builder<Issue>
     */
    public function scopeInBacklog(Builder $query): Builder
    {
        return $query->whereDoesntHave('sprints', fn (Builder $s) => $s->where('state', '!=', SprintState::Completed->value));
    }

    public function key(): string
    {
        return $this->project->key.'-'.$this->number;
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function assignees(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    /**
     * @return BelongsToMany<Sprint, $this>
     */
    public function sprints(): BelongsToMany
    {
        return $this->belongsToMany(Sprint::class)->withPivot('added_at');
    }

    /**
     * @return HasMany<Comment, $this>
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class)->latest();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
