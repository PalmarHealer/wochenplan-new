<p>Es gibt zwei Arten von Angeboten:</p>
<ul>
    <li><x-filament::link :href="\App\Filament\Resources\LessonResource::getUrl()">Angebote</x-filament::link>
        gelten für <strong>genau einen Tag</strong>.</li>
    <li><x-filament::link :href="\App\Filament\Resources\LessonTemplateResource::getUrl()">Wiederholende Angebote</x-filament::link>
        gelten <strong>jede Woche</strong> am gewählten Wochentag.</li>
</ul>
<p>
    Ein einzelnes Angebot im selben Slot ersetzt an diesem Tag das wiederholende Angebot.
    So kann man ein wöchentliches Angebot an einem Tag ändern oder ausfallen lassen, ohne die anderen Wochen anzufassen.
</p>

<h4>Das Angebot aktualisiert sich nicht für alle Wochen</h4>
<p>
    Hast du das Angebot über „Nur heute“ oder unter „Angebote“ geändert, gilt die Änderung nur für diesen einen Tag.
    Soll sie jede Woche gelten, bearbeite das wiederholende Angebot (in der Tagesansicht über „Alle Wochen“).
</p>

<h4>Was passiert mit vergangenen Tagen?</h4>
<ul>
    <li><strong>Neues wiederholendes Angebot:</strong> gilt erst ab dem Tag, an dem es angelegt wurde. Vergangene Wochen bleiben unverändert.</li>
    <li><strong>Gelöschtes wiederholendes Angebot:</strong> verschwindet ab dem Löschtag. In vergangenen Wochen bleibt es sichtbar.</li>
    <li><strong>Geändertes wiederholendes Angebot:</strong> Die Änderung gilt <em>auch rückwirkend</em>.
        Vergangene Tage zeigen danach den neuen Namen, die neuen Personen und den neuen Slot.</li>
    <li>Layouts, Räume, Zeiten, Farben und Abwesenheiten wirken ebenfalls rückwirkend.</li>
</ul>
<p class="help-note">
    Den echten Stand eines vergangenen Tages findest du im archivierten PDF. Es wird jede Nacht automatisch erstellt.
    Soll ein wöchentliches Angebot ab einem bestimmten Datum anders sein, lösch das alte am Stichtag und leg ein neues an.
    Dann bleiben die vergangenen Wochen korrekt.
</p>
