<?php

namespace App\Services\AiChat\Tools\Composite;

use App\Models\User;
use App\Services\AiChat\AiChatTool;
use Illuminate\Support\Facades\Hash;

class ManageUsers implements AiChatTool
{
    public function name(): string
    {
        return 'manage_users';
    }

    public function displayName(): string
    {
        return 'Benutzer verwalten';
    }

    public function description(): string
    {
        return 'Manage users. Actions: list, create, update, delete.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'action' => ['type' => 'string', 'enum' => ['list', 'create', 'update', 'delete'], 'description' => 'Action'],
                'user_id' => ['type' => 'integer', 'description' => 'ID'],
                'name' => ['type' => 'string', 'description' => 'Username'],
                'email' => ['type' => 'string', 'description' => 'Email'],
                'display_name' => ['type' => 'string', 'description' => 'Display name'],
                'password' => ['type' => 'string', 'description' => 'Password'],
            ],
            'required' => ['action'],
        ];
    }

    public function requiredPermission(): ?string
    {
        return 'view_user';
    }

    public function isReadOnly(): bool
    {
        return false;
    }

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
        $users = User::with('roles')->get();

        $items = $users->map(fn (User $u) => [
            'id' => $u->id,
            'display_name' => $u->display_name ?? $u->name,
            'email' => $u->email,
            'roles' => $u->roles->pluck('name')->implode(', ') ?: '-',
        ])->toArray();

        return ['users' => $items, 'count' => count($items)];
    }

    private function create(array $args, User $user): array
    {
        if (! $user->can('create_user')) {
            return ['error' => 'Keine Berechtigung.'];
        }
        if (empty($args['name'])) {
            return ['error' => 'Name ist erforderlich.'];
        }
        if (empty($args['email'])) {
            return ['error' => 'E-Mail ist erforderlich.'];
        }
        if (empty($args['password'])) {
            return ['error' => 'Passwort ist erforderlich.'];
        }

        $newUser = User::create([
            'name' => $args['name'],
            'email' => $args['email'],
            'display_name' => $args['display_name'] ?? null,
            'password' => Hash::make($args['password']),
        ]);

        $displayName = $newUser->display_name ?? $newUser->name;

        return ['success' => true, 'message' => "Benutzer \"{$displayName}\" ({$newUser->email}) erstellt."];
    }

    private function update(array $args, User $user): array
    {
        if (! $user->can('update_user')) {
            return ['error' => 'Keine Berechtigung.'];
        }
        $target = User::find($args['user_id'] ?? 0);
        if (! $target) {
            return ['error' => 'Benutzer nicht gefunden.'];
        }

        $data = [];
        if (isset($args['name'])) {
            $data['name'] = $args['name'];
        }
        if (isset($args['email'])) {
            $data['email'] = $args['email'];
        }
        if (isset($args['display_name'])) {
            $data['display_name'] = $args['display_name'];
        }
        if (isset($args['password'])) {
            $data['password'] = Hash::make($args['password']);
        }

        $target->update($data);

        $displayName = $target->display_name ?? $target->name;

        return ['success' => true, 'message' => "Benutzer \"{$displayName}\" aktualisiert."];
    }

    private function delete(array $args, User $user): array
    {
        if (! $user->can('delete_user')) {
            return ['error' => 'Keine Berechtigung.'];
        }
        $target = User::find($args['user_id'] ?? 0);
        if (! $target) {
            return ['error' => 'Benutzer nicht gefunden.'];
        }

        if ($target->id === $user->id) {
            return ['error' => 'Du kannst dich nicht selbst löschen.'];
        }

        $displayName = $target->display_name ?? $target->name;
        $target->delete();

        return ['success' => true, 'message' => "Benutzer \"{$displayName}\" gelöscht."];
    }
}
