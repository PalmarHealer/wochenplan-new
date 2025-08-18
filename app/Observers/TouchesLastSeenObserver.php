<?php

namespace App\Observers;

use App\Services\LastSeenService;

class TouchesLastSeenObserver
{
    protected function touch(): void
    {
        app(LastSeenService::class)->touch();
    }

    public function created($model): void
    {
        $this->touch();
    }

    public function updated($model): void
    {
        $this->touch();
    }

    public function deleted($model): void
    {
        $this->touch();
    }

    public function restored($model): void
    {
        $this->touch();
    }

    public function forceDeleted($model): void
    {
        $this->touch();
    }
}
