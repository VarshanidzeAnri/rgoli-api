<?php

namespace App\Models;

use App\Enums\Status;
use Database\Factories\ProjectPhaseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $project_id
 * @property string $name
 * @property int $sort_order
 * @property Carbon|null $start_date
 * @property Carbon|null $end_date
 * @property Status $status
 * @property-read Project $project
 */
#[Fillable(['project_id', 'name', 'sort_order', 'start_date', 'end_date', 'status'])]
class ProjectPhase extends Model
{
    /** @use HasFactory<ProjectPhaseFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => Status::class,
            'sort_order' => 'integer',
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
