<?php

namespace App\Services\AiChat\Tools;

use App\Models\Absence;
use App\Models\User;
use App\Services\AiChat\AiChatTool;

class DeleteAbsence implements AiChatTool
{
    public function name(): string
    {
        return 'delete_absence';
    }

    public function displayName(): string
    {
        return 'Krankmeldung löschen';
    }

    public function description(): string
    {
        return 'Delete an absence report (Krankmeldung) by its ID.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'absence_id' => [
                    'type' => 'integer',
                    'description' => 'The absence ID to delete (required)',
                ],
            ],
            'required' => ['absence_id'],
        ];
    }

    public function requiredPermission(): ?string
    {
        return 'delete_absence';
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

        $userName = $absence->user?->display_name ?? 'Unbekannt';
        $absence->delete();

        return [
            'success' => true,
            'message' => "Krankmeldung von {$userName} wurde gelöscht.",
        ];
    }
}
