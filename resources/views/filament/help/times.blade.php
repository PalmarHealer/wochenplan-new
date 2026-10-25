<p>
    <x-filament::link :href="\App\Filament\Resources\TimeResource::getUrl()">Zeiten</x-filament::link>
    sind die Zeilen im Layout. Seit dem Schichtplan haben sie zusätzlich einen Beginn und ein Ende.
</p>
<ul>
    <li><strong>Beginn:</strong> Bleibt das Feld leer, wird die Uhrzeit vorne aus dem Namen gelesen. „10:15 Angebot 1“ beginnt also um 10:15.</li>
    <li><strong>Ende:</strong> Bleibt das Feld leer, endet die Zeit, wenn im Layout des Tages die nächste beginnt.
        Dieselbe Zeit kann deshalb an einem Tag mit anderem Layout länger oder kürzer sein.</li>
    <li>Die <strong>letzte Zeit</strong> des Tages hat kein Ende. Trag dort ein Ende ein, sonst zählt sie im Schichtplan mit 0 Minuten.</li>
    <li>Zeiten ohne Uhrzeit, z. B. „Den ganzen Tag gültig“, haben keine Dauer.</li>
</ul>
<p>Beginn und Ende werden nur für den Schichtplan gebraucht. Tagesansicht und PDF zeigen weiterhin den Namen.</p>
