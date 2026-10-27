<?php

namespace App\Services\AiChat\Tools;

use App\Models\LessonTemplate;
use App\Models\User;
use App\Services\AiChat\AiChatTool;

class DeleteLessonTemplate implements AiChatTool
{
    public function name(): string
    {
        return 'delete_lesson_template';
    }

    public function displayName(): string
    {
        return 'Angebotsvorlage löschen';
    }

    public function description(): string
    {
        return 'Delete a lesson template (Angebotsvorlage) by its ID. This performs a soft delete.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'template_id' => [
                    'type' => 'integer',
                    'description' => 'The template ID to delete (required)',
                ],
            ],
            'required' => ['template_id'],
        ];
    }

    public function requiredPermission(): ?string
    {
        return 'delete_lesson::template';
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
        $template = LessonTemplate::find($arguments['template_id']);

        if (! $template) {
            return ['error' => 'Angebotsvorlage nicht gefunden.'];
        }

        // Policy check: user must be assigned or have view_any_lesson::template
        if (! $user->can('view_any_lesson::template') && ! $template->assignedUsers()->where('user_id', $user->id)->exists()) {
            return ['error' => 'Keine Berechtigung für diese Angebotsvorlage.'];
        }

        $name = strip_tags($template->name);
        $template->assignedUsers()->detach();
        $template->delete();

        return [
            'success' => true,
            'message' => "Angebotsvorlage \"{$name}\" wurde gelöscht.",
        ];
    }
}
