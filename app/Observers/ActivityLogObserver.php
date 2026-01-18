<?php

namespace App\Observers;

use App\Models\ActivityLog;
use App\Services\ActivityLogService;
use Illuminate\Database\Eloquent\Model;

class ActivityLogObserver
{
    protected array $originalData = [];

    public function __construct(
        protected ActivityLogService $activityLog
    ) {}

    /**
     * Handle the "creating" event - store nothing yet, wait for created
     */
    public function creating(Model $model): void
    {
        // Nothing to do here
    }

    /**
     * Handle the "created" event.
     */
    public function created(Model $model): void
    {
        // Don't log ActivityLog creation to avoid infinite loops
        if ($model instanceof ActivityLog) {
            return;
        }

        $this->activityLog->logCreate($model);
    }

    /**
     * Handle the "updating" event - store original data
     */
    public function updating(Model $model): void
    {
        // Don't log ActivityLog updates
        if ($model instanceof ActivityLog) {
            return;
        }

        // Store original data for comparison after update
        $this->originalData[get_class($model).$model->getKey()] = $model->getOriginal();
    }

    /**
     * Handle the "updated" event.
     */
    public function updated(Model $model): void
    {
        // Don't log ActivityLog updates
        if ($model instanceof ActivityLog) {
            return;
        }

        $key = get_class($model).$model->getKey();
        $originalData = $this->originalData[$key] ?? $model->getOriginal();
        unset($this->originalData[$key]);

        // Only log if there were actual changes
        if (! empty($model->getChanges())) {
            $this->activityLog->logUpdate($model, $originalData);
        }
    }

    /**
     * Handle the "deleting" event.
     */
    public function deleting(Model $model): void
    {
        // Don't log ActivityLog deletions
        if ($model instanceof ActivityLog) {
            return;
        }

        $this->activityLog->logDelete($model);
    }

    /**
     * Handle the "restored" event.
     */
    public function restored(Model $model): void
    {
        // Don't log ActivityLog restorations
        if ($model instanceof ActivityLog) {
            return;
        }

        $this->activityLog->log(
            action: 'restore',
            category: ActivityLog::CATEGORY_DATA,
            resourceType: get_class($model),
            resourceId: (string) $model->getKey(),
            resourceLabel: $this->getModelLabel($model),
        );
    }

    protected function getModelLabel(Model $model): string
    {
        $className = class_basename($model);

        foreach (['name', 'title', 'display_name', 'label', 'email'] as $field) {
            if (isset($model->{$field})) {
                return "{$className}: {$model->{$field}}";
            }
        }

        return "{$className} #{$model->getKey()}";
    }
}
