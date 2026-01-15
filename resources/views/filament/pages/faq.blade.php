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


    @if(auth()->user()->can('view_lesson'))
        <x-filament::section
            icon="tabler-table"
            collapsible
            collapsed
            persist-collapsed
            id="lesson-columns">
            <x-slot name="heading">
                Spalten in Tabellen anpassen
            </x-slot>

            <x-slot name="description">
                Nicht alle Details in der Angebotsliste sichtbar.
            </x-slot>

            <span>
                Falls nicht alle Spalten in der Angebotsliste angezeigt werden, kannst du diese ganz einfach einblenden.
                Klicke dazu oben rechts in der Tabelle auf das Spalten-Symbol
                <x-filament::icon
                    icon="heroicon-m-view-columns"
                    class="inline h-5 w-5"
                />
                und wähle aus, welche Spalten du sehen möchtest.
                <br><br>
                Dies funktioniert in allen Tabellen der Anwendung.
            </span>
        </x-filament::section>
    @endif

    @if(auth()->user()->can('view_any_lesson'))
        <x-filament::section
            icon="tabler-user-x"
            collapsible
            collapsed
            persist-collapsed
            id="lesson-users-disappear">
            <x-slot name="heading">
                Zugewiesene Benutzer verschwinden aus Angeboten
            </x-slot>

            <x-slot name="description">
                Benutzer werden automatisch aus der Zuweisung entfernt.
            </x-slot>

            <span>
                Wenn zugewiesene Benutzer aus Angeboten verschwinden, liegt das meist am Filter "Alle Benutzer erlauben".
                <br><br>
                Standardmäßig können nur Benutzer mit entsprechenden Berechtigungen zugewiesen werden.
                Wenn die Option "Alle Benutzer erlauben" deaktiviert ist, werden beim Öffnen/Bearbeiten
                des Angebots automatisch alle Benutzer entfernt, die nicht dem Filtermuster entsprechen.
                <br><br>
                Wenn ein Benutzer beim Laden eines Angebots fehlt, musst du ihn manuell wieder hinzufügen indem du
                die Option "Alle Benutzer erlauben" im Angebot aktivierst und die fehlenden Benutzer wieder auswählst.
                <br><br>
                <em>Wichtig: Die Option "Alle Benutzer erlauben" merkt sich ihren Status nicht und muss bei jedem Bearbeiten
                des Angebots erneut aktiviert werden, bevor Benutzer hinzugefügt werden, die nicht dem Filtermuster entsprechen.</em>
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

    @if(auth()->user()->can('update_layout'))
        <x-filament::section
            icon="tabler-alert-triangle"
            collapsible
            collapsed
            persist-collapsed
            id="lunch-not-current">
            <x-slot name="heading">
                Mittagessen nicht aktuell
            </x-slot>

            <x-slot name="description">
                Das Mittagessen für einen bestimmten Tag ist nicht korrekt.
            </x-slot>

            <div>
                <p class="mb-4">
                    Leider kann das System nicht automatisch erkennen, ob das Mittagessen für einen bestimmten Tag aktuell ist.
                    Das Mittagessen wird beim ersten Abruf gecacht und danach nicht mehr automatisch aktualisiert.
                </p>

                <p class="mb-4">
                    Wenn das Mittagessen für einen Tag nicht korrekt ist, kannst du es hier manuell leeren.
                    Beim nächsten Abruf wird es dann automatisch von der API neu geladen.
                </p>

                <div class="flex justify-start">
                    {{ ($this->clearLunchAction) }}
                </div>
            </div>
        </x-filament::section>
    @endif

</x-filament-panels::page>
