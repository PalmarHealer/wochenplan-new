<p>
    Was jemand sehen und tun darf, hängt an der <x-filament::link :href="route('filament.admin.resources.roles.index')">Rolle</x-filament::link>.
</p>
<ul>
    <li><strong>Ansehen</strong> bei Angeboten, Abwesenheiten und PDFs heißt: nur die eigenen (denen man zugewiesen ist).
        <strong>Alle ansehen</strong> heißt: alle. Bearbeiten und Löschen folgen derselben Regel.</li>
    <li><strong>Seiten</strong> wie Schichtplan und Hilfe müssen extra freigegeben werden. Die Tagesansicht und das Dashboard sieht jede angemeldete Person.</li>
    <li>Unter <strong>Benutzerdefinierte Berechtigungen</strong> steht <code>view_all_shifts</code>: Wer sie hat, sieht im Schichtplan alle Personen statt nur sich selbst.</li>
    <li>Die Hilfe zeigt jeder Person nur die Themen, die zu ihren Berechtigungen passen.</li>
    <li>Personen zuweisen dürfen nur Rollen mit „Alle ansehen“ bei Angeboten. Wer nur „Erstellen“ darf, legt Angebote ohne Personen an
        und kann sie danach selbst nicht mehr sehen.</li>
    <li>Die Rolle <code>super_admin</code> darf alles und bekommt bei jedem Start automatisch alle Berechtigungen.</li>
</ul>
