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

    public function isReadOnly(): bool;

    public function execute(array $arguments, User $user): array;
}
