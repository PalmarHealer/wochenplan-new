<p>
    Mehrere Benutzer legst du auf einmal mit einer <code>.csv</code>-Datei an:
    <x-filament::link :href="route('filament.admin.resources.users.index')">Benutzerliste</x-filament::link> → „Importieren“.
</p>
<ul>
    <li>Spalten: <code>Vorname;Nachname;Email</code>, getrennt mit Semikolon. Die erste Zeile ist die Überschrift und wird übersprungen.
        <x-filament::link href="{{ asset('files/personen-beispiel.csv') }}" target="_blank">Beispiel-CSV herunterladen</x-filament::link></li>
    <li>Der Vorname wird zum Anzeigenamen, „Vorname Nachname“ zum Namen.</li>
    <li>Zeilen mit fehlenden Spalten oder ungültiger E-Mail werden still übersprungen. Vergleich die Anzahl in der Meldung mit deiner Datei.</li>
    <li>Gibt es die E-Mail schon, wird der Benutzer <strong>nicht</strong> verändert.</li>
</ul>
<p class="help-note">
    Importierte Benutzer haben keine Rolle und kein bekanntes Passwort. Gib ihnen eine Rolle (siehe „Neue Benutzer freischalten“).
    Anmelden können sie sich über die Schule, wenn die E-Mail mit dem Schulkonto übereinstimmt.
</p>
