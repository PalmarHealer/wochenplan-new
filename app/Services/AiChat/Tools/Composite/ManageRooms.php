<?php

namespace App\Services\AiChat\Tools\Composite;

use App\Models\Room;
use App\Models\User;
use App\Services\AiChat\AiChatTool;

class ManageRooms implements AiChatTool
{
    public function name(): string { return 'manage_rooms'; }
    public function displayName(): string { return 'Räume verwalten'; }

    public function description(): string
    {
        return 'Manage rooms. Actions: list, create, update, delete.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'action' => ['type' => 'string', 'enum' => ['list', 'create', 'update', 'delete'], 'description' => 'Action'],
                'room_id' => ['type' => 'integer', 'description' => 'ID'],
                'name' => ['type' => 'string', 'description' => 'Name'],
            ],
            'required' => ['action'],
        ];
    }

    public function requiredPermission(): ?string { return 'view_room'; }
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
        $rooms = Room::all();
        return ['rooms' => $rooms->pluck('name', 'id')->toArray(), 'summary' => $rooms->pluck('name')->implode(', ')];
    }

    private function create(array $args, User $user): array
    {
        if (! $user->can('create_room')) return ['error' => 'Keine Berechtigung.'];
        $room = Room::create(['name' => $args['name'] ?? 'Neuer Raum']);
        return ['success' => true, 'message' => "Raum \"{$room->name}\" erstellt."];
    }

    private function update(array $args, User $user): array
    {
        if (! $user->can('update_room')) return ['error' => 'Keine Berechtigung.'];
        $room = Room::find($args['room_id'] ?? 0);
        if (! $room) return ['error' => 'Raum nicht gefunden.'];
        $room->update(['name' => $args['name'] ?? $room->name]);
        return ['success' => true, 'message' => "Raum zu \"{$room->name}\" umbenannt."];
    }

    private function delete(array $args, User $user): array
    {
        if (! $user->can('delete_room')) return ['error' => 'Keine Berechtigung.'];
        $room = Room::find($args['room_id'] ?? 0);
        if (! $room) return ['error' => 'Raum nicht gefunden.'];
        $name = $room->name;
        $room->delete();
        return ['success' => true, 'message' => "Raum \"{$name}\" gelöscht."];
    }
}
