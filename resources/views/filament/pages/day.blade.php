<x-filament::page>
    <div
        id="page-content"
        x-data="fullscreenHandler()"
        x-init="init()"
        class="bg-gray-50 text-gray-950 dark:bg-gray-950 dark:text-white">

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
                                        border-color: white;
                                        border-style: solid;
                                        border-width: 0.35vh;
                                        text-align: {{ $cell['alignment'] ?? 'left' }};
                                        @if (!empty($lesson['color']))
                                            background-color: {{ $colors[$lesson['color']] }};
                                        @elseif(isset($cell['color']))
                                            background-color: {{ $colors[$cell['color']] }};
                                        @endif
                                    "
                                    @if ($lesson['url'] ?? false)
                                        onclick="window.location='{{ $lesson['url'] }}'"
                                    @endif
                                >
                                    @if(isset($lesson))
                                        @if($lesson['disabled'])
                                            <small>
                                                <s>
                                                    @foreach($lesson['assigned_users'] as $userName)
                                                        {{ $userName }}@if(!$loop->last), @endif
                                                    @endforeach
                                                </s>
                                            </small>

                                            <strong><s>{!! $lesson['name'] ?? '' !!}</s></strong>
                                            <s>{!! $lesson['description'] ?? '' !!}</s>
                                        @else
                                            <small>
                                                @foreach($lesson['assigned_users'] as $userName)
                                                    {{ $userName }}@if(!$loop->last), @endif
                                                @endforeach
                                            </small>

                                            <strong>{!! $lesson['name'] ?? '' !!}</strong>
                                            {!! $lesson['description'] ?? '' !!}
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
    </div>

    <script>
        function fullscreenHandler() {
            return {
                isFullscreen: false,
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
                }
            };
        }
    </script>
</x-filament::page>
