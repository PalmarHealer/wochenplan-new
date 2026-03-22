<?php

namespace App\Services\AiChat\Tools\Composite;

use App\Models\Color;
use App\Models\User;
use App\Services\AiChat\AiChatTool;

class ManageColors implements AiChatTool
{
    public function name(): string { return 'manage_colors'; }
    public function displayName(): string { return 'Farben verwalten'; }

    public function description(): string
    {
        return 'Manage colors. Actions: list, create, update, delete.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'action' => ['type' => 'string', 'enum' => ['list', 'create', 'update', 'delete'], 'description' => 'Action'],
                'color_id' => ['type' => 'integer', 'description' => 'ID'],
                'name' => ['type' => 'string', 'description' => 'Name'],
                'color' => ['type' => 'string', 'description' => 'Hex code, e.g. #FF0000'],
            ],
            'required' => ['action'],
        ];
    }

    public function requiredPermission(): ?string { return 'view_color'; }
    public function isReadOnly(): bool { return false; }

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

    private function list(): array
    {
        $colors = Color::all();
        $items = $colors->map(fn (Color $c) => ['id' => $c->id, 'name' => $c->name, 'hex' => $c->color])->toArray();
        $summary = $colors->map(fn (Color $c) => "{$c->name} ({$c->color})")->implode(', ');
        return ['colors' => $items, 'summary' => $summary];
    }

    private function create(array $args, User $user): array
    {
        if (! $user->can('create_color')) return ['error' => 'Keine Berechtigung.'];
        $color = Color::create([
            'name' => $args['name'] ?? 'Neue Farbe',
            'color' => $args['color'] ?? '#000000',
        ]);
        return ['success' => true, 'message' => "Farbe \"{$color->name}\" ({$color->color}) erstellt."];
    }

    private function update(array $args, User $user): array
    {
        if (! $user->can('update_color')) return ['error' => 'Keine Berechtigung.'];
        $color = Color::find($args['color_id'] ?? 0);
        if (! $color) return ['error' => 'Farbe nicht gefunden.'];
        $data = [];
        if (isset($args['name'])) $data['name'] = $args['name'];
        if (isset($args['color'])) $data['color'] = $args['color'];
        $color->update($data);
        return ['success' => true, 'message' => "Farbe aktualisiert: \"{$color->name}\" ({$color->color})."];
    }

    private function delete(array $args, User $user): array
    {
        if (! $user->can('delete_color')) return ['error' => 'Keine Berechtigung.'];
        $color = Color::find($args['color_id'] ?? 0);
        if (! $color) return ['error' => 'Farbe nicht gefunden.'];
        $name = $color->name;
        $color->delete();
        return ['success' => true, 'message' => "Farbe \"{$name}\" gelöscht."];
    }
}
