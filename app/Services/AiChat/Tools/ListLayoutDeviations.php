<?php

namespace App\Services\AiChat\Tools;

use App\Models\LayoutDeviation;
use App\Models\User;
use App\Services\AiChat\AiChatTool;

class ListLayoutDeviations implements AiChatTool
{
    public function name(): string
    {
        return 'list_layout_deviations';
    }

    public function displayName(): string
    {
        return 'Layout-Abweichungen auflisten';
    }

    public function description(): string
    {
        return 'List layout deviations (Layout-Abweichungen) filtered by date range. Shows which alternative layout is active for a given period.';
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
            ],
        ];
    }

    public function requiredPermission(): ?string
    {
        return 'view_layout::deviation';
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
        $query = LayoutDeviation::with('layout');

        if (! empty($arguments['from'])) {
            $query->where('end', '>=', $arguments['from']);
        }

        if (! empty($arguments['to'])) {
            $query->where('start', '<=', $arguments['to']);
        }

        $deviations = $query->orderBy('start', 'desc')->limit(50)->get();

        return [
            'count' => $deviations->count(),
            'deviations' => $deviations->map(fn ($d) => [
                'id' => $d->id,
                'start' => $d->start->format('Y-m-d'),
                'end' => $d->end->format('Y-m-d'),
                'layout_name' => $d->layout?->name ?? 'Unbekannt',
            ])->toArray(),
        ];
    }
}
