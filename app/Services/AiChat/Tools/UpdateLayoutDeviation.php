<?php

namespace App\Services\AiChat\Tools;

use App\Models\LayoutDeviation;
use App\Models\User;
use App\Services\AiChat\AiChatTool;

class UpdateLayoutDeviation implements AiChatTool
{
    public function name(): string
    {
        return 'update_layout_deviation';
    }

    public function displayName(): string
    {
        return 'Layout-Abweichung bearbeiten';
    }

    public function description(): string
    {
        return 'Update an existing layout deviation (Layout-Abweichung) by its ID. Only provided fields will be changed.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'deviation_id' => [
                    'type' => 'integer',
                    'description' => 'The layout deviation ID to update (required)',
                ],
                'start' => [
                    'type' => 'string',
                    'description' => 'New start date (YYYY-MM-DD)',
                ],
                'end' => [
                    'type' => 'string',
                    'description' => 'New end date (YYYY-MM-DD)',
                ],
                'layout_id' => [
                    'type' => 'integer',
                    'description' => 'New layout ID',
                ],
            ],
            'required' => ['deviation_id'],
        ];
    }

    public function requiredPermission(): ?string
    {
        return 'update_layout::deviation';
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

        $updateData = ['updated_by' => $user->id];

        if (array_key_exists('start', $arguments)) {
            $updateData['start'] = $arguments['start'];
        }

        if (array_key_exists('end', $arguments)) {
            $updateData['end'] = $arguments['end'];
        }

        if (array_key_exists('layout_id', $arguments)) {
            $updateData['layout_id'] = $arguments['layout_id'];
        }

        $deviation->update($updateData);
        $deviation->load('layout');

        return [
            'success' => true,
            'message' => 'Layout-Abweichung erfolgreich aktualisiert.',
            'deviation' => [
                'id' => $deviation->id,
                'start' => $deviation->start->format('Y-m-d'),
                'end' => $deviation->end->format('Y-m-d'),
                'layout_name' => $deviation->layout?->name,
            ],
        ];
    }
}
