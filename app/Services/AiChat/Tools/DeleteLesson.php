<?php

namespace App\Services\AiChat\Tools;

use App\Models\Lesson;
use App\Models\User;
use App\Services\AiChat\AiChatTool;

class DeleteLesson implements AiChatTool
{
    public function name(): string
    {
        return 'delete_lesson';
    }

    public function displayName(): string
    {
        return 'Angebot löschen';
    }

    public function description(): string
    {
        return 'Delete a lesson (Angebot) by its ID. This action is permanent.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'lesson_id' => [
                    'type' => 'integer',
                    'description' => 'The lesson ID to delete (required)',
                ],
            ],
            'required' => ['lesson_id'],
        ];
    }

    public function requiredPermission(): ?string
    {
        return 'delete_lesson';
    }

    public function isReadOnly(): bool
    {
        return false;
    }

    public function execute(array $arguments, User $user): array
    {
        $lesson = Lesson::find($arguments['lesson_id']);

        if (! $lesson) {
            return ['error' => 'Angebot nicht gefunden.'];
        }

        // Policy check
        if (! $user->can('view_any_lesson') && ! $lesson->assignedUsers()->where('user_id', $user->id)->exists()) {
            return ['error' => 'Keine Berechtigung für dieses Angebot.'];
        }

        $name = strip_tags($lesson->name);
        $lesson->assignedUsers()->detach();
        $lesson->delete();

        return [
            'success' => true,
            'message' => "Angebot \"{$name}\" wurde gelöscht.",
        ];
    }
}
