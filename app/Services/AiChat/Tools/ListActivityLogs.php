<?php

namespace App\Services\AiChat\Tools;

use App\Models\ActivityLog;
use App\Models\User;
use App\Services\AiChat\AiChatTool;

class ListActivityLogs implements AiChatTool
{
    public function name(): string
    {
        return 'list_activity_logs';
    }

    public function displayName(): string
    {
        return 'Aktivitätsprotokoll anzeigen';
    }

    public function description(): string
    {
        return 'View activity logs. Filter by user, action, date range.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'user_id' => [
                    'type' => 'integer',
                    'description' => 'User ID',
                ],
                'action' => [
                    'type' => 'string',
                    'description' => 'Action filter',
                ],
                'from' => [
                    'type' => 'string',
                    'description' => 'From (YYYY-MM-DD)',
                ],
                'to' => [
                    'type' => 'string',
                    'description' => 'To (YYYY-MM-DD)',
                ],
                'limit' => [
                    'type' => 'integer',
                    'description' => 'Max entries (default 20)',
                ],
            ],
        ];
    }

    public function requiredPermission(): ?string
    {
        return 'view_activity::log';
    }

    public function requiredPermissionForAction(array $arguments): ?string
    {
        return $this->requiredPermission();
    }

    public function isReadOnly(): bool
    {
        return true;
    }

    public function execute(array $arguments, User $user): array
    {
        $query = ActivityLog::with('user');

        if (! empty($arguments['user_id'])) {
            $query->where('user_id', $arguments['user_id']);
        }

        if (! empty($arguments['action'])) {
            $query->where('action', $arguments['action']);
        }

        if (! empty($arguments['from'])) {
            $query->where('timestamp', '>=', $arguments['from']);
        }

        if (! empty($arguments['to'])) {
            $query->where('timestamp', '<=', $arguments['to'].' 23:59:59');
        }

        $limit = $arguments['limit'] ?? 20;
        $logs = $query->orderBy('timestamp', 'desc')->limit($limit)->get();

        return [
            'count' => $logs->count(),
            'logs' => $logs->map(fn ($log) => [
                'timestamp' => $log->timestamp?->format('Y-m-d H:i:s'),
                'user' => $log->user?->display_name ?? $log->user?->name ?? 'Unbekannt',
                'action' => $log->action,
                'resource_label' => $log->resource_label,
            ])->toArray(),
        ];
    }
}
