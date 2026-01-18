<?php

namespace App\Listeners;

use App\Models\ActivityLog;
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

        // Check if a failed login was already logged for this email/IP in the last 5 seconds
        $recentFailed = ActivityLog::where('action', ActivityLog::ACTION_LOGIN_FAILED)
            ->where('timestamp', '>=', now()->subSeconds(2))
            ->where('ip_address', request()->ip())
            ->whereJsonContains('content->attempted_email', $email)
            ->exists();

        if ($recentFailed) {
            return; // Skip duplicate
        }

        $this->activityLog->logLoginFailed($email);
    }
}
