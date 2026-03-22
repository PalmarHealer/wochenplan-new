<?php

namespace App\Services\AiChat\Tools\Composite;

use App\Models\Absence;
use App\Models\User;
use App\Services\AiChat\AiChatTool;

class ManageAbsences implements AiChatTool
{
    public function name(): string { return 'manage_absences'; }
    public function displayName(): string { return 'Krankmeldungen verwalten'; }

    public function description(): string
    {
        return 'Manage absences (Krankmeldungen). Actions: list (filter by date range/user), create (new absence), update (edit dates), delete.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'action' => ['type' => 'string', 'enum' => ['list', 'create', 'update', 'delete'], 'description' => 'Action to perform'],
                'absence_id' => ['type' => 'integer', 'description' => 'Absence ID (for update/delete)'],
                'start' => ['type' => 'string', 'description' => 'Start date (YYYY-MM-DD)'],
                'end' => ['type' => 'string', 'description' => 'End date (YYYY-MM-DD)'],
                'user_id' => ['type' => 'integer', 'description' => 'User ID (for create/list filter)'],
                'from' => ['type' => 'string', 'description' => 'Filter: from date (YYYY-MM-DD, for list)'],
                'to' => ['type' => 'string', 'description' => 'Filter: to date (YYYY-MM-DD, for list)'],
            ],
            'required' => ['action'],
        ];
    }

    public function requiredPermission(): ?string { return 'view_absence'; }
    public function isReadOnly(): bool { return false; }

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
        $query = Absence::with('user');

        if (! $user->can('view_any_absence')) {
            $query->where('user_id', $user->id);
        }

        if (isset($args['from'])) $query->where('end', '>=', $args['from']);
        if (isset($args['to'])) $query->where('start', '<=', $args['to']);
        if (isset($args['user_id'])) $query->where('user_id', $args['user_id']);

        $absences = $query->get();

        $items = $absences->map(fn (Absence $a) => [
            'id' => $a->id,
            'user' => $a->user?->display_name ?? $a->user?->name ?? '-',
            'start' => $a->start->format('Y-m-d'),
            'end' => $a->end->format('Y-m-d'),
        ])->toArray();

        return ['absences' => $items, 'count' => count($items)];
    }

    private function create(array $args, User $user): array
    {
        if (! $user->can('create_absence')) return ['error' => 'Keine Berechtigung.'];
        if (empty($args['start'])) return ['error' => 'Startdatum ist erforderlich.'];
        if (empty($args['end'])) return ['error' => 'Enddatum ist erforderlich.'];

        $targetUserId = $args['user_id'] ?? $user->id;

        if ($targetUserId !== $user->id && ! $user->can('view_any_absence')) {
            return ['error' => 'Keine Berechtigung, Krankmeldungen für andere Benutzer zu erstellen.'];
        }

        $absence = Absence::create([
            'start' => $args['start'],
            'end' => $args['end'],
            'user_id' => $targetUserId,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $targetUser = User::find($targetUserId);
        $name = $targetUser?->display_name ?? $targetUser?->name ?? 'Unbekannt';
        return ['success' => true, 'message' => "Krankmeldung für {$name} vom {$args['start']} bis {$args['end']} erstellt."];
    }

    private function update(array $args, User $user): array
    {
        if (! $user->can('update_absence')) return ['error' => 'Keine Berechtigung.'];
        $absence = Absence::find($args['absence_id'] ?? 0);
        if (! $absence) return ['error' => 'Krankmeldung nicht gefunden.'];

        if (! $user->can('view_any_absence') && $absence->user_id !== $user->id) {
            return ['error' => 'Keine Berechtigung für diese Krankmeldung.'];
        }

        $data = ['updated_by' => $user->id];
        if (isset($args['start'])) $data['start'] = $args['start'];
        if (isset($args['end'])) $data['end'] = $args['end'];

        $absence->update($data);

        return ['success' => true, 'message' => "Krankmeldung aktualisiert ({$absence->start->format('Y-m-d')} bis {$absence->end->format('Y-m-d')})."];
    }

    private function delete(array $args, User $user): array
    {
        if (! $user->can('delete_absence')) return ['error' => 'Keine Berechtigung.'];
        $absence = Absence::find($args['absence_id'] ?? 0);
        if (! $absence) return ['error' => 'Krankmeldung nicht gefunden.'];

        if (! $user->can('view_any_absence') && $absence->user_id !== $user->id) {
            return ['error' => 'Keine Berechtigung für diese Krankmeldung.'];
        }

        $absence->delete();
        return ['success' => true, 'message' => 'Krankmeldung gelöscht.'];
    }
}
