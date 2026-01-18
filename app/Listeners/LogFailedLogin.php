<?php

namespace App\Listeners;

use App\Services\ActivityLogService;
use Illuminate\Auth\Events\Failed;

class LogFailedLogin
{
    public function __construct(
        protected ActivityLogService $activityLog
    ) {}

    public function handle(Failed $event): void
    {
        $email = $event->credentials['email'] ?? 'unknown';
        $this->activityLog->logLoginFailed($email);
    }
}
