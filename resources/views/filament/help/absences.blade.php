<p>
    Unter <x-filament::link :href="\App\Filament\Resources\AbsenceResource::getUrl()">Krankmeldungen</x-filament::link>
    trägst du ein, von wann bis wann jemand fehlt. Erster und letzter Tag zählen beide mit.
</p>
@cannot('view_any_absence')
    <p>Du kannst nur dich selbst eintragen und siehst nur deine eigenen Abwesenheiten.</p>
@endcannot

<h4>Wo Abwesenheiten auftauchen</h4>
<ul>
    <li><strong>Tagesansicht:</strong> Der Name ist bei allen Angeboten durchgestrichen. Fehlen alle Personen eines Angebots, ist das ganze Angebot durchgestrichen.</li>
    <li><strong>Feld „Abwesend“</strong> in der Tagesansicht: Dort stehen die abwesenden Personen, die an dem Tag ein Angebot haben. Im PDF stehen dort alle Abwesenden.</li>
    <li><strong>Schichtplan:</strong> Der Tag ist rot mit „abwesend“ markiert und zählt nicht zur Arbeitszeit.</li>
</ul>

<p class="help-note">
    Eine Abwesenheit lässt das Angebot nicht automatisch ausfallen. Wenn es wirklich nicht stattfindet,
    klick es in der Tagesansicht an, wähl „Nur heute“ und schalte „Fällt aus“ ein, oder trag eine Vertretung ein.
</p>
