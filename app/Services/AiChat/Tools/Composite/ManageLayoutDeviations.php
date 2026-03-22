<?php

namespace App\Services\AiChat\Tools\Composite;

use App\Models\LayoutDeviation;
use App\Models\User;
use App\Services\AiChat\AiChatTool;

class ManageLayoutDeviations implements AiChatTool
{
    public function name(): string { return 'manage_layout_deviations'; }
    public function displayName(): string { return 'Layout-Abweichungen verwalten'; }

    public function description(): string
    {
        return 'Manage layout deviations (Layout-Abweichungen). Actions: list (filter by date range), create (new deviation), update (edit), delete.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'action' => ['type' => 'string', 'enum' => ['list', 'create', 'update', 'delete'], 'description' => 'Action to perform'],
                'deviation_id' => ['type' => 'integer', 'description' => 'Deviation ID (for update/delete)'],
                'start' => ['type' => 'string', 'description' => 'Start date (YYYY-MM-DD)'],
                'end' => ['type' => 'string', 'description' => 'End date (YYYY-MM-DD)'],
                'layout_id' => ['type' => 'integer', 'description' => 'Layout ID to use for this deviation'],
                'from' => ['type' => 'string', 'description' => 'Filter: from date (YYYY-MM-DD, for list)'],
                'to' => ['type' => 'string', 'description' => 'Filter: to date (YYYY-MM-DD, for list)'],
            ],
            'required' => ['action'],
        ];
    }

    public function requiredPermission(): ?string { return 'view_layout::deviation'; }
    public function isReadOnly(): bool { return false; }

    public function execute(array $arguments, User $user): array
    {
        return match ($arguments['action']) {
            'list' => $this->list($arguments),
            'create' => $this->create($arguments, $user),
            'update' => $this->update($arguments, $user),
            'delete' => $this->delete($arguments, $user),
            default => ['error' => 'Unbekannte Aktion.'],
        };
    }

    private function list(array $args): array
    {
        $query = LayoutDeviation::with('layout');

        if (isset($args['from'])) $query->where('end', '>=', $args['from']);
        if (isset($args['to'])) $query->where('start', '<=', $args['to']);

        $deviations = $query->get();

        $items = $deviations->map(fn (LayoutDeviation $d) => [
            'id' => $d->id,
            'start' => $d->start->format('Y-m-d'),
            'end' => $d->end->format('Y-m-d'),
            'layout' => $d->layout?->name ?? '-',
        ])->toArray();

        return ['deviations' => $items, 'count' => count($items)];
    }

    private function create(array $args, User $user): array
    {
        if (! $user->can('create_layout::deviation')) return ['error' => 'Keine Berechtigung.'];
        if (empty($args['start'])) return ['error' => 'Startdatum ist erforderlich.'];
        if (empty($args['end'])) return ['error' => 'Enddatum ist erforderlich.'];
        if (empty($args['layout_id'])) return ['error' => 'Layout-ID ist erforderlich.'];

        $deviation = LayoutDeviation::create([
            'start' => $args['start'],
            'end' => $args['end'],
            'layout_id' => $args['layout_id'],
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $deviation->load('layout');
        $layoutName = $deviation->layout?->name ?? 'Unbekannt';
        return ['success' => true, 'message' => "Layout-Abweichung erstellt: \"{$layoutName}\" vom {$args['start']} bis {$args['end']}."];
    }

    private function update(array $args, User $user): array
    {
        if (! $user->can('update_layout::deviation')) return ['error' => 'Keine Berechtigung.'];
        $deviation = LayoutDeviation::find($args['deviation_id'] ?? 0);
        if (! $deviation) return ['error' => 'Layout-Abweichung nicht gefunden.'];

        $data = ['updated_by' => $user->id];
        if (isset($args['start'])) $data['start'] = $args['start'];
        if (isset($args['end'])) $data['end'] = $args['end'];
        if (isset($args['layout_id'])) $data['layout_id'] = $args['layout_id'];

        $deviation->update($data);

        return ['success' => true, 'message' => "Layout-Abweichung aktualisiert."];
    }

    private function delete(array $args, User $user): array
    {
        if (! $user->can('delete_layout::deviation')) return ['error' => 'Keine Berechtigung.'];
        $deviation = LayoutDeviation::find($args['deviation_id'] ?? 0);
        if (! $deviation) return ['error' => 'Layout-Abweichung nicht gefunden.'];
        $deviation->delete();
        return ['success' => true, 'message' => 'Layout-Abweichung gelöscht.'];
    }
}
