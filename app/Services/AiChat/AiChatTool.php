<?php

namespace App\Services\AiChat;

use App\Models\User;

interface AiChatTool
{
    public function name(): string;

    public function displayName(): string;

    public function description(): string;

    public function parameters(): array;

    public function requiredPermission(): ?string;

    /**
     * Permission needed for the specific action/arguments.
     * Composite tools should override to return granular permissions (e.g. create_lesson).
     * Default: falls back to requiredPermission().
     */
    public function requiredPermissionForAction(array $arguments): ?string;

    public function isReadOnly(): bool;

    public function execute(array $arguments, User $user): array;
}
