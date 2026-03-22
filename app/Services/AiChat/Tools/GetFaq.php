<?php

namespace App\Services\AiChat\Tools;

use App\Models\User;
use App\Services\AiChat\AiChatTool;

class GetFaq implements AiChatTool
{
    public function name(): string
    {
        return 'get_faq';
    }

    public function displayName(): string
    {
        return 'Hilfe & FAQ';
    }

    public function description(): string
    {
        return 'Get FAQ and help information about how the Wochenplan system works. Use this when users ask for help or have questions about using the system.';
    }

    public function parameters(): array
    {
        return ['type' => 'object', 'properties' => new \stdClass];
    }

    public function requiredPermission(): ?string
    {
        return null;
    }

    public function isReadOnly(): bool
    {
        return true;
    }

    public function execute(array $arguments, User $user): array
    {
        $faqs = [];

        $faqs[] = [
            'topic' => 'Angebot wiederholen',
            'answer' => 'Wenn ein Angebot sich nicht für alle Wochen aktualisiert, liegt das daran, dass das System standardmäßig Angebote nur für einen Tag erstellt. Damit es sich für alle Wochen ändert, muss es in den Angebotsvorlagen erstellt werden.',
        ];

        $faqs[] = [
            'topic' => 'Spalten in Tabellen anpassen',
            'answer' => 'Falls nicht alle Spalten in einer Tabelle angezeigt werden, kann man oben rechts auf das Spalten-Symbol klicken und auswählen, welche Spalten sichtbar sein sollen. Das funktioniert in allen Tabellen.',
        ];

        $faqs[] = [
            'topic' => 'Zugewiesene Benutzer verschwinden aus Angeboten',
            'answer' => 'Wenn zugewiesene Benutzer aus Angeboten verschwinden, liegt das am Filter "Alle Benutzer erlauben". Standardmäßig können nur Benutzer mit entsprechenden Berechtigungen zugewiesen werden. Die Option "Alle Benutzer erlauben" muss bei jedem Bearbeiten erneut aktiviert werden.',
        ];

        $faqs[] = [
            'topic' => 'Benutzer importieren',
            'answer' => 'Es ist möglich, mehrere Benutzer gleichzeitig über eine CSV-Datei zu importieren. Eine Beispiel-CSV ist auf der Hilfe-Seite verfügbar.',
        ];

        $faqs[] = [
            'topic' => 'Mittagessen nicht aktuell',
            'answer' => 'Das Mittagessen wird beim ersten Abruf gecacht. Wenn es nicht korrekt ist, kann es auf der Hilfe-Seite manuell geleert werden. Beim nächsten Abruf wird es dann neu von der API geladen.',
        ];

        return ['faqs' => $faqs];
    }
}
