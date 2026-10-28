<?php

namespace App\Observers;

use App\Models\Comment;
use App\Models\Issue;
use App\Models\Project;
use App\Models\Sprint;
use App\Repositories\Contracts\ActivityRepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Writes an activity_logs row for every create, update and delete of the observed models.
 */
class ActivityObserver
{
    /** Attributes never worth logging as changes. */
    private const IGNORED = ['updated_at', 'created_at', 'position', 'deleted_at'];

    public function __construct(private ActivityRepositoryInterface $activity) {}

    public function created(Model $model): void
    {
        $this->log($model, 'created', ['label' => $this->label($model)]);
    }

    public function updated(Model $model): void
    {
        $changes = array_diff_key($model->getChanges(), array_flip(self::IGNORED));
        if ($changes === []) {
            return;
        }

        $event = $model instanceof Issue && array_key_exists('status', $changes) ? 'moved' : 'updated';

        $from = [];
        foreach (array_keys($changes) as $key) {
            $from[$key] = $model->getOriginal($key);
        }

        $this->log($model, $event, [
            'label' => $this->label($model),
            'fields' => array_keys($changes),
            'from' => $this->scalarize($from),
            'to' => $this->scalarize($changes),
        ]);
    }

    public function deleted(Model $model): void
    {
        $this->log($model, 'deleted', ['label' => $this->label($model)]);
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    private function log(Model $model, string $event, array $meta): void
    {
        $key = $model->getKey();

        $userId = Auth::id();

        $this->activity->record([
            'user_id' => is_numeric($userId) ? (int) $userId : null,
            'project_id' => $this->projectId($model),
            'subject_type' => $model->getMorphClass(),
            'subject_id' => is_numeric($key) ? (int) $key : 0,
            'event' => $event,
            'meta' => $meta,
        ]);
    }

    private function projectId(Model $model): ?int
    {
        if ($model instanceof Project) {
            return $model->id;
        }

        if ($model instanceof Comment) {
            $projectId = DB::table('issues')->where('id', $model->issue_id)->value('project_id');

            return is_numeric($projectId) ? (int) $projectId : null;
        }

        if ($model instanceof Sprint) {
            return null;
        }

        $projectId = $model->getAttribute('project_id');

        return is_numeric($projectId) ? (int) $projectId : null;
    }

    private function label(Model $model): string
    {
        foreach (['name', 'title', 'original_name'] as $attribute) {
            $value = $model->getAttribute($attribute);
            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        if ($model instanceof Comment) {
            return mb_strimwidth($model->body, 0, 80, '…');
        }

        $amount = $model->getAttribute('amount');

        return is_scalar($amount) ? (string) $amount : '';
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, scalar|null>
     */
    private function scalarize(array $values): array
    {
        $out = [];
        foreach ($values as $key => $value) {
            $out[$key] = match (true) {
                $value instanceof \BackedEnum => $value->value,
                $value instanceof \DateTimeInterface => $value->format('Y-m-d'),
                is_scalar($value) || $value === null => is_string($value) ? mb_strimwidth($value, 0, 200, '…') : $value,
                default => json_encode($value),
            };
        }

        return $out;
    }
}
