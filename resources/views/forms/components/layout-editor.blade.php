@php
    use Illuminate\Support\Js;
    $colors = $getColors();
@endphp
<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    <div
        style="overflow-y: scroll;"
        x-data="layoutEditor({
            colors: {{ Js::from($colors) }},
            selectedColor: $wire.entangle('data.color'),
            selectedRoom: $wire.entangle('data.room'),
            selectedTime: $wire.entangle('data.time'),
            content: $wire.entangle('data.cellContent'),
            state: $wire.entangle('{{ $getStatePath() }}'),
        })"
        x-effect="handleUpdates()"
        x-init="init()"
    >
        <div class="flex flex-wrap gap-2 mb-2 mt-1">
            <x-filament::button
                icon="tabler-row-insert-top"
                icon-position="after"
                @click="addRow()"
            >
                Zeile einfügen
            </x-filament::button>

            <x-filament::button
                icon="tabler-column-insert-left"
                icon-position="after"
                @click="addColumn()"
            >
                Spalte einfügen
            </x-filament::button>
            <x-filament::button
                icon="tabler-arrows-join"
                icon-position="after"
                @click="mergeCells()"
            >
                Zellen verbinden
            </x-filament::button>
            <x-filament::button
                icon="tabler-arrows-split"
                icon-position="after"
                @click="splitCells()"
            >
                Zellen teilen
            </x-filament::button>


            <x-filament::button
                icon="tabler-align-left"
                icon-position="after"
                @click="setAlignment('left')"
            >
                Linksbündig
            </x-filament::button>
            <x-filament::button
                icon="tabler-align-center"
                icon-position="after"
                @click="setAlignment('center')"
            >
                Zentriert
            </x-filament::button>
            <x-filament::button
                icon="tabler-align-right"
                icon-position="after"
                @click="setAlignment('right')"
            >
                Rechtsbündig
            </x-filament::button>
            <x-filament::button
                icon="tabler-upload"
                icon-position="after"
                @click="importLayout()"
            >
                Layout importieren
            </x-filament::button>

            <x-filament::button
                icon="tabler-download"
                icon-position="after"
                @click="exportLayout()"
            >
                Layout exportieren
            </x-filament::button>
        </div>

        <input
            type="text"
            class="hidden"
        {!! $applyStateBindingModifiers('wire:model') !!}="{{ $getStatePath() }}"
        />
        <div
            x-ref="focusTarget"
            tabindex="-1"
            style="
                width: 0;
                height: 0;
            "
        ></div>
        <table
            class="table-auto w-full border border-gray-400 text-xs bg-white dark:bg-white/5"
            @mouseleave="endSelection(false)">
            <colgroup>
                <template x-for="(col, colIndex) in layout[0]" :key="colIndex">
                    <col :style="colIndex === 1 ? 'max-width: 20%; width: 20%;' : ''">
                </template>
            </colgroup>

            <tbody>
            <template x-for="(row, rowIndex) in layout" :key="rowIndex">
                <tr class="h-10">
                    <template x-for="(cell, colIndex) in row" :key="colIndex">
                        <td
                            x-show="!cell?.hidden"
                            x-data="{ isHovered: false }"
                            class="border border-gray-300 dark:border-white/10 px-2 py-1 cursor-pointer text-xl"
                            :class="{
                                'text-center': cell.alignment === 'center',
                                'text-right': cell.alignment === 'right',
                                'text-left': !cell.alignment,
                            }"
                            :rowspan="cell.rowspan || 1"
                            :colspan="cell.colspan || 1"
                            :style="{
                                backgroundColor: colors[(cell.color ?? 'default')] ?? 'red',
                                filter: isSelected(rowIndex, colIndex) ?
                                    cell.color ? 'grayscale(20%) brightness(80%)' :
                                        'invert(50%)' :
                                        isHovered ?
                                            cell.color ? 'grayscale(10%) brightness(90%)' :
                                            'invert(70%)' :
                                        'none'
                            }"
                            @mouseenter="isHovered = true"
                            @mouseleave="isHovered = false"
                            @mousedown.prevent="startSelection(rowIndex, colIndex)"
                            @mouseover="continueSelection(rowIndex, colIndex)"
                            @mouseup="endSelection()"
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
        function layoutEditor({ colors, selectedColor, selectedRoom, selectedTime, content, state }) {
            return {
                colors,
                selectedColor,
                selectedRoom,
                selectedTime,
                content,
                state,
                layout: [],
                selectedCells: [],
                selecting: false,
                startRow: null,
                startCol: null,
                selectionSummary: '',
                oldContent: null,
                oldSelectedColor: null,
                oldSelectedRoom: null,
                oldSelectedTime: null,

                importLayout() {
                    const json = prompt("Layout-JSON einfügen:");
                    try {
                        this.state = JSON.parse(json);
                    } catch (e) {
                        alert("Ungültiges JSON");
                    }
                    this.init();
                },
                exportLayout() {
                    const json = JSON.stringify(this.state, null, 2);
                    navigator.clipboard.writeText(json)
                        .then(() => alert("Layout kopiert!"))
                        .catch(() => alert("Fehler beim Kopieren."));
                },

                init() {
                    const parsedArray = JSON.parse(this.state);
                    if (parsedArray == null || this.state === '{}' || parsedArray.length < 1) return;

                    try {
                        this.layout = parsedArray;
                    } catch (e) {
                        console.log('Layout could not be loaded:', parsedArray);
                    }

                },

                handleUpdates() {
                    if (!this.selectedCells.length || this.selecting) return;

                    if (
                        this.content === this.oldContent &&
                        this.selectedRoom === this.oldSelectedRoom &&
                        this.selectedTime === this.oldSelectedTime &&
                        this.selectedColor === this.oldSelectedColor
                    ) return;

                    for (const [row, col] of this.selectedCells) {
                        const cell = this.layout[row][col];
                        if (!cell) continue;

                        if (this.content !== this.oldContent) cell.displayName = this.content ?? '';

                        if (this.selectedColor !== this.oldSelectedColor) {
                            if (!this.selectedColor) cell.color = null;
                            else cell.color = parseInt(this.selectedColor);
                        }

                        if (this.selectedRoom !== this.oldSelectedRoom) cell.room = parseInt(this.selectedRoom);
                        if (this.selectedTime !== this.oldSelectedTime) cell.time = parseInt(this.selectedTime);

                    }

                    this.oldContent = this.content;
                    this.oldSelectedRoom = this.selectedRoom;
                    this.oldSelectedTime = this.selectedTime;
                    this.oldSelectedColor = this.selectedColor;

                    this.updateState();
                },


                startSelection(row, col) {
                    this.selecting = true;
                    this.focusTable();
                    this.clearSelection();
                    this.startRow = row;
                    this.startCol = col;
                    this.selectRange(row, col);
                },

                continueSelection(row, col) {
                    if (!this.selecting) return;
                    this.selectRange(row, col);
                },

                endSelection(focusTable = true) {
                    this.selecting = false;


                    if (this.selectedCells.length === 1) {
                        const cell = this.layout[this.selectedCells[0][0]][this.selectedCells[0][1]];
                        if (!cell) return;

                        this.content = cell.displayName ?? null;
                        this.selectedColor = cell.color;
                        this.selectedRoom = cell.room;
                        this.selectedTime = cell.time;

                        if (focusTable) this.focusTable();
                    }

                    this.oldContent = this.content;
                    this.oldSelectedColor = this.selectedColor;
                    this.oldSelectedRoom = this.selectedRoom;
                    this.oldSelectedTime = this.selectedTime;
                },

                selectRange(endRow, endCol) {
                    this.clearSelection();
                    const minRow = Math.min(this.startRow, endRow);
                    const maxRow = Math.max(this.startRow, endRow);
                    const minCol = Math.min(this.startCol, endCol);
                    const maxCol = Math.max(this.startCol, endCol);

                    for (let row = minRow; row <= maxRow; row++) {
                        for (let col = minCol; col <= maxCol; col++) {
                            this.selectedCells.push([row, col]);
                        }
                    }
                },

                clearSelection() {
                    this.selectedCells = [];
                    this.content = null;
                    this.selectedRoom = null;
                    this.selectedTime = null;
                    this.selectedColor = null;
                },

                isSelected(row, col) {
                    return this.selectedCells.some(([r, c]) => r === row && c === col);
                },

                selectSingleCell(row, col) {
                    this.selectedCells = [[row, col]];
                },

                updateState() {
                    if (this.layout.length < 1) {
                        this.state = '{}';
                        return;
                    }
                    this.state = JSON.stringify(this.layout);
                },

                inArray(needle, haystack) {
                    return haystack.some(cell =>
                        cell.length === needle.length &&
                        cell.every((value, index) => value === needle[index])
                    );
                },

                focusTable() {
                    this.$refs.focusTarget?.focus();
                    setTimeout(() => {
                        this.$refs.focusTarget?.focus();
                    }, 10);
                },

                setAlignment(alignment) {
                    if (!this.selectedCells.length) return;

                    for (const [row, col] of this.selectedCells) {
                        const cell = this.layout[row][col];
                        if (!cell) continue;
                        if (alignment === 'left') delete this.layout[row][col].alignment;
                        else cell.alignment = alignment;
                    }

                    this.updateState();
                },

                addRow() {
                    const columnCount = this.layout[0]?.length || 1;
                    const newRow = [];

                    for (let col = 0; col < columnCount; col++) {
                        newRow.push({
                            displayName: ``,
                            alignment: null,
                            color: null,
                            room: null,
                            time: null,
                        });
                    }

                    this.layout.push(newRow);
                    this.updateState();
                },

                addColumn() {
                    for (let row = 0; row < this.layout.length; row++) {
                        this.layout[row].push({
                            displayName: ``,
                            alignment: null,
                            color: null,
                            room: null,
                            time: null,
                        });
                    }
                    this.updateState();
                },

                mergeCells() {
                    if (this.selectedCells.length <= 1) return;

                    for (let i = 0; i < this.layout.length; i++) {
                        for (let j = 0; j < this.layout[i].length; j++) {
                            const cell = this.layout[i][j];
                            if (!cell || !this.isSelected(i, j)) continue;

                            const rowSpan = cell.rowspan || 1;
                            const colSpan = cell.colspan || 1;

                            for (let r = 0; r < rowSpan; r++) {
                                for (let c = 0; c < colSpan; c++) {
                                    const rowIndex = i + r;
                                    const colIndex = j + c;

                                    if (this.layout[rowIndex][colIndex]['hidden']) {
                                        if (!this.inArray(this.layout[rowIndex][colIndex]['mergedTo'], this.selectedCells)) return;
                                    }

                                    if (rowIndex === i && colIndex === j) {
                                        cell.rowspan = 1;
                                        cell.colspan = 1;
                                    } else {
                                        if (!this.layout[rowIndex]) this.layout[rowIndex] = [];
                                        delete this.layout[rowIndex][colIndex].hidden;
                                        delete this.layout[rowIndex][colIndex].mergedTo;
                                    }
                                }
                            }
                        }
                    }

                    // Get first cell index
                    const rows = this.selectedCells.map(([r]) => r);
                    const cols = this.selectedCells.map(([, c]) => c);
                    const minRow = Math.min(...rows);
                    const maxRow = Math.max(...rows);
                    const minCol = Math.min(...cols);
                    const maxCol = Math.max(...cols);

                    const rowSpan = maxRow - minRow + 1;
                    const colSpan = maxCol - minCol + 1;

                    // Empty all cells except first
                    for (let row = minRow; row <= maxRow; row++) {
                        for (let col = minCol; col <= maxCol; col++) {
                            if (row === minRow && col === minCol) {
                                this.layout[row][col].rowspan = rowSpan;
                                this.layout[row][col].colspan = colSpan;
                            } else {
                                this.layout[row][col].hidden = true;
                                this.layout[row][col].mergedTo = [minRow, minCol];
                            }
                        }
                    }

                    this.clearSelection();
                    this.selectSingleCell(minRow, minCol);
                    this.updateState();
                },

                splitCells() {
                    for (let i = 0; i < this.layout.length; i++) {
                        for (let j = 0; j < this.layout[i].length; j++) {
                            const cell = this.layout[i][j];
                            if (!cell || !this.isSelected(i, j)) continue;

                            const rowSpan = cell.rowspan || 1;
                            const colSpan = cell.colspan || 1;

                            for (let r = 0; r < rowSpan; r++) {
                                for (let c = 0; c < colSpan; c++) {
                                    const rowIndex = i + r;
                                    const colIndex = j + c;
                                    if (rowIndex === i && colIndex === j) {
                                        cell.rowspan = 1;
                                        cell.colspan = 1;
                                    } else {
                                        if (!this.layout[rowIndex]) this.layout[rowIndex] = [];
                                        delete this.layout[rowIndex][colIndex].hidden;
                                        delete this.layout[rowIndex][colIndex].mergedTo;
                                    }
                                }
                            }
                        }
                    }

                    this.clearSelection();
                    this.updateState();
                },

            }
        }
    </script>
</x-dynamic-component>
