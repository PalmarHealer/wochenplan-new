<?php

namespace App\Services\AiChat\Tools\Composite;

use App\Models\User;
use App\Services\AiChat\AiChatTool;
use Spatie\Permission\Models\Role;

class ManageRoles implements AiChatTool
{
    public function name(): string { return 'manage_roles'; }
    public function displayName(): string { return 'Rollen verwalten'; }

    public function description(): string
    {
        return 'Manage roles (Rollen). Actions: list (show all roles with permission count), create (new role), update (rename), delete.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'action' => ['type' => 'string', 'enum' => ['list', 'create', 'update', 'delete'], 'description' => 'Action to perform'],
                'role_id' => ['type' => 'integer', 'description' => 'Role ID (for update/delete)'],
                'name' => ['type' => 'string', 'description' => 'Role name (for create/update)'],
            ],
            'required' => ['action'],
        ];
    }

    public function requiredPermission(): ?string { return 'view_role'; }
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
        $roles = Role::withCount('permissions')->get();

        $items = $roles->map(fn (Role $r) => [
            'id' => $r->id,
            'name' => $r->name,
            'permissions_count' => $r->permissions_count,
        ])->toArray();

        $summary = $roles->map(fn (Role $r) => "{$r->name} ({$r->permissions_count} Berechtigungen)")->implode(', ');
        return ['roles' => $items, 'summary' => $summary];
    }

    private function create(array $args, User $user): array
    {
        if (! $user->can('create_role')) return ['error' => 'Keine Berechtigung.'];
        if (empty($args['name'])) return ['error' => 'Name ist erforderlich.'];

        $role = Role::create(['name' => $args['name'], 'guard_name' => 'web']);
        return ['success' => true, 'message' => "Rolle \"{$role->name}\" erstellt."];
    }

    private function update(array $args, User $user): array
    {
        if (! $user->can('update_role')) return ['error' => 'Keine Berechtigung.'];
        $role = Role::find($args['role_id'] ?? 0);
        if (! $role) return ['error' => 'Rolle nicht gefunden.'];

        if (isset($args['name'])) {
            $role->update(['name' => $args['name']]);
        }

        return ['success' => true, 'message' => "Rolle zu \"{$role->name}\" umbenannt."];
    }

    private function delete(array $args, User $user): array
    {
        if (! $user->can('delete_role')) return ['error' => 'Keine Berechtigung.'];
        $role = Role::find($args['role_id'] ?? 0);
        if (! $role) return ['error' => 'Rolle nicht gefunden.'];

        if ($role->name === 'super_admin') {
            return ['error' => 'Die Rolle "super_admin" kann nicht gelöscht werden.'];
        }

        $name = $role->name;
        $role->delete();
        return ['success' => true, 'message' => "Rolle \"{$name}\" gelöscht."];
    }
}
