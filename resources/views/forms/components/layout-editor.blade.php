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
        style="overflow-y: scroll;"
        x-data="layoutEditor({
        layout: {{ Js::from($layout) }},
        colors: {{ Js::from($colors) }},
        state: $wire.entangle('{{ $getStatePath() }}'),
    })"
    >
        <input
            type="text"
        {!! $applyStateBindingModifiers('wire:model') !!}="{{ $getStatePath() }}"
        />
        <table class="table-auto w-full border border-gray-400 rounded-xl text-xs bg-white dark:bg-white/5">
            <tbody>
            <template x-for="(row, rowIndex) in layout" :key="rowIndex">
                <tr>
                    <template x-for="(cell, colIndex) in row" :key="colIndex">
                        <td
                            class="border border-gray-300 dark:border-white/10 px-2 py-1 cursor-pointer"
                            :rowspan="cell.rowspan || 1"
                            :colspan="cell.colspan || 1"
                            :style="{
                                backgroundColor: colors[cell.color] || 'transparent',
                                filter: isSelected(rowIndex, colIndex) ? 'contrast(50%)' : 'none'
                            }"
                            @click="selectCell(rowIndex, colIndex); updateState()"
                        >
                            <div x-text="cell.customName ?? `${rowIndex}:${colIndex}`"></div>
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
                    this.selectedRow = row;
                    this.selectedCol = col;
                },

                isSelected(row, col) {
                    return this.selectedRow === row && this.selectedCol === col;
                },

                updateState() {
                    if (this.selectedRow === null || this.selectedCol === null) return;

                    const cell = this.layout[this.selectedRow][this.selectedCol];
                    if (!cell.attributes) {
                        this.layout[this.selectedRow][this.selectedCol].attributes = {};
                    }

                    this.state = JSON.stringify(cell.attributes);
                }

            }
        }
    </script>
</x-dynamic-component>
