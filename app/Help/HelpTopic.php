<?php

namespace App\Help;

use App\Models\User;

/**
 * One entry on the help page. The body lives in resources/views/filament/help/{id}.blade.php.
 */
final class HelpTopic
{
    /**
     * @param  list<string>  $permissions  shown if the user has any of them; empty = everyone
     * @param  list<string>  $keywords  extra search terms that are not in the text
     */
    public function __construct(
        public readonly string $id,
        public readonly string $group,
        public readonly string $icon,
        public readonly string $heading,
        public readonly string $description,
        public readonly array $permissions = [],
        public readonly array $keywords = [],
    ) {}

    public function isVisibleTo(User $user): bool
    {
        return $this->permissions === [] || $user->canAny($this->permissions);
    }

    public function view(): string
    {
        return 'filament.help.'.$this->id;
    }
}
