<?php

namespace App\Services\AiChat\Tools\Composite;

use App\Models\Time;
use App\Models\User;
use App\Services\AiChat\AiChatTool;

class ManageTimes implements AiChatTool
{
    public function name(): string { return 'manage_times'; }
    public function displayName(): string { return 'Zeiten verwalten'; }

    public function description(): string
    {
        return 'Manage times (Zeiten). Actions: list (show all times), create (new time), update (rename), delete.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'action' => ['type' => 'string', 'enum' => ['list', 'create', 'update', 'delete'], 'description' => 'Action to perform'],
                'time_id' => ['type' => 'integer', 'description' => 'Time ID (for update/delete)'],
                'name' => ['type' => 'string', 'description' => 'Time name (for create/update)'],
            ],
            'required' => ['action'],
        ];
    }

    public function requiredPermission(): ?string { return 'view_time'; }
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
        $times = Time::all();
        return ['times' => $times->pluck('name', 'id')->toArray(), 'summary' => $times->pluck('name')->implode(', ')];
    }

    private function create(array $args, User $user): array
    {
        if (! $user->can('create_time')) return ['error' => 'Keine Berechtigung.'];
        $time = Time::create(['name' => $args['name'] ?? 'Neue Zeit']);
        return ['success' => true, 'message' => "Zeit \"{$time->name}\" erstellt."];
    }

    private function update(array $args, User $user): array
    {
        if (! $user->can('update_time')) return ['error' => 'Keine Berechtigung.'];
        $time = Time::find($args['time_id'] ?? 0);
        if (! $time) return ['error' => 'Zeit nicht gefunden.'];
        $time->update(['name' => $args['name'] ?? $time->name]);
        return ['success' => true, 'message' => "Zeit zu \"{$time->name}\" umbenannt."];
    }

    private function delete(array $args, User $user): array
    {
        if (! $user->can('delete_time')) return ['error' => 'Keine Berechtigung.'];
        $time = Time::find($args['time_id'] ?? 0);
        if (! $time) return ['error' => 'Zeit nicht gefunden.'];
        $name = $time->name;
        $time->delete();
        return ['success' => true, 'message' => "Zeit \"{$name}\" gelöscht."];
    }
}
