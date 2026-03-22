<?php

namespace App\Services\AiChat\Tools;

use App\Models\Layout;
use App\Models\LayoutDeviation;
use App\Models\User;
use App\Services\AiChat\AiChatTool;

class CreateLayoutDeviation implements AiChatTool
{
    public function name(): string
    {
        return 'create_layout_deviation';
    }

    public function displayName(): string
    {
        return 'Layout-Abweichung erstellen';
    }

    public function description(): string
    {
        return 'Create a new layout deviation (Layout-Abweichung) for a date range with a specific layout.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'start' => [
                    'type' => 'string',
                    'description' => 'Start date (YYYY-MM-DD, required)',
                ],
                'end' => [
                    'type' => 'string',
                    'description' => 'End date (YYYY-MM-DD, required)',
                ],
                'layout_id' => [
                    'type' => 'integer',
                    'description' => 'The layout ID to use for this deviation (required)',
                ],
            ],
            'required' => ['start', 'end', 'layout_id'],
        ];
    }

    public function requiredPermission(): ?string
    {
        return 'create_layout::deviation';
    }

    public function isReadOnly(): bool
    {
        return false;
    }

    public function execute(array $arguments, User $user): array
    {
        $layout = Layout::find($arguments['layout_id']);

        if (! $layout) {
            return ['error' => 'Layout nicht gefunden.'];
        }

        $deviation = LayoutDeviation::create([
            'start' => $arguments['start'],
            'end' => $arguments['end'],
            'layout_id' => $arguments['layout_id'],
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $deviation->load('layout');

        return [
            'success' => true,
            'message' => "Layout-Abweichung für \"{$layout->name}\" vom {$deviation->start->format('d.m.Y')} bis {$deviation->end->format('d.m.Y')} wurde erstellt.",
            'deviation' => [
                'id' => $deviation->id,
                'start' => $deviation->start->format('Y-m-d'),
                'end' => $deviation->end->format('Y-m-d'),
                'layout_name' => $deviation->layout?->name,
            ],
        ];
    }
}
