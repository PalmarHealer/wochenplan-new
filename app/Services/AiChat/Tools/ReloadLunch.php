<?php

namespace App\Services\AiChat\Tools;

use App\Models\User;
use App\Services\AiChat\AiChatTool;
use App\Services\LunchService;

class ReloadLunch implements AiChatTool
{
    public function name(): string
    {
        return 'reload_lunch';
    }

    public function displayName(): string
    {
        return 'Mittagessen neu laden';
    }

    public function description(): string
    {
        return 'Clear the cached lunch for a specific date so it will be reloaded from the API on next access. Use this when the lunch menu for a day is wrong or outdated.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'date' => [
                    'type' => 'string',
                    'description' => 'The date to reload lunch for (YYYY-MM-DD format, required)',
                ],
            ],
            'required' => ['date'],
        ];
    }

    public function requiredPermission(): ?string
    {
        return 'update_layout';
    }

    public function isReadOnly(): bool
    {
        return false;
    }

    public function execute(array $arguments, User $user): array
    {
        $date = $arguments['date'];
        $lunchService = app(LunchService::class);

        $cleared = $lunchService->clearLunch($date);

        if ($cleared) {
            // Fetch fresh data
            $newLunch = $lunchService->getLunch($date);

            return [
                'success' => true,
                'message' => "Mittagessen für {$date} wurde neu geladen.",
                'lunch' => $newLunch,
            ];
        }

        // No cached entry, try fetching
        $lunch = $lunchService->getLunch($date);

        return [
            'success' => true,
            'message' => "Kein Cache vorhanden. Mittagessen wurde frisch geladen.",
            'lunch' => $lunch,
        ];
    }
}
