<?php

namespace App\Forms\Components;

use Filament\Forms\Components\Field;

class LayoutEditor extends Field
{
    protected string $view = 'forms.components.layout-editor';

    protected array $colors = [];

    public function colors(array $colors): static
    {
        $this->colors = $colors;

        return $this;
    }

    public function getColors(): array
    {
        return $this->colors;
    }
}
