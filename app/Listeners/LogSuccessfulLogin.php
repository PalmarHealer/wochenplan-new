<?php

namespace App\Listeners;

use App\Models\ActivityLog;
use App\Services\ActivityLogService;
use Illuminate\Auth\Events\Login;

class LogSuccessfulLogin
{
    public function __construct(
        protected ActivityLogService $activityLog
    ) {}

    public function handle(Login $event): void
    {
        // Check if a login event was already logged for this user in the last 5 seconds
        $recentLogin = ActivityLog::where('user_id', $event->user->id)
            ->where('action', ActivityLog::ACTION_LOGIN)
            ->where('timestamp', '>=', now()->subSeconds(2))
            ->exists();

        if ($recentLogin) {
            return; // Skip duplicate
        }

        $this->activityLog->logLogin($event->user);
    }
}
