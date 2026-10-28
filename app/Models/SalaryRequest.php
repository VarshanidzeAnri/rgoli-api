<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use Database\Factories\SalaryRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $project_id
 * @property int $user_id
 * @property string $title
 * @property string $amount
 * @property string|null $note
 * @property PaymentStatus $status
 * @property int|null $decided_by
 * @property Carbon|null $decided_at
 * @property string|null $decision_note
 * @property Carbon|null $created_at
 * @property-read Project $project
 * @property-read User $user
 * @property-read User|null $decider
 */
#[Fillable(['project_id', 'user_id', 'title', 'amount', 'note', 'status', 'decided_by', 'decided_at', 'decision_note'])]
class SalaryRequest extends Model
{
    /** @use HasFactory<SalaryRequestFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'status' => PaymentStatus::class,
            'decided_at' => 'datetime',
        ];
    }

    /**
     * Requests on visible projects; without finance.view only the user's own.
     *
     * @param  Builder<SalaryRequest>  $query
     * @return Builder<SalaryRequest>
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        $query->whereHas('project', fn (Builder $p) => $p->visibleTo($user));

        if (! $user->can('finance.view')) {
            $query->where('user_id', $user->id);
        }

        return $query;
    }

    public function isPending(): bool
    {
        return $this->status === PaymentStatus::Pending;
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }
}
