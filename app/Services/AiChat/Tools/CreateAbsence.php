<?php

namespace App\Services\AiChat\Tools;

use App\Models\Absence;
use App\Models\User;
use App\Services\AiChat\AiChatTool;

class CreateAbsence implements AiChatTool
{
    public function name(): string
    {
        return 'create_absence';
    }

    public function displayName(): string
    {
        return 'Krankmeldung erstellen';
    }

    public function description(): string
    {
        return 'Create a new absence report (Krankmeldung) for a user. Regular users can only create absences for themselves. Admins with view_any_absence can create for any user.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'start' => [
                    'type' => 'string',
                    'description' => 'Start date of absence (YYYY-MM-DD, required)',
                ],
                'end' => [
                    'type' => 'string',
                    'description' => 'End date of absence (YYYY-MM-DD, required)',
                ],
                'user_id' => [
                    'type' => 'integer',
                    'description' => 'User ID for the absence. Defaults to current user if not specified.',
                ],
            ],
            'required' => ['start', 'end'],
        ];
    }

    public function requiredPermission(): ?string
    {
        return 'create_absence';
    }

    public function isReadOnly(): bool
    {
        return false;
    }

    public function execute(array $arguments, User $user): array
    {
        $targetUserId = $arguments['user_id'] ?? $user->id;

        // Non-admin users can only create absences for themselves
        if ($targetUserId !== $user->id && ! $user->can('view_any_absence')) {
            return ['error' => 'Du kannst nur Krankmeldungen für dich selbst erstellen.'];
        }

        $absence = Absence::create([
            'start' => $arguments['start'],
            'end' => $arguments['end'],
            'user_id' => $targetUserId,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $absence->load('user');

        return [
            'success' => true,
            'message' => 'Krankmeldung erfolgreich erstellt.',
            'absence' => [
                'id' => $absence->id,
                'user' => $absence->user?->display_name ?? $absence->user?->name,
                'start' => $absence->start->format('Y-m-d'),
                'end' => $absence->end->format('Y-m-d'),
            ],
        ];
    }
}
