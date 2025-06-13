<x-filament::page>
    <div class="overflow-x-auto bg-gray-50 text-gray-950 dark:bg-gray-950 dark:text-white" id="page-content">
        <table class="table-auto border-collapse border border-gray-300 dark:border-white/10 w-full">
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
                                class="border border-gray-300 dark:border-white/10 p-2 text-center align-middle @if($lesson['url'] ?? false) cursor-pointer @endif"
                                style="
                                        @if (!empty($lesson['color']))
                                            background-color: {{ $colors[$lesson['color']] }};
                                        @elseif(isset($cell['color']))
                                            background-color: {{ $colors[$cell['color']] }};
                                        @endif
                                        text-align: {{ $cell['alignment'] ?? 'left' }};
                                    "
                                @if ($lesson['url'] ?? false)
                                    onclick="window.location='{{ $lesson['url'] }}'"
                                @endif
                            >
                                @if(isset($lesson))
                                    <small>
                                        @foreach($lesson['assigned_users'] as $userName)
                                            {{ $userName }}@if(!$loop->last), @endif
                                        @endforeach
                                    </small>
                                    <strong>{!! $lesson['name'] ?? '' !!}</strong>
                                    {!! $lesson['description'] ?? '' !!}
                                @else
                                    {!! $cell['displayName'] ?? '' !!}
                                @endif

                            </td>
                        @endif
                    @endforeach
                </tr>
            @endforeach
            </tbody>
        </table>
        <div>

            <div x-data="{
                isFullscreen: false,
                toggleFullscreen() {
                    const el = document.getElementById('page-content');
                    if (!document.fullscreenElement) {
                        el.requestFullscreen();
                        this.isFullscreen = true;
                    } else {
                        document.exitFullscreen();
                        this.isFullscreen = false;
                    }
                }
            }">
                <x-filament::icon-button
                    x-show="!isFullscreen"
                    @click="toggleFullscreen"
                    icon="tabler-arrows-diagonal"
                    size="xl"
                    label="Vollbild"
                />
                <x-filament::icon-button
                    x-show="isFullscreen"
                    @click="toggleFullscreen"
                    icon="tabler-arrows-diagonal-minimize-2"
                    size="xl"
                    label="Vollbild beenden"
                />
            </div>

            <x-filament::icon-button
                x-data="{
        isFullscreen: false,
        toggleFullscreen() {
            const el = document.getElementById('page-content');
            if (!document.fullscreenElement) {
                el.requestFullscreen();
                this.isFullscreen = true;
            } else {
                document.exitFullscreen();
                this.isFullscreen = false;
            }
        }
    }"
                @click="toggleFullscreen"
                size="xl"
                label="Vollbild"
            >
                <template x-if="!isFullscreen">
                    <x-tabler-arrows-diagonal class="w-5 h-5" />
                </template>
                <template x-if="isFullscreen">
                    <x-tabler-arrows-diagonal-minimize-2 class="w-5 h-5" />
                </template>
            </x-filament::icon-button>



        </div>
    </div>
</x-filament::page>
