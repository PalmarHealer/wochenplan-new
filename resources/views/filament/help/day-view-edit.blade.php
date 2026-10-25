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

<h4>Neues Angebot in einer leeren Zelle</h4>
<p>
    Klick auf einen freien Slot. Du wirst gefragt, ob das Angebot <strong>nur heute</strong> oder <strong>jede Woche</strong>
    stattfinden soll. Danach öffnet sich dasselbe Fenster wie beim Bearbeiten.
</p>
<ul>
    <li><strong>Nur heute</strong> legt ein Angebot für genau diesen Tag an.</li>
    <li><strong>Jede Woche</strong> legt ein wiederholendes Angebot an, das ab heute jede Woche an diesem Wochentag in diesem Slot stattfindet.</li>
    <li>Darfst du nur eins von beiden, entfällt die Frage.</li>
</ul>

<h4>Häufige Fragen</h4>
<ul>
    <li><strong>Ich kann ein Angebot nicht anklicken.</strong> Bearbeiten darfst du nur Angebote, denen du zugewiesen bist,
        es sei denn, deine Rolle darf alle Angebote bearbeiten.</li>
    <li><strong>Eine leere Zelle lässt sich nicht anklicken.</strong> Anklickbar sind nur Zellen mit Raum und Zeit,
        und du brauchst das Recht, Angebote anzulegen.</li>
    <li><strong>Ich sehe kein Feld „Personen“.</strong> Personen zuweisen dürfen nur Rollen, die alle Angebote sehen.</li>
</ul>
