@php
    use Illuminate\Support\Js;
    $layout = $getLayout();
    $colors = $getColors();
@endphp
<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    <div
        wire:key="layout-selector-{{ md5(json_encode($layout)) }}"
        style="overflow-y: scroll;"
        x-data="layoutEditor({
            layout: {{ Js::from($layout) }},
            colors: {{ Js::from($colors) }},
            state: $wire.entangle('{{ $getStatePath() }}'),
        })"
        x-init="init()"
    >

        <input
            type="text"
            class="hidden"
        {!! $applyStateBindingModifiers('wire:model') !!}="{{ $getStatePath() }}"
        />
        <table class="table-auto w-full border border-gray-400 text-xs bg-white dark:bg-white/5">
            <tbody>
            <template x-for="(row, rowIndex) in layout" :key="rowIndex">
                <tr class="h-10">
                    <template x-for="(cell, colIndex) in row" :key="colIndex">
                        <td
                            x-show="!cell?.hidden"
                            x-data="{ isHovered: false }"
                            class="px-2 py-1 text-xl"
                            :class="{
                                'text-center': cell.alignment === 'center',
                                'text-right': cell.alignment === 'right',
                                'text-left': !cell.alignment,
                                'cursor-pointer': cell.room && cell.time,
                            }"
                            :rowspan="cell.rowspan || 1"
                            :colspan="cell.colspan || 1"
                            style="color: black; border: 0.35vh solid white;"
                            :style="{
                                backgroundColor: colors[(cell.color ?? 'default')] ?? 'red',
                                filter: isSelected(rowIndex, colIndex) && (cell.room && cell.time) ?
                                    cell.color ? 'grayscale(20%) brightness(80%)' :
                                        'invert(50%)' :
                                        isHovered && (cell.room && cell.time) ?
                                            cell.color ? 'grayscale(10%) brightness(90%)' :
                                            'invert(70%)' :
                                        'none'
                            }"
                            @click="selectCell(rowIndex, colIndex); updateState()"
                            @mouseenter="isHovered = true"
                            @mouseleave="isHovered = false"
                        >
                            <div x-html="cell.displayName ?? ``" class="select-none"></div>
                        </td>
                    </template>
                </tr>
            </template>
            </tbody>
        </table>
    </div>

    <script>

        function layoutEditor({layout, colors, state}) {
            return {
                layout: layout,
                colors: colors,
                selectedRow: null,
                selectedCol: null,
                state: state,

                selectCell(row, col) {
                    if (row === undefined || col === undefined || row === null || col === null) return;
                    const cell = this.layout?.[row]?.[col];
                    if (!cell || !cell.room || !cell.time) return;

                    this.selectedRow = row;
                    this.selectedCol = col;
                },

                isSelected(row, col) {
                    return this.selectedRow === row && this.selectedCol === col;
                },

                updateState() {
                    if (this.selectedRow === null || this.selectedCol === null) return;

                    const cell = this.layout[this.selectedRow][this.selectedCol];

                    this.state = JSON.stringify({room: cell.room, lesson_time: cell.time});
                },

                clearSelection() {
                    this.selectedRow = null;
                    this.selectedCol = null;
                },

                init() {
                    const tryReselect = () => {
                        if (!this.state || this.state === '' || this.state === '{}' || this.state === 'null') {
                            this.clearSelection();
                            return;
                        }
                        try {
                            const parsed = JSON.parse(this.state);
                            const targetRoom = parsed?.room ?? null;
                            const targetLessonTime = parsed?.lesson_time ?? null;
                            if (!targetRoom || !targetLessonTime) {
                                this.clearSelection();
                                return;
                            }
                            const cellPath = this.findCellPath(this.layout, targetRoom, targetLessonTime);
                            if (cellPath) {
                                this.selectCell(cellPath[0], cellPath[1]);
                            } else {
                                this.clearSelection();
                            }
                        } catch (e) {
                            console.log('State could not be loaded:', e);
                            this.clearSelection();
                        }
                    };

                    // Watch for state changes and try to reselect the matching cell
                    this.$watch('state', (newValue) => {
                        if (!newValue || newValue === '' || newValue === '{}' || newValue === 'null') {
                            this.clearSelection();
                            return;
                        }
                        tryReselect();
                    });

                    // In case the layout changes dynamically and component doesn't remount
                    this.$watch('layout', () => {
                        tryReselect();
                    });

                    // Initial selection restore
                    tryReselect();
                },

                findCellPath(data, targetRoom, targetLessonTime) {
                    for (let row = 0; row < data.length; row++) {
                        const columns = data[row];
                        for (let col = 0; col < columns.length; col++) {
                            const cell = columns[col];
                            if (!cell) continue;

                            const room = cell.room;
                            const lessonTime = cell.time;

                            if (String(room) === String(targetRoom) && String(lessonTime) === String(targetLessonTime)) {
                                return [row, col];
                            }
                        }
                    }
                    return null;
                }
            }
        }
    </script>
</x-dynamic-component>
