<?php

namespace App\Listeners;

use App\Models\ActivityLog;
use App\Services\ActivityLogService;
use Illuminate\Auth\Events\Logout;

class LogSuccessfulLogout
{
    public function __construct(
        protected ActivityLogService $activityLog
    ) {}

    public function handle(Logout $event): void
    {
        // Check if a logout event was already logged for this user/session in the last 5 seconds
        $userId = $event->user?->id;
        $sessionId = session()->getId();

        $query = ActivityLog::where('action', ActivityLog::ACTION_LOGOUT)
            ->where('timestamp', '>=', now()->subSeconds(2));

        if ($userId) {
            $query->where('user_id', $userId);
        } else {
            $query->where('session_id', $sessionId);
        }

        if ($query->exists()) {
            return; // Skip duplicate
        }

        $this->activityLog->logLogout($event->user);
    }
}
