<?php

namespace App\Listeners;

use App\Services\ActivityLogService;
use Illuminate\Auth\Events\Logout;

class LogSuccessfulLogout
{
    public function __construct(
        protected ActivityLogService $activityLog
    ) {}

    public function handle(Logout $event): void
    {
        $this->activityLog->logLogout($event->user);
    }
}
