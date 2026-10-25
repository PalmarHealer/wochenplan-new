<p>
    Wer sich zum ersten Mal über „Aktive Schule Leipzig“ anmeldet, bekommt automatisch ein Konto, aber <strong>keine Rolle</strong>.
    Ohne Rolle kann die Person fast nichts sehen und taucht in der Personen-Auswahl der Angebote nicht auf.
</p>
<ol>
    <li>Öffne die <x-filament::link :href="route('filament.admin.resources.users.index')">Benutzerliste</x-filament::link>
        und filtere nach Rolle „Ohne Rolle“.</li>
    <li>Gib der Person eine Rolle, entweder beim Bearbeiten oder für mehrere auf einmal über „Berechtigung vergeben“.</li>
</ol>
<ul>
    <li>„Berechtigung vergeben“ <strong>ersetzt</strong> die bisherigen Rollen. Mit leerer Auswahl entfernst du alle Rollen.</li>
    <li>Gäste aus anderen Organisationen (z. B. Eltern-Adressen) werden mit ihrer echten E-Mail angelegt.
        Alte Konten mit Adressen wie <code>…#EXT#@…onmicrosoft.com</code> werden bei der nächsten Anmeldung automatisch umgestellt.</li>
    <li>Personen ohne Rolle kannst du trotzdem zuweisen: im Angebot „Alle Benutzer erlauben“ einschalten.</li>
</ul>
