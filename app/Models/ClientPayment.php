<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use Database\Factories\ClientPaymentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $project_id
 * @property string $amount
 * @property Carbon $paid_at
 * @property PaymentMethod $method
 * @property string|null $note
 * @property int|null $recorded_by
 * @property Carbon|null $created_at
 * @property-read Project $project
 * @property-read User|null $recorder
 */
#[Fillable(['project_id', 'amount', 'paid_at', 'method', 'note', 'recorded_by'])]
class ClientPayment extends Model
{
    /** @use HasFactory<ClientPaymentFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'date',
            'method' => PaymentMethod::class,
        ];
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
    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
