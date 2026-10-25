<p>
    Leider kann das System nicht automatisch erkennen, ob das Mittagessen für einen bestimmten Tag aktuell ist.
    Das Mittagessen wird beim ersten Abruf gecacht und danach nicht mehr automatisch aktualisiert.
</p>
<p>
    Wenn das Mittagessen für einen Tag nicht korrekt ist, kannst du es hier manuell leeren.
    Beim nächsten Abruf wird es dann automatisch von der API neu geladen.
</p>
<div>{{ $page->clearLunchAction }}</div>
