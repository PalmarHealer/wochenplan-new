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
        <div class="flex flex-wrap items-end gap-2 mb-2 mt-1">
            <x-filament::button
                icon="tabler-row-insert-top"
                icon-position="after"
                color="gray"
                @click="addRowAtSelection('after')"
            >
                Zeile unten einfügen
            </x-filament::button>

            <x-filament::button
                icon="tabler-column-insert-left"
                icon-position="after"
                color="gray"
                @click="addColumnAtSelection('after')"
            >
                Spalte rechts einfügen
            </x-filament::button>
            <x-filament::button
                icon="tabler-row-insert-top"
                icon-position="after"
                color="gray"
                @click="addRowAtSelection('before')"
            >
                Zeile oben einfügen
            </x-filament::button>
            <x-filament::button
                icon="tabler-column-insert-left"
                icon-position="after"
                color="gray"
                @click="addColumnAtSelection('before')"
            >
                Spalte links einfügen
            </x-filament::button>
            <x-filament::button
                icon="tabler-row-remove"
                icon-position="after"
                color="danger"
                @click="deleteSelectedRow()"
            >
                Zeile löschen
            </x-filament::button>
            <x-filament::button
                icon="tabler-column-remove"
                icon-position="after"
                color="danger"
                @click="deleteSelectedColumn()"
            >
                Spalte löschen
            </x-filament::button>
            <x-filament::button
                icon="tabler-arrow-up"
                icon-position="after"
                color="gray"
                @click="moveSelectedRow('up')"
            >
                Zeile hoch
            </x-filament::button>
            <x-filament::button
                icon="tabler-arrow-down"
                icon-position="after"
                color="gray"
                @click="moveSelectedRow('down')"
            >
                Zeile runter
            </x-filament::button>
            <x-filament::button
                icon="tabler-arrow-left"
                icon-position="after"
                color="gray"
                @click="moveSelectedColumn('left')"
            >
                Spalte links
            </x-filament::button>
            <x-filament::button
                icon="tabler-arrow-right"
                icon-position="after"
                color="gray"
                @click="moveSelectedColumn('right')"
            >
                Spalte rechts
            </x-filament::button>
            <x-filament::button
                icon="tabler-arrows-join"
                icon-position="after"
                color="gray"
                @click="mergeCells()"
            >
                Zellen verbinden
            </x-filament::button>
            <x-filament::button
                icon="tabler-arrows-split"
                icon-position="after"
                color="gray"
                @click="splitCells()"
            >
                Zellen teilen
            </x-filament::button>


            <x-filament::button
                icon="tabler-align-left"
                icon-position="after"
                color="gray"
                @click="setAlignment('left')"
            >
                Linksbündig
            </x-filament::button>
            <x-filament::button
                icon="tabler-align-center"
                icon-position="after"
                color="gray"
                @click="setAlignment('center')"
            >
                Zentriert
            </x-filament::button>
            <x-filament::button
                icon="tabler-align-right"
                icon-position="after"
                color="gray"
                @click="setAlignment('right')"
            >
                Rechtsbündig
            </x-filament::button>
            <div class="flex items-center gap-2">
                <label class="text-xs">Spaltenbreite (px)</label>
                <input
                    type="number"
                    min="20"
                    class="fi-input block w-28 rounded-lg border-none bg-white/50 text-xs ring-1 ring-gray-950/10 dark:bg-white/5 dark:ring-white/20"
                    x-model.number="columnWidthInput"
                />
                <x-filament::button
                    size="sm"
                    color="gray"
                    @click="applyColumnWidth()"
                >
                    Setzen
                </x-filament::button>
            </div>
            <div class="flex items-center gap-2">
                <label class="text-xs">Zeilenhöhe (px)</label>
                <input
                    type="number"
                    min="20"
                    class="fi-input block w-28 rounded-lg border-none bg-white/50 text-xs ring-1 ring-gray-950/10 dark:bg-white/5 dark:ring-white/20"
                    x-model.number="rowHeightInput"
                />
                <x-filament::button
                    size="sm"
                    color="gray"
                    @click="applyRowHeight()"
                >
                    Setzen
                </x-filament::button>
            </div>
            <x-filament::button
                icon="tabler-upload"
                icon-position="after"
                color="gray"
                @click="importLayout()"
            >
                Layout importieren
            </x-filament::button>

            <x-filament::button
                icon="tabler-download"
                icon-position="after"
                color="gray"
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
            <colgroup x-show="layout.length && layout[0]?.length">
                <template x-for="(col, colIndex) in (layout[0] || [])" :key="colIndex">
                    <col :style="getColumnStyle(colIndex)">
                </template>
            </colgroup>

            <tbody>
            <template x-for="(row, rowIndex) in layout" :key="rowIndex">
                <tr :style="getRowStyle(rowIndex)">
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
                rowHeightInput: null,
                columnWidthInput: null,

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

                    this.refreshSizeInputsFromSelection();

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
                        this.refreshSizeInputsFromSelection();

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
                    this.refreshSizeInputsFromSelection();
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

                createCell() {
                    return {
                        displayName: ``,
                        alignment: null,
                        color: null,
                        room: null,
                        time: null,
                    };
                },

                ensureLayoutExists() {
                    if (this.layout.length) return;
                    this.layout = [[this.createCell()]];
                },

                getColumnStyle(colIndex) {
                    const width = this.getColumnWidth(colIndex);
                    return width ? `width: ${width}px;` : '';
                },

                getRowStyle(rowIndex) {
                    const height = this.getRowHeight(rowIndex);
                    return height ? `height: ${height}px; min-height: ${height}px;` : '';
                },

                getActiveCellCoordinates() {
                    if (!this.selectedCells.length) return null;
                    const [row, col] = this.selectedCells[0];
                    const cell = this.layout[row]?.[col];
                    if (!cell) return null;
                    if (cell.hidden && Array.isArray(cell.mergedTo)) return cell.mergedTo;

                    return [row, col];
                },

                getColumnWidth(colIndex) {
                    if (colIndex < 0) return null;
                    for (let row = 0; row < this.layout.length; row++) {
                        const value = Number(this.layout[row]?.[colIndex]?.colWidth);
                        if (Number.isFinite(value) && value > 0) return value;
                    }

                    return null;
                },

                getRowHeight(rowIndex) {
                    if (rowIndex < 0 || rowIndex >= this.layout.length) return null;
                    for (let col = 0; col < (this.layout[rowIndex]?.length ?? 0); col++) {
                        const value = Number(this.layout[rowIndex]?.[col]?.rowHeight);
                        if (Number.isFinite(value) && value > 0) return value;
                    }

                    return null;
                },

                setColumnWidth(colIndex, width) {
                    if (colIndex < 0) return;
                    const normalized = Number(width);
                    const value = Number.isFinite(normalized) && normalized > 0 ? Math.round(normalized) : null;

                    for (let row = 0; row < this.layout.length; row++) {
                        if (!this.layout[row]?.[colIndex]) continue;
                        if (value === null) delete this.layout[row][colIndex].colWidth;
                        else this.layout[row][colIndex].colWidth = value;
                    }
                },

                setRowHeight(rowIndex, height) {
                    if (rowIndex < 0 || rowIndex >= this.layout.length) return;
                    const normalized = Number(height);
                    const value = Number.isFinite(normalized) && normalized > 0 ? Math.round(normalized) : null;

                    for (let col = 0; col < this.layout[rowIndex].length; col++) {
                        if (!this.layout[rowIndex]?.[col]) continue;
                        if (value === null) delete this.layout[rowIndex][col].rowHeight;
                        else this.layout[rowIndex][col].rowHeight = value;
                    }
                },

                applyColumnWidth() {
                    const active = this.getActiveCellCoordinates();
                    if (!active) return;
                    const colIndex = active[1];
                    this.setColumnWidth(colIndex, this.columnWidthInput);
                    this.updateState();
                },

                applyRowHeight() {
                    const active = this.getActiveCellCoordinates();
                    if (!active) return;
                    const rowIndex = active[0];
                    this.setRowHeight(rowIndex, this.rowHeightInput);
                    this.updateState();
                },

                refreshSizeInputsFromSelection() {
                    const active = this.getActiveCellCoordinates();
                    if (!active) return;

                    this.columnWidthInput = this.getColumnWidth(active[1]);
                    this.rowHeightInput = this.getRowHeight(active[0]);
                },

                getMergeAnchors() {
                    const anchors = [];
                    for (let row = 0; row < this.layout.length; row++) {
                        for (let col = 0; col < (this.layout[row]?.length ?? 0); col++) {
                            const cell = this.layout[row]?.[col];
                            if (!cell || cell.hidden) continue;
                            const rowspan = Math.max(1, Number(cell.rowspan) || 1);
                            const colspan = Math.max(1, Number(cell.colspan) || 1);
                            if (rowspan > 1 || colspan > 1) {
                                anchors.push({ row, col, rowspan, colspan });
                            }
                        }
                    }

                    return anchors;
                },

                clearMergeFlags() {
                    for (let row = 0; row < this.layout.length; row++) {
                        for (let col = 0; col < (this.layout[row]?.length ?? 0); col++) {
                            const cell = this.layout[row]?.[col];
                            if (!cell) continue;
                            delete cell.hidden;
                            delete cell.mergedTo;
                            delete cell.rowspan;
                            delete cell.colspan;
                        }
                    }
                },

                applyAnchorsAndRebuild(anchors) {
                    this.clearMergeFlags();
                    const rowCount = this.layout.length;
                    const colCount = this.layout[0]?.length ?? 0;

                    for (const anchor of anchors) {
                        if (anchor.row < 0 || anchor.col < 0 || anchor.row >= rowCount || anchor.col >= colCount) continue;
                        const cell = this.layout[anchor.row]?.[anchor.col];
                        if (!cell) continue;

                        const maxRowspan = Math.max(1, Math.min(anchor.rowspan, rowCount - anchor.row));
                        const maxColspan = Math.max(1, Math.min(anchor.colspan, colCount - anchor.col));

                        if (maxRowspan > 1) cell.rowspan = maxRowspan;
                        if (maxColspan > 1) cell.colspan = maxColspan;
                    }

                    for (let row = 0; row < rowCount; row++) {
                        for (let col = 0; col < colCount; col++) {
                            const cell = this.layout[row]?.[col];
                            if (!cell || cell.hidden) continue;
                            const rowspan = Math.max(1, Number(cell.rowspan) || 1);
                            const colspan = Math.max(1, Number(cell.colspan) || 1);
                            if (rowspan === 1 && colspan === 1) continue;

                            for (let r = row; r < row + rowspan; r++) {
                                for (let c = col; c < col + colspan; c++) {
                                    if (r === row && c === col) continue;
                                    const target = this.layout[r]?.[c];
                                    if (!target) continue;
                                    target.hidden = true;
                                    target.mergedTo = [row, col];
                                }
                            }
                        }
                    }
                },

                addRowAtSelection(position = 'after') {
                    this.ensureLayoutExists();
                    const columnCount = this.layout[0]?.length || 1;

                    const active = this.getActiveCellCoordinates();
                    const selectedRow = active ? active[0] : this.layout.length - 1;
                    const insertIndex = position === 'before' ? selectedRow : selectedRow + 1;

                    const anchors = this.getMergeAnchors().map(anchor => {
                        if (anchor.row >= insertIndex) anchor.row += 1;
                        else if (anchor.row + anchor.rowspan - 1 >= insertIndex) anchor.rowspan += 1;

                        return anchor;
                    });

                    const newRow = [];
                    for (let col = 0; col < columnCount; col++) {
                        const cell = this.createCell();
                        const inheritedWidth = this.getColumnWidth(col);
                        if (inheritedWidth) cell.colWidth = inheritedWidth;
                        newRow.push(cell);
                    }

                    this.layout.splice(insertIndex, 0, newRow);
                    this.applyAnchorsAndRebuild(anchors);
                    this.clearSelection();
                    this.selectSingleCell(insertIndex, 0);
                    this.updateState();
                },

                addColumnAtSelection(position = 'after') {
                    this.ensureLayoutExists();
                    const rowCount = this.layout.length;
                    const active = this.getActiveCellCoordinates();
                    const selectedCol = active ? active[1] : (this.layout[0]?.length ?? 1) - 1;
                    const insertIndex = position === 'before' ? selectedCol : selectedCol + 1;

                    const anchors = this.getMergeAnchors().map(anchor => {
                        if (anchor.col >= insertIndex) anchor.col += 1;
                        else if (anchor.col + anchor.colspan - 1 >= insertIndex) anchor.colspan += 1;

                        return anchor;
                    });

                    for (let row = 0; row < rowCount; row++) {
                        if (!this.layout[row]) this.layout[row] = [];
                        const cell = this.createCell();
                        const inheritedHeight = this.getRowHeight(row);
                        if (inheritedHeight) cell.rowHeight = inheritedHeight;
                        this.layout[row].splice(insertIndex, 0, cell);
                    }

                    this.applyAnchorsAndRebuild(anchors);
                    this.clearSelection();
                    this.selectSingleCell(0, insertIndex);
                    this.updateState();
                },

                deleteSelectedRow() {
                    if (!this.layout.length) return;
                    const active = this.getActiveCellCoordinates();
                    const deleteRow = active ? active[0] : this.layout.length - 1;
                    if (this.layout.length <= 1) return;

                    const anchors = [];
                    for (const anchor of this.getMergeAnchors()) {
                        const endRow = anchor.row + anchor.rowspan - 1;
                        if (anchor.row === deleteRow) continue;
                        if (anchor.row > deleteRow) anchor.row -= 1;
                        else if (anchor.row < deleteRow && deleteRow <= endRow) {
                            anchor.rowspan -= 1;
                        }
                        if (anchor.rowspan > 1 || anchor.colspan > 1) anchors.push(anchor);
                    }

                    this.layout.splice(deleteRow, 1);
                    this.applyAnchorsAndRebuild(anchors);
                    const rowAfterDelete = Math.max(0, deleteRow - 1);
                    this.clearSelection();
                    this.selectSingleCell(rowAfterDelete, 0);
                    this.updateState();
                },

                deleteSelectedColumn() {
                    const colCount = this.layout[0]?.length ?? 0;
                    if (colCount <= 1) return;
                    const active = this.getActiveCellCoordinates();
                    const deleteCol = active ? active[1] : colCount - 1;

                    const anchors = [];
                    for (const anchor of this.getMergeAnchors()) {
                        const endCol = anchor.col + anchor.colspan - 1;
                        if (anchor.col === deleteCol) continue;
                        if (anchor.col > deleteCol) anchor.col -= 1;
                        else if (anchor.col < deleteCol && deleteCol <= endCol) {
                            anchor.colspan -= 1;
                        }
                        if (anchor.rowspan > 1 || anchor.colspan > 1) anchors.push(anchor);
                    }

                    for (let row = 0; row < this.layout.length; row++) {
                        this.layout[row].splice(deleteCol, 1);
                    }

                    this.applyAnchorsAndRebuild(anchors);
                    const colAfterDelete = Math.max(0, deleteCol - 1);
                    this.clearSelection();
                    this.selectSingleCell(0, colAfterDelete);
                    this.updateState();
                },

                moveSelectedRow(direction) {
                    if (this.layout.length < 2) return;
                    const active = this.getActiveCellCoordinates();
                    if (!active) return;
                    const from = active[0];
                    const to = direction === 'up' ? from - 1 : from + 1;
                    if (to < 0 || to >= this.layout.length) return;

                    const anchors = this.getMergeAnchors().filter(anchor => {
                        const end = anchor.row + anchor.rowspan - 1;
                        const intersectsFrom = from >= anchor.row && from <= end;
                        const intersectsTo = to >= anchor.row && to <= end;
                        if (anchor.rowspan > 1 && (intersectsFrom || intersectsTo)) return false;

                        return true;
                    }).map(anchor => {
                        if (anchor.row === from) anchor.row = to;
                        else if (anchor.row === to) anchor.row = from;
                        return anchor;
                    });

                    const rowA = this.layout[from];
                    this.layout[from] = this.layout[to];
                    this.layout[to] = rowA;
                    this.applyAnchorsAndRebuild(anchors);
                    this.clearSelection();
                    this.selectSingleCell(to, active[1]);
                    this.updateState();
                },

                moveSelectedColumn(direction) {
                    const colCount = this.layout[0]?.length ?? 0;
                    if (colCount < 2) return;
                    const active = this.getActiveCellCoordinates();
                    if (!active) return;
                    const from = active[1];
                    const to = direction === 'left' ? from - 1 : from + 1;
                    if (to < 0 || to >= colCount) return;

                    const anchors = this.getMergeAnchors().filter(anchor => {
                        const end = anchor.col + anchor.colspan - 1;
                        const intersectsFrom = from >= anchor.col && from <= end;
                        const intersectsTo = to >= anchor.col && to <= end;
                        if (anchor.colspan > 1 && (intersectsFrom || intersectsTo)) return false;

                        return true;
                    }).map(anchor => {
                        if (anchor.col === from) anchor.col = to;
                        else if (anchor.col === to) anchor.col = from;
                        return anchor;
                    });

                    for (let row = 0; row < this.layout.length; row++) {
                        const rowData = this.layout[row];
                        const colA = rowData[from];
                        rowData[from] = rowData[to];
                        rowData[to] = colA;
                    }
                    this.applyAnchorsAndRebuild(anchors);
                    this.clearSelection();
                    this.selectSingleCell(active[0], to);
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
