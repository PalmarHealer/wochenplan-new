<p>
    Das <x-filament::link :href="\App\Filament\Resources\LayoutResource::getUrl()">Layout</x-filament::link>
    ist das Raster des Tages: welche Räume und Zeiten es gibt und welche Texte fest im Plan stehen.
</p>

<h4>Welches Layout gilt an einem Tag?</h4>
<ol>
    <li>Gibt es eine <x-filament::link :href="\App\Filament\Resources\LayoutDeviationResource::getUrl()">Layout-Abweichung</x-filament::link>
        für das Datum (z. B. Ferien, Feiertag, Projektwoche), gilt deren Layout. Überschneiden sich mehrere, gewinnt die zuletzt geänderte.</li>
    <li>Sonst gilt das Layout, das für diesen Wochentag eingetragen ist („Gültig für“).</li>
    <li>Am Wochenende gibt es ohne Abweichung keinen Plan.</li>
</ol>
<p class="help-note">
    Ein Wochentag kann nur zu einem Layout gehören. Trägst du bei einem Layout einen Wochentag ein,
    wird er bei allen anderen Layouts entfernt.
</p>

<h4>Zellen</h4>
<ul>
    <li>Eine Zelle wird nur dann zum Slot für Angebote, wenn sie <strong>Raum und Zeit</strong> hat.</li>
    <li>Der „Zellen Text“ erscheint nur, solange kein Angebot in der Zelle liegt.</li>
    <li>Platzhalter im Zellentext: <code>%mittagessen%</code> (Essen des Tages), <code>%abwesenheit%</code> (abwesende Personen),
        <code>%tag%</code> (z. B. „Mo 26.10.2026“).</li>
    <li>Mehrere Zellen markierst du durch Ziehen. Farbe, Raum, Zeit und Text gelten dann für alle markierten Zellen.</li>
    <li>Spaltenbreite und Zeilenhöhe wirken nur im PDF, nicht in der Tagesansicht.</li>
    <li>Mit „Layout exportieren / importieren“ kopierst du ein Layout als Text, z. B. um es als Vorlage für ein neues zu nutzen.</li>
</ul>

<h4>Das solltest du wissen</h4>
<ul>
    <li>Layout-Änderungen gelten auch rückwirkend für die Ansicht vergangener Tage. Das archivierte PDF bleibt unverändert.</li>
    <li>Wiederholende Angebote zeigen beim Bearbeiten das normale Layout des Wochentags, ohne Abweichungen.</li>
    <li>Entfernst du eine Zelle mit Raum und Zeit, sind die Angebote darin nicht gelöscht, nur unsichtbar.</li>
</ul>
