<?php

namespace App\Filament\Pages;

use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Page;

class FAQ extends Page
{
    use HasPageShield;

    protected static ?string $navigationIcon = 'tabler-progress-help';

    protected static ?string $navigationLabel = 'Hilfe';

    protected static ?string $slug = 'faq';

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationGroup = 'Administration';

    public function getTitle(): string
    {
        return 'Hilfe/Anleitungen';
    }

    protected ?string $subheading = 'In diesem Bereich findest du Anleitungen und Antworten zu häufig gestellten Fragen und bekannten Problemen.
    Diese Sammlung wird kontinuierlich erweitert. Wenn dir ein wiederkehrendes Problem oder eine häufige Frage auffällt,
    sende bitte eine Nachricht an support@nauren.de, damit der Inhalt entsprechend ergänzt werden kann.';

    protected static string $view = 'filament.pages.faq';
}
