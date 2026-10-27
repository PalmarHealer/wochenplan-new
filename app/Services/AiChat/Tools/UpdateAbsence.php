<?php

namespace App\Services\AiChat\Tools;

use App\Models\Absence;
use App\Models\User;
use App\Services\AiChat\AiChatTool;

class UpdateAbsence implements AiChatTool
{
    public function name(): string
    {
        return 'update_absence';
    }

    public function displayName(): string
    {
        return 'Krankmeldung bearbeiten';
    }

    public function description(): string
    {
        return 'Update an existing absence report (Krankmeldung) by its ID. Only provided fields will be changed.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'absence_id' => [
                    'type' => 'integer',
                    'description' => 'The absence ID to update (required)',
                ],
                'start' => [
                    'type' => 'string',
                    'description' => 'New start date (YYYY-MM-DD)',
                ],
                'end' => [
                    'type' => 'string',
                    'description' => 'New end date (YYYY-MM-DD)',
                ],
            ],
            'required' => ['absence_id'],
        ];
    }

    public function requiredPermission(): ?string
    {
        return 'update_absence';
    }

    public function requiredPermissionForAction(array $arguments): ?string
    {
        return $this->requiredPermission();
    }

    public function isReadOnly(): bool
    {
        return false;
    }

    public function execute(array $arguments, User $user): array
    {
        $absence = Absence::with('user')->find($arguments['absence_id']);

        if (! $absence) {
            return ['error' => 'Krankmeldung nicht gefunden.'];
        }

        // Policy check: own absence or view_any_absence
        if ($absence->user_id !== $user->id && ! $user->can('view_any_absence')) {
            return ['error' => 'Keine Berechtigung für diese Krankmeldung.'];
        }

        $updateData = ['updated_by' => $user->id];

        if (array_key_exists('start', $arguments)) {
            $updateData['start'] = $arguments['start'];
        }

        if (array_key_exists('end', $arguments)) {
            $updateData['end'] = $arguments['end'];
        }

        $absence->update($updateData);
        $absence->refresh();

        return [
            'success' => true,
            'message' => 'Krankmeldung erfolgreich aktualisiert.',
            'absence' => [
                'id' => $absence->id,
                'user' => $absence->user?->display_name ?? $absence->user?->name,
                'start' => $absence->start->format('Y-m-d'),
                'end' => $absence->end->format('Y-m-d'),
            ],
        ];
    }
}
