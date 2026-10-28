<?php

namespace App\Models;

use App\Enums\Discipline;
use App\Enums\Status;
use App\Support\Money;
use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $key
 * @property string $name
 * @property string $client_name
 * @property string|null $client_phone
 * @property string|null $client_email
 * @property string|null $address
 * @property Discipline $discipline
 * @property Status $status
 * @property string $budget
 * @property string|null $discount_price
 * @property string|null $lat
 * @property string|null $lng
 * @property int $progress
 * @property Carbon|null $start_date
 * @property Carbon|null $end_date
 * @property string|null $description
 * @property int|null $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $paid_sum
 * @property int|null $issues_count
 * @property int|null $done_issues_count
 * @property-read Collection<int, User> $members
 * @property-read Collection<int, ProjectPhase> $phases
 * @property-read ProjectPhase|null $currentPhase
 * @property-read Collection<int, Issue> $issues
 * @property-read Collection<int, ClientPayment> $clientPayments
 * @property-read User|null $creator
 */
#[Fillable(['key', 'name', 'client_name', 'client_phone', 'client_email', 'address', 'discipline', 'status', 'budget', 'discount_price', 'lat', 'lng', 'progress', 'start_date', 'end_date', 'description', 'created_by'])]
class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'discipline' => Discipline::class,
            'status' => Status::class,
            'budget' => 'decimal:2',
            'discount_price' => 'decimal:2',
            'lat' => 'decimal:7',
            'lng' => 'decimal:7',
            'progress' => 'integer',
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    /**
     * Projects visible to a user: everything with projects.view_all, otherwise
     * projects the user is a member of or whose discipline is one of theirs.
     *
     * @param  Builder<Project>  $query
     * @return Builder<Project>
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->can('projects.view_all')) {
            return $query;
        }

        $disciplines = $user->disciplineValues();

        return $query->where(function (Builder $q) use ($user, $disciplines): void {
            $q->whereHas('members', fn (Builder $m) => $m->whereKey($user->id));
            if ($disciplines !== []) {
                $q->orWhereIn('discipline', $disciplines);
            }
        });
    }

    /**
     * Adds paid_sum, issues_count and done_issues_count.
     *
     * @param  Builder<Project>  $query
     * @return Builder<Project>
     */
    public function scopeWithTotals(Builder $query): Builder
    {
        return $query
            ->withSum('clientPayments as paid_sum', 'amount')
            ->withCount([
                'issues',
                'issues as done_issues_count' => fn (Builder $q) => $q->where('status', Status::Done->value),
            ]);
    }

    public function isVisibleTo(User $user): bool
    {
        return static::query()->whereKey($this->id)->visibleTo($user)->exists();
    }

    /**
     * Agreed price: the discount price when set, otherwise the budget.
     *
     * @return numeric-string
     */
    public function agreedPrice(): string
    {
        return Money::of($this->discount_price ?? $this->budget);
    }

    /**
     * Sum of client payments. Uses paid_sum from withTotals() when loaded (null = no payments).
     *
     * @return numeric-string
     */
    public function paidAmount(): string
    {
        $sum = array_key_exists('paid_sum', $this->getAttributes())
            ? $this->paid_sum
            : $this->clientPayments()->sum('amount');

        return Money::of(is_numeric($sum) ? $sum : null);
    }

    /**
     * @return numeric-string
     */
    public function remainingAmount(): string
    {
        return Money::sub($this->agreedPrice(), $this->paidAmount());
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    /**
     * @return HasMany<ProjectPhase, $this>
     */
    public function phases(): HasMany
    {
        return $this->hasMany(ProjectPhase::class)->orderBy('sort_order');
    }

    /**
     * First phase (by order) that is not done.
     *
     * @return HasOne<ProjectPhase, $this>
     */
    public function currentPhase(): HasOne
    {
        return $this->hasOne(ProjectPhase::class)->ofMany(
            ['sort_order' => 'min'],
            fn (Builder $q) => $q->where('status', '!=', Status::Done->value),
        );
    }

    /**
     * @return HasMany<Issue, $this>
     */
    public function issues(): HasMany
    {
        return $this->hasMany(Issue::class);
    }

    /**
     * @return HasMany<ClientPayment, $this>
     */
    public function clientPayments(): HasMany
    {
        return $this->hasMany(ClientPayment::class);
    }

    /**
     * @return HasMany<ProjectFile, $this>
     */
    public function files(): HasMany
    {
        return $this->hasMany(ProjectFile::class);
    }

    /**
     * @return HasMany<SalaryRequest, $this>
     */
    public function salaryRequests(): HasMany
    {
        return $this->hasMany(SalaryRequest::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
