<?php

namespace App\Services\AiChat\Tools\Composite;

use App\Models\Lesson;
use App\Models\User;
use App\Services\AiChat\AiChatTool;

class ManageLessons implements AiChatTool
{
    public function name(): string
    {
        return 'manage_lessons';
    }

    public function displayName(): string
    {
        return 'Angebote verwalten';
    }

    public function description(): string
    {
        return 'Manage lessons (Angebote). Actions: list, create, update, delete. Use manage_rooms/manage_times/manage_colors with action "list" to find valid IDs.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'action' => ['type' => 'string', 'enum' => ['list', 'create', 'update', 'delete'], 'description' => 'Action'],
                'lesson_id' => ['type' => 'integer', 'description' => 'ID'],
                'name' => ['type' => 'string', 'description' => 'Name'],
                'description' => ['type' => 'string', 'description' => 'Description'],
                'date' => ['type' => 'string', 'description' => 'Date (YYYY-MM-DD)'],
                'room_id' => ['type' => 'integer', 'description' => 'Room ID'],
                'time_id' => ['type' => 'integer', 'description' => 'Time ID'],
                'color_id' => ['type' => 'integer', 'description' => 'Color ID'],
                'assigned_user_ids' => ['type' => 'array', 'items' => ['type' => 'integer'], 'description' => 'User IDs'],
                'notes' => ['type' => 'string', 'description' => 'Notes'],
                'disabled' => ['type' => 'boolean', 'description' => 'Disabled'],
                'user_id' => ['type' => 'integer', 'description' => 'Filter by user (list)'],
            ],
            'required' => ['action'],
        ];
    }

    public function requiredPermission(): ?string
    {
        return 'view_lesson';
    }

    public function requiredPermissionForAction(array $arguments): ?string
    {
        return match ($arguments['action'] ?? 'list') {
            'create' => 'create_lesson',
            'update' => 'update_lesson',
            'delete' => 'delete_lesson',
            default => $this->requiredPermission(),
        };
    }

    public function isReadOnly(): bool
    {
        return false;
    }

    public function execute(array $arguments, User $user): array
    {
        return match ($arguments['action']) {
            'list' => $this->list($arguments, $user),
            'create' => $this->create($arguments, $user),
            'update' => $this->update($arguments, $user),
            'delete' => $this->delete($arguments, $user),
            default => ['error' => 'Unbekannte Aktion.'],
        };
    }

    private function list(array $args, User $user): array
    {
        $query = Lesson::with(['rooms', 'times', 'colors', 'assignedUsers']);

        if (! $user->can('view_any_lesson')) {
            $query->whereHas('assignedUsers', fn ($q) => $q->where('users.id', $user->id));
        }

        if (isset($args['date'])) {
            $query->where('date', $args['date']);
        }
        if (isset($args['room_id'])) {
            $query->where('room', $args['room_id']);
        }
        if (isset($args['user_id'])) {
            $uid = $args['user_id'];
            $query->whereHas('assignedUsers', fn ($q) => $q->where('users.id', $uid));
        }

        $lessons = $query->get();

        $items = $lessons->map(function (Lesson $l) {
            return [
                'id' => $l->id,
                'name' => strip_tags($l->name),
                'description' => strip_tags($l->description ?? ''),
                'date' => $l->date,
                'room' => $l->rooms?->name ?? '-',
                'time' => $l->times?->name ?? '-',
                'color' => $l->colors?->name ?? '-',
                'assigned_users' => $l->assignedUsers->pluck('display_name')->implode(', ') ?: '-',
                'notes' => $l->notes ?? '',
                'disabled' => (bool) $l->disabled,
            ];
        })->toArray();

        return ['lessons' => $items, 'count' => count($items)];
    }

    private function create(array $args, User $user): array
    {
        if (! $user->can('create_lesson')) {
            return ['error' => 'Keine Berechtigung.'];
        }
        if (empty($args['name'])) {
            return ['error' => 'Name ist erforderlich.'];
        }
        if (empty($args['date'])) {
            return ['error' => 'Datum ist erforderlich.'];
        }

        $lesson = Lesson::create([
            'name' => $args['name'],
            'description' => $args['description'] ?? null,
            'date' => $args['date'],
            'room' => $args['room_id'] ?? null,
            'lesson_time' => $args['time_id'] ?? null,
            'color' => $args['color_id'] ?? null,
            'notes' => $args['notes'] ?? null,
            'disabled' => $args['disabled'] ?? false,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        if (! empty($args['assigned_user_ids'])) {
            $lesson->assignedUsers()->sync($args['assigned_user_ids']);
        }

        return ['success' => true, 'message' => "Angebot \"{$lesson->name}\" am {$lesson->date} erstellt."];
    }

    private function update(array $args, User $user): array
    {
        if (! $user->can('update_lesson')) {
            return ['error' => 'Keine Berechtigung.'];
        }
        $lesson = Lesson::with('assignedUsers')->find($args['lesson_id'] ?? 0);
        if (! $lesson) {
            return ['error' => 'Angebot nicht gefunden.'];
        }

        if (! $user->can('view_any_lesson') && ! $lesson->assignedUsers->contains('id', $user->id)) {
            return ['error' => 'Keine Berechtigung für dieses Angebot.'];
        }

        $data = ['updated_by' => $user->id];
        if (isset($args['name'])) {
            $data['name'] = $args['name'];
        }
        if (isset($args['description'])) {
            $data['description'] = $args['description'];
        }
        if (isset($args['date'])) {
            $data['date'] = $args['date'];
        }
        if (isset($args['room_id'])) {
            $data['room'] = $args['room_id'];
        }
        if (isset($args['time_id'])) {
            $data['lesson_time'] = $args['time_id'];
        }
        if (isset($args['color_id'])) {
            $data['color'] = $args['color_id'];
        }
        if (isset($args['notes'])) {
            $data['notes'] = $args['notes'];
        }
        if (isset($args['disabled'])) {
            $data['disabled'] = $args['disabled'];
        }

        $lesson->update($data);

        if (isset($args['assigned_user_ids'])) {
            $lesson->assignedUsers()->sync($args['assigned_user_ids']);
        }

        return ['success' => true, 'message' => "Angebot \"{$lesson->name}\" aktualisiert."];
    }

    private function delete(array $args, User $user): array
    {
        if (! $user->can('delete_lesson')) {
            return ['error' => 'Keine Berechtigung.'];
        }
        $lesson = Lesson::with('assignedUsers')->find($args['lesson_id'] ?? 0);
        if (! $lesson) {
            return ['error' => 'Angebot nicht gefunden.'];
        }

        if (! $user->can('view_any_lesson') && ! $lesson->assignedUsers->contains('id', $user->id)) {
            return ['error' => 'Keine Berechtigung für dieses Angebot.'];
        }

        $name = $lesson->name;
        $lesson->assignedUsers()->detach();
        $lesson->delete();

        return ['success' => true, 'message' => "Angebot \"{$name}\" gelöscht."];
    }
}
