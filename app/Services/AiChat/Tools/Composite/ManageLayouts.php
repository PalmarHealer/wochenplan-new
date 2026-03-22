<?php

namespace App\Services\AiChat\Tools\Composite;

use App\Models\Layout;
use App\Models\User;
use App\Services\AiChat\AiChatTool;

class ManageLayouts implements AiChatTool
{
    public function name(): string { return 'manage_layouts'; }
    public function displayName(): string { return 'Layouts verwalten'; }

    public function description(): string
    {
        return 'Manage layouts. Actions: list, create, update, delete. Weekday: 1=Mon-5=Fri.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'action' => ['type' => 'string', 'enum' => ['list', 'create', 'update', 'delete'], 'description' => 'Action'],
                'layout_id' => ['type' => 'integer', 'description' => 'ID'],
                'name' => ['type' => 'string', 'description' => 'Name'],
                'description' => ['type' => 'string', 'description' => 'Description'],
                'weekdays' => ['type' => 'array', 'items' => ['type' => 'integer'], 'description' => 'Weekdays (1-5)'],
                'text_size' => ['type' => 'integer', 'description' => 'Text size % (default 100)'],
                'notes' => ['type' => 'string', 'description' => 'Notes'],
            ],
            'required' => ['action'],
        ];
    }

    public function requiredPermission(): ?string { return 'view_layout'; }
    public function isReadOnly(): bool { return false; }

    private const WEEKDAY_NAMES = [1 => 'Montag', 2 => 'Dienstag', 3 => 'Mittwoch', 4 => 'Donnerstag', 5 => 'Freitag'];

    public function execute(array $arguments, User $user): array
    {
        return match ($arguments['action']) {
            'list' => $this->list(),
            'create' => $this->create($arguments, $user),
            'update' => $this->update($arguments, $user),
            'delete' => $this->delete($arguments, $user),
            default => ['error' => 'Unbekannte Aktion.'],
        };
    }

    private function formatWeekdays(?array $days): string
    {
        if (empty($days)) return '-';
        return collect($days)->map(fn ($d) => self::WEEKDAY_NAMES[$d] ?? $d)->implode(', ');
    }

    private function list(): array
    {
        $layouts = Layout::all();

        $items = $layouts->map(fn (Layout $l) => [
            'id' => $l->id,
            'name' => $l->name,
            'description' => $l->description ?? '',
            'weekdays' => $this->formatWeekdays($l->weekdays),
        ])->toArray();

        return ['layouts' => $items, 'count' => count($items)];
    }

    private function create(array $args, User $user): array
    {
        if (! $user->can('create_layout')) return ['error' => 'Keine Berechtigung.'];
        if (empty($args['name'])) return ['error' => 'Name ist erforderlich.'];

        $layout = Layout::create([
            'name' => $args['name'],
            'description' => $args['description'] ?? null,
            'weekdays' => $args['weekdays'] ?? null,
            'text_size' => $args['text_size'] ?? 100,
            'layout' => '[]',
        ]);

        return ['success' => true, 'message' => "Layout \"{$layout->name}\" erstellt."];
    }

    private function update(array $args, User $user): array
    {
        if (! $user->can('update_layout')) return ['error' => 'Keine Berechtigung.'];
        $layout = Layout::find($args['layout_id'] ?? 0);
        if (! $layout) return ['error' => 'Layout nicht gefunden.'];

        $data = [];
        if (isset($args['name'])) $data['name'] = $args['name'];
        if (isset($args['description'])) $data['description'] = $args['description'];
        if (isset($args['weekdays'])) $data['weekdays'] = $args['weekdays'];
        if (isset($args['text_size'])) $data['text_size'] = $args['text_size'];
        if (isset($args['notes'])) $data['notes'] = $args['notes'];

        $layout->update($data);

        return ['success' => true, 'message' => "Layout \"{$layout->name}\" aktualisiert."];
    }

    private function delete(array $args, User $user): array
    {
        if (! $user->can('delete_layout')) return ['error' => 'Keine Berechtigung.'];
        $layout = Layout::find($args['layout_id'] ?? 0);
        if (! $layout) return ['error' => 'Layout nicht gefunden.'];
        $name = $layout->name;
        $layout->delete();
        return ['success' => true, 'message' => "Layout \"{$name}\" gelöscht."];
    }
}
