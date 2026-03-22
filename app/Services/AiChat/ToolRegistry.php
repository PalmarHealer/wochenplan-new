<?php

namespace App\Services\AiChat;

use App\Models\User;
use App\Services\AiChat\Tools\Composite\ManageAbsences;
use App\Services\AiChat\Tools\Composite\ManageColors;
use App\Services\AiChat\Tools\Composite\ManageLayoutDeviations;
use App\Services\AiChat\Tools\Composite\ManageLayouts;
use App\Services\AiChat\Tools\Composite\ManageLessons;
use App\Services\AiChat\Tools\Composite\ManageLessonTemplates;
use App\Services\AiChat\Tools\Composite\ManageRoles;
use App\Services\AiChat\Tools\Composite\ManageRooms;
use App\Services\AiChat\Tools\Composite\ManageTimes;
use App\Services\AiChat\Tools\Composite\ManageUsers;
use App\Services\AiChat\Tools\ExportDayPdf;
use App\Services\AiChat\Tools\GetFaq;
use App\Services\AiChat\Tools\GetScheduleForDate;
use App\Services\AiChat\Tools\ListActivityLogs;
use App\Services\AiChat\Tools\ReloadLunch;

class ToolRegistry
{
    /** @var AiChatTool[] */
    private array $tools = [];

    public function __construct()
    {
        $this->registerAll();
    }

    private function registerAll(): void
    {
        $this->tools = [
            new GetScheduleForDate,
            new ManageLessons,
            new ManageLessonTemplates,
            new ManageAbsences,
            new ManageRooms,
            new ManageTimes,
            new ManageColors,
            new ManageLayouts,
            new ManageLayoutDeviations,
            new ManageUsers,
            new ManageRoles,
            new ListActivityLogs,
            new ExportDayPdf,
            new GetFaq,
            new ReloadLunch,
        ];
    }

    /** @return AiChatTool[] */
    public function getToolsForUser(User $user): array
    {
        return array_filter($this->tools, function (AiChatTool $tool) use ($user) {
            $permission = $tool->requiredPermission();

            return $permission === null || $user->can($permission);
        });
    }

    public function getOllamaToolSchemas(User $user): array
    {
        return array_values(array_map(function (AiChatTool $tool) {
            return [
                'type' => 'function',
                'function' => [
                    'name' => $tool->name(),
                    'description' => $tool->description(),
                    'parameters' => $tool->parameters(),
                ],
            ];
        }, $this->getToolsForUser($user)));
    }

    public function findTool(string $name): ?AiChatTool
    {
        foreach ($this->tools as $tool) {
            if ($tool->name() === $name) {
                return $tool;
            }
        }

        return null;
    }

    public function getDisplayName(string $name): string
    {
        $tool = $this->findTool($name);

        return $tool ? $tool->displayName() : $name;
    }
}
