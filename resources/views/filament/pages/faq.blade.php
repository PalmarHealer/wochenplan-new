<x-filament-panels::page>

    @if(auth()->user()->can('view_lesson::template'))
        <x-filament::section
            icon="tabler-calendar-time"
            collapsible
            collapsed
            persist-collapsed
            id="lesson-template">
            <x-slot name="heading">
                Das Angebot wiederholen
            </x-slot>

            <x-slot name="description">
                Das Angebot aktualisiert sich nicht für alle Wochen.
            </x-slot>

            <span>
                Falls das Angebot sich nicht für alle Wochen aktualisiert, obwohl du auf das Angebot geklickt hast,
                liegt das daran, dass das neue System standardmäßig Angebote nur für einen Tag erstellen will.
                Wenn du willst, dass es sich für alle Wochen ändert, musst du das in den
            <x-filament::link :href="route('filament.admin.resources.lesson-templates.index')">
                Angebot vorlagen
            </x-filament::link>
                erstellen.
        </span>
        </x-filament::section>
    @endif


        @if(auth()->user()->can('view_user'))
            <x-filament::section
                icon="tabler-users-group"
                collapsible
                collapsed
                persist-collapsed
                id="import-users">
                <x-slot name="heading">
                    Benutzer importieren
                </x-slot>

            <x-slot name="description">
                Mehrere Benutzer schnell und einfach per CSV-Datei importieren.
            </x-slot>

            <span>
                    Es ist möglich mehrere Benutzer gleichzeitig importieren, indem eine <code>.csv</code>-Datei mit den
                    entsprechenden Benutzerdaten hochgeladen wird.
        <br><br>
        Eine Beispiel-Datei findest du hier:
        <x-filament::link href="{{ asset('files/personen-beispiel.csv') }}" target="_blank">
            Beispiel-CSV herunterladen
        </x-filament::link>.
        <br><br>
        Die Benutzer werden hier verwaltet und importiert:
        <x-filament::link :href="route('filament.admin.resources.users.index')">
            Zur Benutzerliste
        </x-filament::link>.
    </span>
        </x-filament::section>
    @endif

</x-filament-panels::page>
