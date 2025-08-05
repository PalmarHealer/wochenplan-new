<style>
    #weekDays > section > div > div {
        padding: 0 !important;
    }
</style>

<x-filament-widgets::widget>
    <div id="weekDays" class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
        @foreach ($days as $day)
            <x-filament::card>
                <div class="relative cursor-pointer" @click="window.location.href='{{ $day['url'] }}'">
                    @if ($day['isToday'])
                        <div class="absolute top-0 left-0 w-full bg-primary-500 text-white text-xs font-bold px-2 py-1 rounded-t-xl">
                            Heute
                        </div>
                    @endif
                    <div class="flex items-center gap-4 p-6">

                        <x-tabler-calendar class="h-10 w-10 flex-shrink-0 text-gray-500"/>

                        <div class="flex-1 min-w-0">
                        <span class="text-lg font-semibold text-gray-900 dark:text-white">
                            {{ $day['label'] }} der {{ $day['date'] }}
                        </span>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                Mittagessen: Noch nicht eingetragen
                            </p>
                        </div>

                        <x-tabler-chevron-right class="h-6 w-6 text-gray-400 group-hover:text-primary-600 transition-colors flex-shrink-0" />
                    </div>
                </div>
            </x-filament::card>
        @endforeach
    </div>
</x-filament-widgets::widget>
