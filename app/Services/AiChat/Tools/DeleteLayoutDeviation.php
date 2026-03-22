<?php

namespace App\Services\AiChat\Tools;

use App\Models\LayoutDeviation;
use App\Models\User;
use App\Services\AiChat\AiChatTool;

class DeleteLayoutDeviation implements AiChatTool
{
    public function name(): string
    {
        return 'delete_layout_deviation';
    }

    public function displayName(): string
    {
        return 'Layout-Abweichung löschen';
    }

    public function description(): string
    {
        return 'Delete a layout deviation (Layout-Abweichung) by its ID.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'deviation_id' => [
                    'type' => 'integer',
                    'description' => 'The layout deviation ID to delete (required)',
                ],
            ],
            'required' => ['deviation_id'],
        ];
    }

    public function requiredPermission(): ?string
    {
        return 'delete_layout::deviation';
    }

    public function isReadOnly(): bool
    {
        return false;
    }

    public function execute(array $arguments, User $user): array
    {
        $deviation = LayoutDeviation::with('layout')->find($arguments['deviation_id']);

        if (! $deviation) {
            return ['error' => 'Layout-Abweichung nicht gefunden.'];
        }

        $layoutName = $deviation->layout?->name ?? 'Unbekannt';
        $start = $deviation->start->format('d.m.Y');
        $end = $deviation->end->format('d.m.Y');
        $deviation->delete();

        return [
            'success' => true,
            'message' => "Layout-Abweichung \"{$layoutName}\" ({$start} - {$end}) wurde gelöscht.",
        ];
    }
}
