<?php

namespace App\Models;

use App\Enums\SprintState;
use Database\Factories\SprintFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string|null $goal
 * @property Carbon $start_date
 * @property Carbon $end_date
 * @property SprintState $state
 * @property Carbon|null $started_at
 * @property Carbon|null $completed_at
 * @property-read Collection<int, Issue> $issues
 */
#[Fillable(['name', 'goal', 'start_date', 'end_date', 'state', 'started_at', 'completed_at'])]
class Sprint extends Model
{
    /** @use HasFactory<SprintFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'state' => SprintState::class,
            'start_date' => 'date',
            'end_date' => 'date',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsToMany<Issue, $this>
     */
    public function issues(): BelongsToMany
    {
        return $this->belongsToMany(Issue::class)->withPivot('added_at');
    }
}
