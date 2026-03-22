<?php

namespace App\Services\AiChat\Tools\Composite;

use App\Models\LessonTemplate;
use App\Models\User;
use App\Services\AiChat\AiChatTool;

class ManageLessonTemplates implements AiChatTool
{
    public function name(): string { return 'manage_lesson_templates'; }
    public function displayName(): string { return 'Angebotsvorlagen verwalten'; }

    public function description(): string
    {
        return 'Manage lesson templates (Angebotsvorlagen). Actions: list (filter by weekday), create (new template), update (edit template), delete (soft delete).';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'action' => ['type' => 'string', 'enum' => ['list', 'create', 'update', 'delete'], 'description' => 'Action to perform'],
                'template_id' => ['type' => 'integer', 'description' => 'Template ID (for update/delete)'],
                'name' => ['type' => 'string', 'description' => 'Template name'],
                'description' => ['type' => 'string', 'description' => 'Template description'],
                'weekday' => ['type' => 'integer', 'description' => 'Weekday (1=Monday to 5=Friday)'],
                'room_id' => ['type' => 'integer', 'description' => 'Room ID'],
                'time_id' => ['type' => 'integer', 'description' => 'Time ID'],
                'color_id' => ['type' => 'integer', 'description' => 'Color ID'],
                'assigned_user_ids' => ['type' => 'array', 'items' => ['type' => 'integer'], 'description' => 'Array of user IDs to assign'],
                'notes' => ['type' => 'string', 'description' => 'Internal notes'],
                'disabled' => ['type' => 'boolean', 'description' => 'Whether the template is disabled'],
            ],
            'required' => ['action'],
        ];
    }

    public function requiredPermission(): ?string { return 'view_lesson::template'; }
    public function isReadOnly(): bool { return false; }

    private const WEEKDAY_NAMES = [1 => 'Montag', 2 => 'Dienstag', 3 => 'Mittwoch', 4 => 'Donnerstag', 5 => 'Freitag'];

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
        $query = LessonTemplate::with(['rooms', 'times', 'colors', 'assignedUsers']);

        if (! $user->can('view_any_lesson::template')) {
            $query->whereHas('assignedUsers', fn ($q) => $q->where('users.id', $user->id));
        }

        if (isset($args['weekday'])) $query->where('weekday', $args['weekday']);

        $templates = $query->get();

        $items = $templates->map(function (LessonTemplate $t) {
            return [
                'id' => $t->id,
                'name' => strip_tags($t->name),
                'description' => strip_tags($t->description ?? ''),
                'weekday' => self::WEEKDAY_NAMES[$t->weekday] ?? $t->weekday,
                'room' => $t->rooms?->name ?? '-',
                'time' => $t->times?->name ?? '-',
                'color' => $t->colors?->name ?? '-',
                'assigned_users' => $t->assignedUsers->pluck('display_name')->implode(', ') ?: '-',
                'notes' => $t->notes ?? '',
                'disabled' => (bool) $t->disabled,
            ];
        })->toArray();

        return ['templates' => $items, 'count' => count($items)];
    }

    private function create(array $args, User $user): array
    {
        if (! $user->can('create_lesson::template')) return ['error' => 'Keine Berechtigung.'];
        if (empty($args['name'])) return ['error' => 'Name ist erforderlich.'];
        if (! isset($args['weekday'])) return ['error' => 'Wochentag ist erforderlich.'];

        $template = LessonTemplate::create([
            'name' => $args['name'],
            'description' => $args['description'] ?? null,
            'weekday' => $args['weekday'],
            'room' => $args['room_id'] ?? null,
            'lesson_time' => $args['time_id'] ?? null,
            'color' => $args['color_id'] ?? null,
            'notes' => $args['notes'] ?? null,
            'disabled' => $args['disabled'] ?? false,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        if (! empty($args['assigned_user_ids'])) {
            $template->assignedUsers()->sync($args['assigned_user_ids']);
        }

        $day = self::WEEKDAY_NAMES[$template->weekday] ?? $template->weekday;
        return ['success' => true, 'message' => "Vorlage \"{$template->name}\" für {$day} erstellt."];
    }

    private function update(array $args, User $user): array
    {
        if (! $user->can('update_lesson::template')) return ['error' => 'Keine Berechtigung.'];
        $template = LessonTemplate::with('assignedUsers')->find($args['template_id'] ?? 0);
        if (! $template) return ['error' => 'Vorlage nicht gefunden.'];

        if (! $user->can('view_any_lesson::template') && ! $template->assignedUsers->contains('id', $user->id)) {
            return ['error' => 'Keine Berechtigung für diese Vorlage.'];
        }

        $data = ['updated_by' => $user->id];
        if (isset($args['name'])) $data['name'] = $args['name'];
        if (isset($args['description'])) $data['description'] = $args['description'];
        if (isset($args['weekday'])) $data['weekday'] = $args['weekday'];
        if (isset($args['room_id'])) $data['room'] = $args['room_id'];
        if (isset($args['time_id'])) $data['lesson_time'] = $args['time_id'];
        if (isset($args['color_id'])) $data['color'] = $args['color_id'];
        if (isset($args['notes'])) $data['notes'] = $args['notes'];
        if (isset($args['disabled'])) $data['disabled'] = $args['disabled'];

        $template->update($data);

        if (isset($args['assigned_user_ids'])) {
            $template->assignedUsers()->sync($args['assigned_user_ids']);
        }

        return ['success' => true, 'message' => "Vorlage \"{$template->name}\" aktualisiert."];
    }

    private function delete(array $args, User $user): array
    {
        if (! $user->can('delete_lesson::template')) return ['error' => 'Keine Berechtigung.'];
        $template = LessonTemplate::with('assignedUsers')->find($args['template_id'] ?? 0);
        if (! $template) return ['error' => 'Vorlage nicht gefunden.'];

        if (! $user->can('view_any_lesson::template') && ! $template->assignedUsers->contains('id', $user->id)) {
            return ['error' => 'Keine Berechtigung für diese Vorlage.'];
        }

        $name = $template->name;
        $template->assignedUsers()->detach();
        $template->delete();
        return ['success' => true, 'message' => "Vorlage \"{$name}\" gelöscht."];
    }
}
