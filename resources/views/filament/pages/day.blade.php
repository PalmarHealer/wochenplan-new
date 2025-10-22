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
                                @endphp
                                <td
                                    @if (isset($cell['colspan']) && $cell['colspan'] > 1)
                                        colspan="{{ $cell['colspan'] }}"
                                    @endif
                                    @if (isset($cell['rowspan']) && $cell['rowspan'] > 1)
                                        rowspan="{{ $cell['rowspan'] }}"
                                    @endif
                                    class="p-2 text-center align-middle @if($lesson['url'] ?? false) cursor-pointer @endif"
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
                                    @if ($lesson['url'] ?? false)

                                        @if($canCreateTemplates && ($lesson['url_template'] ?? false))
                                            @click="openScopeModal('{{ $lesson['url'] }}', '{{ $lesson['url_template'] }}')"
                                        @else
                                            onclick="window.location='{{ $lesson['url'] }}'"
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
                                                    @if(in_array($userId, $absentUserIds))
                                                        <s>{{ $userName }}</s>
                                                    @else
                                                        {{ $userName }}
                                                    @endif
                                                    @if(!$loop->last), @endif
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
                Möchtest du alle Wochen oder nur den heutigen Tag bearbeiten.
            </x-slot>

            <div class="flex gap-3">
                <x-filament::button
                    tag="a"
                    x-bind:href="modal.templateUrl"
                    color="gray"
                    icon="tabler-calendar-repeat"
                    class="w-1/2">
                    Alle Wochen
                </x-filament::button>
                <x-filament::button
                    tag="a"
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
                    singleUrl: null,
                    templateUrl: null,
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
                openScopeModal(singleUrl, templateUrl) {
                    console.log(singleUrl);
                    console.log(templateUrl);
                    this.modal.singleUrl = singleUrl;
                    this.modal.templateUrl = templateUrl;
                    this.$dispatch('open-modal', { id: 'select-scope' });
                }
            };
        }
    </script>
</x-filament::page>
