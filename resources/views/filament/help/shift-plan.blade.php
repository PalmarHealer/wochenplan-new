<p>
    Der <x-filament::link :href="\App\Filament\Pages\ShiftPlan::getUrl()">Schichtplan</x-filament::link>
    zeigt eine ganze Woche (Montag bis Freitag) pro Person statt pro Raum: Wer ist wann da und was macht die Person?
    Er wird automatisch aus den Angeboten erstellt, es gibt nichts extra zu pflegen.
</p>

<h4>Was du siehst</h4>
<ul>
    <li><strong>Eine Zeile pro Person</strong>, die in dieser Woche mindestens einem Angebot zugewiesen ist.
        Personen ohne Angebot tauchen nicht auf.</li>
    <li><strong>Pro Tag</strong> oben fett „von – bis“ (Beginn des ersten bis Ende des letzten Angebots) und rechts daneben die Arbeitszeit, darunter alle Angebote mit Uhrzeit, Name und Raum.</li>
    <li><strong>Unter dem Namen</strong> die Summe der Woche, z. B. „18:15 h / Woche“.</li>
    <li><strong>Durchgestrichen</strong> sind Angebote, die ausfallen (deaktiviert), oder alle Angebote an einem Tag, an dem die Person abwesend ist.</li>
    <li><strong>Rot mit „abwesend“</strong> sind Tage mit einer eingetragenen Abwesenheit.</li>
    <li>Unter dem Datum steht der Name des Layouts, wenn an dem Tag nichts geplant ist, z. B. „Ferien“ oder „Feiertag“.</li>
    <li>Ein Klick auf das Datum öffnet die Tagesansicht dieses Tages.</li>
</ul>

<h4>Wie die Arbeitszeit berechnet wird</h4>
<ul>
    <li>Gezählt wird die Zeit, die durch Angebote abgedeckt ist. Überschneiden sich zwei Angebote, zählt die Zeit nur einmal.</li>
    <li>Lücken zwischen Angeboten zählen <strong>nicht</strong>. Deshalb kann „9:00–15:00“ mit „3:30 h“ dastehen.</li>
    <li>Ausgefallene Angebote und Tage mit Abwesenheit zählen nicht.</li>
    <li>Pausen und Mittagessen zählen, wenn die Person dort als Aufsicht eingetragen ist.</li>
</ul>

<h4>Woher Beginn und Ende kommen</h4>
<ul>
    <li>Der <strong>Beginn</strong> steht vorne im Namen der Zeit, z. B. „10:15 Angebot 1“ beginnt um 10:15.</li>
    <li>Das <strong>Ende</strong> ist der Beginn der nächsten Zeit im Layout des jeweiligen Tages.
        Gilt an einem Tag ein anderes Layout (z. B. Elternsprechtag), kann dieselbe Zeit dort länger oder kürzer sein.</li>
    <li>Die letzte Zeit des Tages (z. B. „15:00 Check-Out“) hat kein Ende und zählt mit 0 Minuten, bis ein Ende eingetragen ist.</li>
    <li>Zeiten ohne Uhrzeit im Namen (z. B. „Den ganzen Tag gültig“) haben keine Uhrzeit und zählen nicht mit.</li>
</ul>
@can('update_time')
    <p class="help-note">
        Stimmt eine Zeit nicht, trag unter
        <x-filament::link :href="\App\Filament\Resources\TimeResource::getUrl()">Zeiten</x-filament::link>
        beim Bearbeiten „Beginn“ und „Ende“ fest ein (Format <code>HH:MM</code>). Das gilt dann für alle Layouts.
    </p>
@endcan

<h4>Meins und Alle</h4>
<p>
    Wer alle Schichten sehen darf, kann oben rechts zwischen <strong>Meins</strong> (nur die eigene Zeile) und
    <strong>Alle</strong> wechseln. Wer das nicht darf, sieht immer nur die eigenen Schichten.
    Die Auswahl steht in der Adresse, ein Lesezeichen merkt sie sich also.
</p>
@can('update_role')
    <p class="help-note">
        Ob jemand alle Schichten sieht, steuert die Berechtigung <code>view_all_shifts</code> im Rollen-Editor unter
        „Benutzerdefinierte Berechtigungen“. Für den Schichtplan selbst braucht die Rolle zusätzlich die Seite „Schichtplan“.
    </p>
@endcan

<h4>Häufige Fragen</h4>
<ul>
    <li><strong>Jemand fehlt im Schichtplan.</strong> Die Person ist in dieser Woche keinem Angebot zugewiesen,
        oder ihre Angebote liegen in einem Slot, den das Layout des Tages nicht anzeigt.</li>
    <li><strong>Ich sehe mich nicht.</strong> Du bist keinem Angebot zugewiesen. Zuweisen geht beim Bearbeiten eines Angebots unter „Personen“.</li>
    <li><strong>Am Wochenende</strong> zeigt der Schichtplan automatisch die kommende Woche.</li>
    <li><strong>Ändern</strong> kann man im Schichtplan nichts. Klick auf den Tag und bearbeite das Angebot in der Tagesansicht.</li>
</ul>
