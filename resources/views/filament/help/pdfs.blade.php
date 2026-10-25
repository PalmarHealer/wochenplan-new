<p>
    Unter <x-filament::link :href="\App\Filament\Resources\DayPdfResource::getUrl()">PDF-Exporte</x-filament::link>
    liegt für jeden Schultag ein PDF. Das ist das <strong>Archiv</strong>: So sah der Plan an dem Tag aus.
</p>

<h4>Wann PDFs entstehen</h4>
<ul>
    <li>Jede Nacht um 1 Uhr wird das PDF des Vortags (montags das vom Freitag) und das des neuen Tages erstellt.</li>
    <li>Ändert sich etwas an einem Tag, wird dessen PDF als <strong>veraltet</strong> markiert (Spalte „Aktuell“ mit rotem X).
        Beim nächsten Download für heute oder einen kommenden Tag wird es neu erstellt.</li>
    <li>PDFs vergangener Tage werden nie automatisch überschrieben. Auch wenn sie als veraltet markiert sind, zeigen sie den Stand von damals.</li>
</ul>

<h4>Herunterladen</h4>
<ul>
    <li>Ein Klick auf eine Zeile lädt das PDF herunter.</li>
    <li>Mehrere markieren und „Herunterladen“ wählen ergibt eine ZIP-Datei.</li>
</ul>

<p class="help-note">
    Vorsicht mit „Neu generieren“ oder „Erstellen“ bei vergangenen Tagen: Das überschreibt das Archiv mit dem heutigen Stand,
    inklusive aller seither geänderten wiederholenden Angebote. Der alte Stand ist danach weg.
    Deshalb dürfen das nur Rollen, die auch PDFs erstellen dürfen.
</p>
