@php
    use App\Models\Time;
    $fmt = fn (?int $minutes) => $minutes === null ? '?' : Time::formatMinutes($minutes);
@endphp
<x-filament::page>
    <style>
        .sp-wrap { overflow-x: auto; }
        .sp-table { width: 100%; border-collapse: separate; border-spacing: 0; font-size: .875rem; }
        .sp-table th, .sp-table td { border-bottom: 1px solid rgb(229 231 235); padding: .5rem; vertical-align: top; text-align: left; }
        .sp-table thead th { position: sticky; top: 0; background: rgb(249 250 251); font-weight: 600; white-space: nowrap; }
        .sp-table th.sp-name { position: sticky; left: 0; background: rgb(255 255 255); font-weight: 600; white-space: nowrap; z-index: 1; }
        .sp-table thead th.sp-name { z-index: 2; background: rgb(249 250 251); }
        .sp-table td { min-width: 9rem; }
        .sp-day-link { color: inherit; text-decoration: none; }
        .sp-day-link:hover { text-decoration: underline; }
        .sp-note { display: block; font-weight: 400; font-size: .75rem; opacity: .7; }
        .sp-entry { display: flex; gap: .375rem; align-items: baseline; padding: .125rem 0; line-height: 1.3; }
        .sp-dot { flex: none; width: .5rem; height: .5rem; border-radius: 9999px; background: rgb(156 163 175); transform: translateY(-.05rem); }
        .sp-time { font-variant-numeric: tabular-nums; font-weight: 600; white-space: nowrap; }
        .sp-room { opacity: .7; }
        .sp-off { text-decoration: line-through; opacity: .55; }
        .sp-absent { background: rgb(254 242 242); }
        .sp-badge { display: inline-block; font-size: .7rem; font-weight: 600; padding: .05rem .4rem; border-radius: .375rem; background: rgb(254 226 226); color: rgb(153 27 27); margin-bottom: .25rem; }
        .sp-sum { display: block; font-weight: 400; font-size: .75rem; opacity: .7; font-variant-numeric: tabular-nums; }
        .sp-day { display: flex; justify-content: space-between; gap: .5rem; font-size: .75rem; font-variant-numeric: tabular-nums; padding-bottom: .25rem; margin-bottom: .25rem; border-bottom: 1px dashed rgb(229 231 235); }
        .sp-day strong { font-weight: 600; }
        .dark .sp-day { border-color: rgb(255 255 255 / .1); }
        .sp-empty { padding: 2rem; text-align: center; opacity: .7; }
        .dark .sp-table th, .dark .sp-table td { border-color: rgb(255 255 255 / .1); }
        .dark .sp-table thead th, .dark .sp-table thead th.sp-name { background: rgb(24 24 27); }
        .dark .sp-table th.sp-name { background: rgb(9 9 11); }
        .dark .sp-absent { background: rgb(127 29 29 / .25); }
        .dark .sp-badge { background: rgb(127 29 29 / .6); color: rgb(254 226 226); }
    </style>

    <div style="display: flex; gap: .5rem; flex-wrap: wrap; align-items: center;">
        <x-filament::button icon="tabler-arrow-narrow-left" wire:click="changeWeek(-1)">
            Vorherige Woche
        </x-filament::button>
        <x-filament::button color="gray" wire:click="currentWeek">
            Aktuelle Woche
        </x-filament::button>
        <x-filament::button icon="tabler-arrow-narrow-right" icon-position="after" wire:click="changeWeek(1)">
            Nächste Woche
        </x-filament::button>

        @if ($this->canSeeAll())
            <x-filament::tabs style="margin: 0 0 0 auto;">
                <x-filament::tabs.item :active="! $this->showsAll()" icon="tabler-user" wire:click="setScope('meins')">
                    Meins
                </x-filament::tabs.item>
                <x-filament::tabs.item :active="$this->showsAll()" icon="tabler-users" wire:click="setScope('alle')">
                    Alle
                </x-filament::tabs.item>
            </x-filament::tabs>
        @endif
    </div>

    <x-filament::section>
        @if (empty($rows))
            <div class="sp-empty">
                {{ $this->showsAll()
                    ? 'In dieser Woche sind keine Angebote mit zugewiesenen Personen geplant.'
                    : 'Du hast in dieser Woche keine Angebote.' }}
            </div>
        @else
            <div class="sp-wrap">
                <table class="sp-table">
                    <thead>
                        <tr>
                            <th class="sp-name">Person</th>
                            @foreach ($days as $day)
                                <th>
                                    <a class="sp-day-link" href="{{ $day['url'] }}">{{ $day['label'] }}</a>
                                    @if ($day['note'])
                                        <span class="sp-note">{{ $day['note'] }}</span>
                                    @endif
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                            <tr wire:key="sp-row-{{ $row['id'] }}">
                                <th class="sp-name">
                                    {{ $row['name'] }}
                                    <span class="sp-sum" title="Arbeitszeit in dieser Woche">{{ $fmt($row['minutes']) }} h / Woche</span>
                                </th>
                                @foreach ($days as $day)
                                    @php($cell = $row['days'][$day['date']])
                                    <td @class(['sp-absent' => $cell['absent']])>
                                        @if ($cell['absent'])
                                            <span class="sp-badge">abwesend</span>
                                        @endif
                                        @if ($cell['from'] !== null)
                                            <div class="sp-day" title="Anwesend von – bis · Arbeitszeit">
                                                <strong>{{ $fmt($cell['from']) }}–{{ $fmt($cell['until']) }}</strong>
                                                <span>{{ $fmt($cell['minutes']) }} h</span>
                                            </div>
                                        @endif
                                        @foreach ($cell['entries'] as $entry)
                                            <div @class(['sp-entry', 'sp-off' => $entry['disabled'] || $cell['absent']])
                                                 title="{{ $entry['disabled'] ? 'Fällt aus' : '' }}">
                                                <span class="sp-dot" @if ($entry['color']) style="background: {{ $entry['color'] }}" @endif></span>
                                                <span>
                                                    <span class="sp-time" title="{{ $entry['time'] }}">
                                                        @if ($entry['start'] !== null)
                                                            {{ $fmt($entry['start']) }}@if ($entry['end'] !== null)–{{ $fmt($entry['end']) }}@endif
                                                        @else
                                                            {{ $entry['time'] }}
                                                        @endif
                                                    </span>
                                                    {{ $entry['name'] }}
                                                    @if ($entry['room'])
                                                        <span class="sp-room">· {{ $entry['room'] }}</span>
                                                    @endif
                                                </span>
                                            </div>
                                        @endforeach
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-filament::section>
</x-filament::page>
