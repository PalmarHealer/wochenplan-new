<?php

namespace App\Forms\Components;

use Filament\Forms\Components\RichEditor;

class CustomRichEditor extends RichEditor
{
    protected string $view = 'forms.components.custom-rich-editor';

    public static function make(string $name): static
    {
        $instance = parent::make($name);

        // Optional: Hier schon Standardwerte setzen
        return $instance;
    }

    // Hier kannst du deine eigenen Methoden/Verhalten einbauen
}
