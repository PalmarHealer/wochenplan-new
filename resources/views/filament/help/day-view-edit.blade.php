<p>Angebote lassen sich direkt in der Tagesansicht bearbeiten: Klick einfach auf das Angebot.</p>

<h4>Einzelnes Angebot</h4>
<p>
    Es öffnet sich ein Fenster mit Name, Beschreibung, Farbe, Notizen und dem Schalter <strong>„Fällt aus“</strong>.
    Nach dem Speichern ist die Änderung sofort für alle sichtbar.
    Über <strong>„Alle Optionen“</strong> kommst du zur vollen Bearbeiten-Seite, z. B. um Datum oder Slot zu ändern.
</p>

<h4>Wiederholendes Angebot</h4>
<p>Bei Angeboten, die jede Woche stattfinden, wirst du gefragt, was du ändern willst:</p>
<ul>
    <li><strong>Nur heute</strong> legt eine Kopie nur für diesen Tag an und öffnet sie zum Bearbeiten.
        Das wiederholende Angebot bleibt unverändert. Ideal für „fällt heute aus“ oder eine Vertretung.</li>
    <li><strong>Alle Wochen</strong> öffnet das wiederholende Angebot selbst. Änderungen gelten dann für jede Woche,
        auch rückwirkend in der Ansicht vergangener Tage (siehe „Einzelne und wiederholende Angebote“).</li>
</ul>
<p>
    Willst du eine „Nur heute“-Änderung zurücknehmen, lösch das einzelne Angebot unter
    <x-filament::link :href="\App\Filament\Resources\LessonResource::getUrl()">Angebote</x-filament::link>.
    Dann gilt an dem Tag wieder das wiederholende Angebot.
</p>

<h4>Häufige Fragen</h4>
<ul>
    <li><strong>Ich kann ein Angebot nicht anklicken.</strong> Bearbeiten darfst du nur Angebote, denen du zugewiesen bist,
        es sei denn, deine Rolle darf alle Angebote bearbeiten.</li>
    <li><strong>Leere Zellen</strong> lassen sich nicht anklicken. Neue Angebote legst du unter
        <x-filament::link :href="\App\Filament\Resources\LessonResource::getUrl('create')">Angebote → Erstellen</x-filament::link> an.</li>
    <li><strong>Ich sehe kein Feld „Personen“.</strong> Personen zuweisen dürfen nur Rollen, die alle Angebote sehen.</li>
</ul>
