<?php

namespace App\Forms\Components;

use Filament\Forms\Components\Field;

class LayoutSelector extends Field
{
    protected string $view = 'forms.components.layout-selector';

    protected array $layout = [];

    protected array $colors = [];

    protected bool $isEditable = false;

    public function colors(array $colors): static
    {
        $this->colors = $colors;

        return $this;
    }

    public function getColors(): array
    {
        return $this->colors;
    }


    public function layout(array $layout): static
    {
        $this->layout = $layout;

        return $this;
    }

    public function getLayout(): array
    {
        return $this->layout;
    }
}
