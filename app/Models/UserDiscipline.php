<?php

namespace App\Models;

use App\Enums\Discipline;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Row of the discipline_user pivot. Read-only helper; writes go through User::syncDisciplines().
 *
 * @property int $user_id
 * @property Discipline $discipline
 */
class UserDiscipline extends Model
{
    protected $table = 'discipline_user';

    public $timestamps = false;

    public $incrementing = false;

    protected $primaryKey = 'user_id';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['discipline' => Discipline::class];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
