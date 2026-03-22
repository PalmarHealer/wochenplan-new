<?php

namespace App\Services\AiChat\Tools;

use App\Models\Absence;
use App\Models\User;
use App\Services\AiChat\AiChatTool;

class ListAbsences implements AiChatTool
{
    public function name(): string
    {
        return 'list_absences';
    }

    public function displayName(): string
    {
        return 'Krankmeldungen auflisten';
    }

    public function description(): string
    {
        return 'List absences (Krankmeldungen) filtered by date range or user. Shows who is absent and when.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'from' => [
                    'type' => 'string',
                    'description' => 'Start date filter (YYYY-MM-DD)',
                ],
                'to' => [
                    'type' => 'string',
                    'description' => 'End date filter (YYYY-MM-DD)',
                ],
                'user_id' => [
                    'type' => 'integer',
                    'description' => 'Filter by absent user ID',
                ],
            ],
        ];
    }

    public function requiredPermission(): ?string
    {
        return 'view_absence';
    }

    public function isReadOnly(): bool
    {
        return true;
    }

    public function execute(array $arguments, User $user): array
    {
        $query = Absence::with(['user']);

        // Scope: only own absences unless view_any_absence
        if (! $user->can('view_any_absence')) {
            $query->where('user_id', $user->id);
        }

        if (! empty($arguments['from'])) {
            $query->where('end', '>=', $arguments['from']);
        }

        if (! empty($arguments['to'])) {
            $query->where('start', '<=', $arguments['to']);
        }

        if (! empty($arguments['user_id'])) {
            $query->where('user_id', $arguments['user_id']);
        }

        $absences = $query->orderBy('start', 'desc')->limit(50)->get();

        return [
            'count' => $absences->count(),
            'absences' => $absences->map(fn ($a) => [
                'id' => $a->id,
                'user' => $a->user?->display_name ?? $a->user?->name,
                'user_id' => $a->user_id,
                'start' => $a->start->format('Y-m-d'),
                'end' => $a->end->format('Y-m-d'),
            ])->toArray(),
        ];
    }
}
