<?php

namespace App\Forms\Components;

use Closure;
use Filament\Forms\Components\Field;

class LayoutSelector extends Field
{
    protected string $view = 'forms.components.layout-selector';

    /** @var array|Closure */
    protected $layout = [];

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

    /**
     * Accepts either a static array layout or a Closure that returns an array.
     */
    public function layout(array|Closure $layout): static
    {
        $this->layout = $layout;

        return $this;
    }

    public function getLayout(): array
    {
        $evaluated = $this->evaluate($this->layout);

        return is_array($evaluated) ? $evaluated : [];
    }
}
