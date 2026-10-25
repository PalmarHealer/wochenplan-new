<x-filament::page>
    <style>
        body {
            font-size: {{ $textSize }}% !important;
        }
    </style>

    <div
        id="page-content"
        x-data="mount()"
        x-init="init()"
        class="bg-gray-50 text-gray-950 dark:bg-gray-950 dark:text-white"
        wire:poll.2000ms="checkForUpdate">

        <div
            class="mb-2 gap-2 flex z-10"
            :class="{
                'fixed bottom-0 left-0': isFullscreen,
            }"
            :style="isFullscreen ? 'margin-left: 0.5rem' : ''">
            <x-filament::button
                icon="tabler-arrows-diagonal"
                icon-position="after"
                x-show="!isFullscreen"
                @click="toggleFullscreen"
            >
                Vollbild
            </x-filament::button>
            <x-filament::icon-button
                icon="tabler-arrows-diagonal-minimize-2"
                size="xl"
                label="Vollbild verlassen"
                x-show="isFullscreen"
                @click="toggleFullscreen"
            />
            <x-filament::button
                icon="tabler-download"
                icon-position="after"
                x-show="!isFullscreen"
                wire:click="downloadPdf"
            >
                PDF herunterladen
            </x-filament::button>

            <x-filament::button
                icon="tabler-arrow-narrow-left"
                icon-position="after"
                x-show="!isFullscreen"
                wire:click="changeDay(-1)"
            >
                Vorheriger Tag
            </x-filament::button>
            <x-filament::icon-button
                icon="tabler-arrow-narrow-left"
                size="xl"
                label="Vorheriger Tag"
                x-show="isFullscreen"
                wire:click="changeDay(-1)"
            />
            <x-filament::button
                icon="tabler-arrow-narrow-right"
                icon-position="after"
                x-show="!isFullscreen"
                wire:click="changeDay(1)"
            >
                Nächster Tag
            </x-filament::button>
            <x-filament::icon-button
                icon="tabler-arrow-narrow-right"
                size="xl"
                label="Nächster Tag"
                x-show="isFullscreen"
                wire:click="changeDay(1)"
            />
        </div>

        <div class="overflow-x-auto h-full">
            <table
                class="relative table-auto w-full"
                :class="{
                'h-full': isFullscreen,
            }">
                <tbody>
                @foreach ($dayLayout as $row)
                    <tr class="h-10">
                        @foreach ($row as $cell)
                            @if (!isset($cell['hidden']))
                                @php
                                    $lesson = collect($lessons)->first(function ($lesson) use ($cell) {
                                        return $lesson['room'] == $cell['room'] && $lesson['lesson_time'] == $cell['time'];
                                    });
                                    $canCreateHere = ! isset($lesson) && ! empty($cell['room']) && ! empty($cell['time'])
                                        && ($canCreateLesson || $canCreateRecurring);
                                @endphp
                                <td
                                    @if (isset($cell['colspan']) && $cell['colspan'] > 1)
                                        colspan="{{ $cell['colspan'] }}"
                                    @endif
                                    @if (isset($cell['rowspan']) && $cell['rowspan'] > 1)
                                        rowspan="{{ $cell['rowspan'] }}"
                                    @endif
                                    class="p-1 text-center align-middle @if(($lesson['url'] ?? false) || ! empty($lesson['inline_lesson']) || $canCreateHere) cursor-pointer @endif"
                                    style="
                                        color: black;
                                        border: 0.35vh solid white;
                                        text-align: {{ $cell['alignment'] ?? 'left' }};
                                        @if (!empty($lesson['color']))
                                            background-color: {{ $colors[$lesson['color']] }};
                                        @elseif(isset($cell['color']))
                                            background-color: {{ $colors[$cell['color']] }};
                                        @endif
                                    "
                                    @if (! empty($lesson['inline_lesson']))
                                        wire:click="mountAction('editLesson', { lesson: {{ (int) $lesson['inline_lesson'] }} })"
                                    @elseif ($lesson['url'] ?? false)

                                        @if($canCreateTemplates && ($lesson['url_template'] ?? false))
                                            @click="openScopeModal('{{ $lesson['url'] }}', '{{ $lesson['url_template'] }}', {{ (int) ($lesson['inline_template'] ?? 0) }})"
                                        @elseif (! empty($lesson['inline_template']))
                                            wire:click="mountAction('editLesson', { template: {{ (int) $lesson['inline_template'] }} })"
                                        @else
                                            onclick="window.location='{{ $lesson['url'] }}'"
                                        @endif

                                    @elseif ($canCreateHere)
                                        title="Klicken, um hier ein Angebot anzulegen"
                                        @if ($canCreateLesson && $canCreateRecurring)
                                            @click="openCreateModal({{ (int) $cell['room'] }}, {{ (int) $cell['time'] }})"
                                        @else
                                            wire:click="mountAction('createLesson', { room: {{ (int) $cell['room'] }}, time: {{ (int) $cell['time'] }}, recurring: {{ $canCreateRecurring ? 'true' : 'false' }} })"
                                        @endif
                                    @endif
                                >
                                    @if(isset($lesson))
                                        @if($lesson['disabled'])
                                            <small>
                                                <s>
                                                    @foreach($lesson['assigned_users'] as $userId => $userName)
                                                        {{ $userName }}@if(!$loop->last), @endif
                                                    @endforeach
                                                </s>
                                            </small>

                                            <strong><s>{!! $lesson['name'] ?? '' !!}</s></strong>
                                            <s>{!! $lesson['description'] ?? '' !!}</s>
                                        @else
                                            @php
                                                $absentUserIds = $lesson['absent_user_ids'] ?? [];
                                                $allUsersAbsent = !empty($lesson['assigned_users']) &&
                                                    count(array_intersect(array_keys($lesson['assigned_users']), $absentUserIds)) === count($lesson['assigned_users']);
                                            @endphp

                                            <small>
                                                @foreach($lesson['assigned_users'] as $userId => $userName)
                                                    @php
                                                        $isAbsent = collect($absences)->contains('id', $userId);
                                                    @endphp
                                                    @if($isAbsent)<s>@endif{{ $userName }}@if($isAbsent)</s>@endif@if(!$loop->last), @endif
                                                @endforeach
                                            </small>

                                            @if($allUsersAbsent)
                                                <strong><s>{!! $lesson['name'] ?? '' !!}</s></strong>
                                                <s>{!! $lesson['description'] ?? '' !!}</s>
                                            @else
                                                <strong>{!! $lesson['name'] ?? '' !!}</strong>
                                                {!! $lesson['description'] ?? '' !!}
                                            @endif
                                        @endif
                                    @else
                                        {!! $this->replacePlaceholders($cell['displayName'] ?? '', $this->day) !!}
                                    @endif
                                </td>

                            @endif
                        @endforeach
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        <x-filament::modal
            id="select-scope"
            icon="tabler-switch-horizontal"
            icon-color="info"
            alignment="center"
        >
            <x-slot name="heading">
                Zeitraum auswählen
            </x-slot>

            <x-slot name="description">
                <span x-text="modal.create ? 'Soll das neue Angebot nur heute oder jede Woche stattfinden?' : 'Möchtest du alle Wochen oder nur den heutigen Tag bearbeiten?'"></span>
            </x-slot>

            <div class="flex gap-3" x-show="modal.create">
                <x-filament::button
                    x-on:click="$dispatch('close-modal', { id: 'select-scope' }); $wire.mountAction('createLesson', { room: modal.room, time: modal.time, recurring: true })"
                    color="gray"
                    icon="tabler-calendar-repeat"
                    class="w-1/2">
                    Jede Woche
                </x-filament::button>
                <x-filament::button
                    x-on:click="$dispatch('close-modal', { id: 'select-scope' }); $wire.mountAction('createLesson', { room: modal.room, time: modal.time, recurring: false })"
                    icon="tabler-calendar-dot"
                    class="w-1/2">
                    Nur heute
                </x-filament::button>
            </div>

            <div class="flex gap-3" x-show="! modal.create">
                <x-filament::button
                    tag="a"
                    x-bind:href="modal.templateUrl"
                    x-show="modal.templateUrl"
                    color="gray"
                    icon="tabler-calendar-repeat"
                    class="w-1/2">
                    Alle Wochen
                </x-filament::button>
                <x-filament::button
                    x-show="modal.templateId"
                    x-on:click="$dispatch('close-modal', { id: 'select-scope' }); $wire.mountAction('editLesson', { template: modal.templateId })"
                    icon="tabler-calendar-dot"
                    class="w-1/2">
                    Nur heute
                </x-filament::button>
                <x-filament::button
                    tag="a"
                    x-show="!modal.templateId"
                    x-bind:href="modal.singleUrl"
                    icon="tabler-calendar-dot"
                    class="w-1/2">
                    Nur heute
                </x-filament::button>

            </div>
        </x-filament::modal>
    </div>

    <script>
        function mount() {
            return {
                isFullscreen: false,
                modal: {
                    create: false,
                    room: null,
                    time: null,
                    singleUrl: null,
                    templateUrl: null,
                    templateId: null,
                },
                init() {
                    document.addEventListener('fullscreenchange', () => {
                        this.isFullscreen = !!document.fullscreenElement;
                    });
                },
                toggleFullscreen() {
                    const el = document.getElementById('page-content');
                    if (!document.fullscreenElement) {
                        el.requestFullscreen();
                    } else {
                        document.exitFullscreen();
                    }
                },
                openCreateModal(room, time) {
                    this.modal.create = true;
                    this.modal.room = room;
                    this.modal.time = time;
                    this.$dispatch('open-modal', { id: 'select-scope' });
                },
                openScopeModal(singleUrl, templateUrl, templateId = 0) {
                    this.modal.create = false;
                    this.modal.singleUrl = singleUrl;
                    this.modal.templateUrl = templateUrl;
                    this.modal.templateId = templateId || null;
                    this.$dispatch('open-modal', { id: 'select-scope' });
                }
            };
        }
    </script>
</x-filament::page>
