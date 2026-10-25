<p>
    Die Tagesansicht zeigt den Plan eines Tages so, wie er im Haus aushängt. Du erreichst sie über die Tageskarten
    auf dem <x-filament::link :href="\App\Filament\Pages\Dashboard::getUrl()">Dashboard</x-filament::link>
    oder über einen Klick auf ein Datum im Schichtplan.
</p>

<h4>Bedienung</h4>
<ul>
    <li><strong>Vorheriger Tag / Nächster Tag</strong> springen über das Wochenende hinweg.</li>
    <li><strong>Vollbild</strong> blendet alles außer dem Plan aus, z. B. für einen Bildschirm im Flur. Die Knöpfe bleiben klein unten links.</li>
    <li>Die Ansicht <strong>aktualisiert sich von selbst</strong>, sobald jemand ein Angebot, eine Abwesenheit, ein Layout oder eine Farbe ändert. Neu laden ist nicht nötig.</li>
    <li>Das Datum steht in der Adresse (<code>?date=26.10.2026</code>). So kannst du einen bestimmten Tag als Link verschicken.</li>
</ul>

<h4>Was die Darstellung bedeutet</h4>
<ul>
    <li>In jeder Zelle stehen oben klein die Personen, darunter fett das Angebot und die Beschreibung.</li>
    <li><strong>Ganz durchgestrichen:</strong> Das Angebot fällt aus.</li>
    <li><strong>Einzelner Name durchgestrichen:</strong> Diese Person ist abwesend. Sind alle Personen eines Angebots abwesend, ist auch das Angebot durchgestrichen.</li>
    <li>Die Schriftgröße kommt aus dem Layout des Tages und kann deshalb von Tag zu Tag unterschiedlich sein.</li>
</ul>

<h4>PDF herunterladen</h4>
<ul>
    <li>Für heute und kommende Tage wird das PDF bei Bedarf frisch erstellt.</li>
    <li>Für vergangene Tage bekommst du das archivierte PDF, also den Stand von damals.
        Gibt es keins, erscheint „PDF nicht verfügbar“.</li>
</ul>

<h4>Häufige Fragen</h4>
<ul>
    <li><strong>Der Tag ist leer.</strong> Am Wochenende gibt es keinen Plan. In den Ferien oder an Feiertagen gilt meist ein eigenes Layout,
        in dem keine Angebote vorkommen. Die Angebote sind nicht gelöscht, sie werden nur nicht angezeigt.</li>
    <li><strong>Ein Angebot fehlt.</strong> Siehe „Angebot wird nicht angezeigt“ in dieser Hilfe.</li>
</ul>
