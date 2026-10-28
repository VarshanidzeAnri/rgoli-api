<?php

namespace App\Models;

use App\Enums\FileType;
use Database\Factories\ProjectFileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Uploaded document. Named ProjectFile to avoid clashing with the File facade.
 *
 * @property int $id
 * @property int $project_id
 * @property int|null $uploaded_by
 * @property string $original_name
 * @property string $disk
 * @property string $path
 * @property string $mime
 * @property int $size_bytes
 * @property FileType $type
 * @property Carbon|null $created_at
 * @property-read Project $project
 * @property-read User|null $uploader
 */
#[Fillable(['project_id', 'uploaded_by', 'original_name', 'disk', 'path', 'mime', 'size_bytes', 'type'])]
class ProjectFile extends Model
{
    /** @use HasFactory<ProjectFileFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'files';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => FileType::class,
            'size_bytes' => 'integer',
        ];
    }

    /**
     * @param  Builder<ProjectFile>  $query
     * @return Builder<ProjectFile>
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $query->whereHas('project', fn (Builder $p) => $p->visibleTo($user));
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
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
