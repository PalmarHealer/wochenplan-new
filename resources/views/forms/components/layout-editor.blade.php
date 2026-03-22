@php
    use Illuminate\Support\Js;
    $colors = $getColors();
@endphp
<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    <div
        style="overflow: auto;"
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
        @mousemove.window="onGlobalMouseMove($event)"
        @mouseup.window="onGlobalMouseUp($event)"
    >
        <div>

        {{-- Toolbar --}}
        <div class="fi-layout-editor-toolbar">
            {{-- Row operations --}}
            <button type="button" class="fi-toolbar-btn" @click="addRowAtSelection('before')" title="Zeile oben einfügen">
                <x-filament::icon icon="tabler-row-insert-top" class="h-5 w-5" />
            </button>
            <button type="button" class="fi-toolbar-btn" @click="addRowAtSelection('after')" title="Zeile unten einfügen">
                <x-filament::icon icon="tabler-row-insert-bottom" class="h-5 w-5" />
            </button>
            <button type="button" class="fi-toolbar-btn fi-toolbar-btn-danger" @click="deleteSelectedRow()" title="Zeile löschen">
                <x-filament::icon icon="tabler-row-remove" class="h-5 w-5" />
            </button>

            <div class="w-px h-6 bg-gray-300 dark:bg-white/10 mx-1 self-center"></div>

            {{-- Column operations --}}
            <button type="button" class="fi-toolbar-btn" @click="addColumnAtSelection('before')" title="Spalte links einfügen">
                <x-filament::icon icon="tabler-column-insert-left" class="h-5 w-5" />
            </button>
            <button type="button" class="fi-toolbar-btn" @click="addColumnAtSelection('after')" title="Spalte rechts einfügen">
                <x-filament::icon icon="tabler-column-insert-right" class="h-5 w-5" />
            </button>
            <button type="button" class="fi-toolbar-btn fi-toolbar-btn-danger" @click="deleteSelectedColumn()" title="Spalte löschen">
                <x-filament::icon icon="tabler-column-remove" class="h-5 w-5" />
            </button>

            <div class="w-px h-6 bg-gray-300 dark:bg-white/10 mx-1 self-center"></div>

            {{-- Merge / Split --}}
            <button type="button" class="fi-toolbar-btn" @click="mergeCells()" title="Zellen verbinden">
                <x-filament::icon icon="tabler-arrows-join" class="h-5 w-5" />
            </button>
            <button type="button" class="fi-toolbar-btn" @click="splitCells()" title="Zellen teilen">
                <x-filament::icon icon="tabler-arrows-split" class="h-5 w-5" />
            </button>

            <div class="w-px h-6 bg-gray-300 dark:bg-white/10 mx-1 self-center"></div>

            {{-- Alignment --}}
            <button type="button" class="fi-toolbar-btn" :class="{ 'fi-toolbar-btn-active': selectedCells.length && (getActiveAlignment() === 'left' || getActiveAlignment() === null) }" @click="setAlignment('left')" title="Linksbündig">
                <x-filament::icon icon="tabler-align-left" class="h-5 w-5" />
            </button>
            <button type="button" class="fi-toolbar-btn" :class="{ 'fi-toolbar-btn-active': selectedCells.length && getActiveAlignment() === 'center' }" @click="setAlignment('center')" title="Zentriert">
                <x-filament::icon icon="tabler-align-center" class="h-5 w-5" />
            </button>
            <button type="button" class="fi-toolbar-btn" :class="{ 'fi-toolbar-btn-active': selectedCells.length && getActiveAlignment() === 'right' }" @click="setAlignment('right')" title="Rechtsbündig">
                <x-filament::icon icon="tabler-align-right" class="h-5 w-5" />
            </button>

            <div class="w-px h-6 bg-gray-300 dark:bg-white/10 mx-1 self-center"></div>

            {{-- Import / Export --}}
            <button type="button" class="fi-toolbar-btn" @click="importLayout()" title="Layout importieren">
                <x-filament::icon icon="tabler-upload" class="h-5 w-5" />
            </button>
            <button type="button" class="fi-toolbar-btn" @click="exportLayout()" title="Layout exportieren">
                <x-filament::icon icon="tabler-download" class="h-5 w-5" />
            </button>
        </div>

        <style>
            .fi-layout-editor-table-wrap {
                border: 1px solid rgb(0 0 0 / 0.1);
                border-top: none;
                border-radius: 0 0 0.5rem 0.5rem;
                overflow: hidden;
            }
            :is(.dark .fi-layout-editor-table-wrap) {
                border-color: rgb(255 255 255 / 0.2);
            }
            .fi-layout-editor-table-wrap table {
                width: 100%;
                border-collapse: collapse;
                font-size: 0.75rem;
            }
            .fi-layout-editor-toolbar {
                display: flex;
                align-items: center;
                gap: 0.25rem;
                flex-wrap: wrap;
                padding: 0.5rem 0.625rem;
                border: 1px solid rgb(0 0 0 / 0.1);
                border-bottom: none;
                border-radius: 0.5rem 0.5rem 0 0;
                background: white;
            }
            :is(.dark .fi-layout-editor-toolbar) {
                border-color: rgb(255 255 255 / 0.2);
                background: rgb(255 255 255 / 0.05);
            }
            .fi-toolbar-btn {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 2rem;
                height: 2rem;
                border-radius: 0.375rem;
                color: rgb(156 163 175);
                transition: all 150ms;
            }
            .fi-toolbar-btn:hover {
                background: rgb(229 231 235);
                color: rgb(55 65 81);
            }
            :is(.dark .fi-toolbar-btn) {
                color: rgb(156 163 175);
            }
            :is(.dark .fi-toolbar-btn:hover) {
                background: rgba(255 255 255 / 0.1);
                color: rgb(229 231 235);
            }
            .fi-toolbar-btn-active {
                background: rgb(229 231 235);
                color: rgb(37 99 235) !important;
            }
            .fi-toolbar-btn-active:hover {
                background: rgb(229 231 235);
                color: rgb(37 99 235) !important;
            }
            :is(.dark .fi-toolbar-btn-active) {
                background: rgba(255 255 255 / 0.1);
                color: rgb(96 165 250) !important;
            }
            :is(.dark .fi-toolbar-btn-active:hover) {
                background: rgba(255 255 255 / 0.1);
                color: rgb(96 165 250) !important;
            }
            .fi-toolbar-btn-danger {
                color: rgb(220 38 38) !important;
            }
            .fi-toolbar-btn-danger:hover {
                background: rgb(229 231 235);
            }
            :is(.dark .fi-toolbar-btn-danger) {
                color: rgb(248 113 113) !important;
            }
            :is(.dark .fi-toolbar-btn-danger:hover) {
                background: rgba(255 255 255 / 0.1);
            }
        </style>

        <input
            type="text"
            class="hidden"
        {!! $applyStateBindingModifiers('wire:model') !!}="{{ $getStatePath() }}"
        />
        <div
            x-ref="focusTarget"
            tabindex="-1"
            style="width: 0; height: 0;"
        ></div>

        {{-- Table with spreadsheet-style headers --}}
        <div class="fi-layout-editor-table-wrap">
        <table
            x-ref="layoutTable"
            @mouseleave="endSelection(false)"
        >
            <colgroup x-show="layout.length && layout[0]?.length">
                <col style="width: 32px; min-width: 32px;">
                <template x-for="(col, colIndex) in (layout[0] || [])" :key="'cg-' + colIndex">
                    <col :style="getColumnStyle(colIndex)">
                </template>
            </colgroup>

            {{-- Column headers --}}
            <thead x-show="layout.length && layout[0]?.length">
                <tr>
                    {{-- Corner cell --}}
                    <th class="border border-gray-300 dark:border-white/10 bg-gray-100 dark:bg-white/10"
                        style="width: 32px; height: 24px; min-width: 32px;"></th>

                    <template x-for="(col, colIndex) in (layout[0] || [])" :key="'ch-' + colIndex">
                        <th class="border border-gray-300 dark:border-white/10 bg-gray-100 dark:bg-white/10 select-none p-0"
                            style="height: 24px;"
                            :class="{
                                'bg-blue-100 dark:bg-blue-900/30': dragTarget?.type === 'col' && dragTarget?.index === colIndex && dragging?.fromIndex !== colIndex,
                                'opacity-50': dragging?.type === 'col' && dragging?.fromIndex === colIndex,
                            }"
                            @mouseover="if (dragging?.type === 'col') dragTarget = { type: 'col', index: colIndex }"
                            @mousemove="updateHeaderCursor($event, 'col')"
                            @mousedown.prevent="handleHeaderMouseDown($event, 'col', colIndex)"
                        >
                            <span x-text="getColumnLabel(colIndex)"
                                  class="text-[10px] font-medium text-gray-400 dark:text-gray-500 pointer-events-none"></span>
                        </th>
                    </template>
                </tr>
            </thead>

            <tbody>
            <template x-for="(row, rowIndex) in layout" :key="'r-' + rowIndex">
                <tr :style="getRowStyle(rowIndex)">
                    {{-- Row header --}}
                    <td class="border border-gray-300 dark:border-white/10 bg-gray-100 dark:bg-white/10 select-none p-0 text-center"
                        style="width: 32px; min-width: 32px;"
                        :class="{
                            'bg-blue-100 dark:bg-blue-900/30': dragTarget?.type === 'row' && dragTarget?.index === rowIndex && dragging?.fromIndex !== rowIndex,
                            'opacity-50': dragging?.type === 'row' && dragging?.fromIndex === rowIndex,
                        }"
                        :style="{ cursor: resizing ? (resizing.type === 'row' ? 'row-resize' : '') : '' }"
                        @mouseover="if (dragging?.type === 'row') dragTarget = { type: 'row', index: rowIndex }"
                        @mousemove="updateHeaderCursor($event, 'row')"
                        @mousedown.prevent="handleHeaderMouseDown($event, 'row', rowIndex)"
                    >
                        <span x-text="rowIndex + 1"
                              class="text-[10px] font-medium text-gray-400 dark:text-gray-500 pointer-events-none"></span>
                    </td>

                    {{-- Data cells --}}
                    <template x-for="(cell, colIndex) in row" :key="'c-' + colIndex">
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
                                filter: (dragging || dragPending || resizing) ? 'none' :
                                    isSelected(rowIndex, colIndex) ?
                                        cell.color ? 'grayscale(20%) brightness(80%)' :
                                            'invert(50%)' :
                                        isHovered ?
                                            cell.color ? 'grayscale(10%) brightness(90%)' :
                                            'invert(70%)' :
                                        'none'
                            }"
                            @mouseenter="if (!dragging && !dragPending && !resizing) isHovered = true"
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
        </div> {{-- end table-wrap --}}

        </div>
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

                // Header drag & resize state
                resizing: null,
                dragging: null,
                dragTarget: null,
                dragPending: null,

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
                    if (parsedArray == null || this.state === '{}' || parsedArray.length < 1) {
                        this.createDefaultLayout(4, 4);
                        this.updateState();
                        return;
                    }

                    try {
                        this.layout = parsedArray;
                    } catch (e) {
                        console.log('Layout could not be loaded:', parsedArray);
                    }

                    this.refreshSizeInputsFromSelection();

                },

                createDefaultLayout(rows, cols) {
                    const defaultRowHeight = 90;
                    this.layout = [];
                    for (let r = 0; r < rows; r++) {
                        const row = [];
                        for (let c = 0; c < cols; c++) {
                            const cell = this.createCell();
                            cell.rowHeight = defaultRowHeight;
                            row.push(cell);
                        }
                        this.layout.push(row);
                    }
                },

                handleUpdates() {
                    if (!this.selectedCells.length || this.selecting || this.dragging || this.dragPending) return;

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
                    if (this.resizing || this.dragging || this.dragPending) return;
                    this.selecting = true;
                    this.focusTable();
                    this.clearSelection();
                    this.startRow = row;
                    this.startCol = col;
                    this.selectRange(row, col);
                },

                continueSelection(row, col) {
                    if (!this.selecting || this.dragging || this.resizing) return;
                    this.selectRange(row, col);
                },

                endSelection(focusTable = true) {
                    if (this.dragging || this.resizing || this.dragPending) return;
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

                // ── Column labels (A, B, C … Z, AA, AB …) ──

                getColumnLabel(index) {
                    let label = '';
                    let i = index;
                    do {
                        label = String.fromCharCode(65 + (i % 26)) + label;
                        i = Math.floor(i / 26) - 1;
                    } while (i >= 0);
                    return label;
                },

                // ── Size helpers ──

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

                // ── Header mouse-position based resize/drag ──

                handleHeaderMouseDown(event, type, index) {
                    const el = event.currentTarget;
                    const rect = el.getBoundingClientRect();
                    const EDGE_SIZE = 14;

                    const distFromStart = type === 'col'
                        ? (event.clientX - rect.left)
                        : (event.clientY - rect.top);
                    const distFromEnd = type === 'col'
                        ? (rect.right - event.clientX)
                        : (rect.bottom - event.clientY);

                    const nearStart = distFromStart < EDGE_SIZE && index > 0;
                    const nearEnd = distFromEnd < EDGE_SIZE;

                    if (nearStart || nearEnd) {
                        const resizeIndex = nearStart ? index - 1 : index;
                        this.resizing = {
                            type,
                            index: resizeIndex,
                            startPos: type === 'col' ? event.clientX : event.clientY,
                            startSize: type === 'col'
                                ? (this.getColumnWidth(resizeIndex) || rect.width)
                                : (this.getRowHeight(resizeIndex) || rect.height),
                        };
                        document.body.style.cursor = type === 'col' ? 'col-resize' : 'row-resize';
                        document.body.style.userSelect = 'none';
                    } else {
                        // Don't start drag yet — wait for mouse movement
                        this.dragPending = {
                            type, index,
                            startX: event.clientX,
                            startY: event.clientY,
                        };
                        this.selectedCells = [];
                        document.body.style.cursor = 'grab';
                        document.body.style.userSelect = 'none';
                    }
                },

                updateHeaderCursor(event, type) {
                    if (this.resizing || this.dragging) {
                        event.currentTarget.style.cursor = '';
                        return;
                    }
                    const el = event.currentTarget;
                    const rect = el.getBoundingClientRect();
                    const EDGE_SIZE = 14;

                    const distFromStart = type === 'col'
                        ? (event.clientX - rect.left)
                        : (event.clientY - rect.top);
                    const distFromEnd = type === 'col'
                        ? (rect.right - event.clientX)
                        : (rect.bottom - event.clientY);

                    const nearAnyEdge = distFromStart < EDGE_SIZE || distFromEnd < EDGE_SIZE;
                    el.style.cursor = nearAnyEdge ? (type === 'col' ? 'col-resize' : 'row-resize') : 'grab';
                },

                getActiveAlignment() {
                    if (!this.selectedCells.length) return null;
                    const [row, col] = this.selectedCells[0];
                    const cell = this.layout[row]?.[col];
                    if (!cell) return null;
                    return cell.alignment || null;
                },

                // ── Build clone cell from layout data ──

                buildCloneCell(cell) {
                    const td = document.createElement('td');
                    td.style.cssText = 'border: 1px solid rgba(128,128,128,0.3); padding: 0.5rem; font-size: 1.25rem; line-height: 1.75rem;';
                    td.style.backgroundColor = this.colors[cell?.color ?? 'default'] ?? '';
                    if (cell?.hidden) td.style.display = 'none';
                    if (cell?.rowspan > 1) td.rowSpan = cell.rowspan;
                    if (cell?.colspan > 1) td.colSpan = cell.colspan;
                    if (cell?.alignment === 'center') td.style.textAlign = 'center';
                    else if (cell?.alignment === 'right') td.style.textAlign = 'right';
                    const div = document.createElement('div');
                    div.innerHTML = cell?.displayName || '';
                    td.appendChild(div);
                    return td;
                },

                // ── Header drag with floating clone ──

                startHeaderDrag(type, index, event) {
                    const table = this.$refs.layoutTable;
                    if (!table) return;

                    // Clear selection highlight before drag starts
                    this.selectedCells = [];
                    this.dragging = { type, fromIndex: index };
                    this.dragTarget = { type, index };
                    document.body.style.cursor = 'grabbing';
                    document.body.style.userSelect = 'none';
                    // Prevent scroll during drag
                    this.$el.style.overflow = 'hidden';

                    if (type === 'row') {
                        const rows = Array.from(table.querySelectorAll('tbody tr'));
                        const draggedRow = rows[index];
                        if (!draggedRow) return;

                        const rowRect = draggedRow.getBoundingClientRect();
                        this.dragging.startPos = event.clientY;
                        this.dragging.cloneOffset = rowRect.top;
                        this.dragging.minPos = rows[0].getBoundingClientRect().top;
                        this.dragging.maxPos = rows[rows.length - 1].getBoundingClientRect().bottom - rowRect.height;
                        this.dragging.dragSize = rowRect.height;
                        this.dragging.midpoints = rows.map(r => {
                            const rr = r.getBoundingClientRect();
                            return rr.top + rr.height / 2;
                        });

                        // Measure actual column widths from the table
                        const colWidths = [];
                        const firstRowCells = rows[0]?.querySelectorAll('td');
                        if (firstRowCells) {
                            firstRowCells.forEach(td => colWidths.push(td.getBoundingClientRect().width));
                        }

                        // Build clone from layout data
                        const clone = document.createElement('table');
                        clone.style.cssText = `
                            position: fixed; left: ${rowRect.left}px; top: ${rowRect.top}px;
                            width: ${rowRect.width}px; height: ${rowRect.height}px;
                            z-index: 9999; pointer-events: none; opacity: 0.9;
                            box-shadow: 0 8px 25px rgba(0,0,0,0.35);
                            border-collapse: collapse; table-layout: fixed;
                        `;
                        // Build colgroup from measured widths
                        const cg = document.createElement('colgroup');
                        for (const w of colWidths) {
                            const col = document.createElement('col');
                            col.style.width = w + 'px';
                            cg.appendChild(col);
                        }
                        clone.appendChild(cg);

                        const tbody = document.createElement('tbody');
                        const tr = document.createElement('tr');
                        tr.style.height = rowRect.height + 'px';
                        // Row header
                        const rhTd = document.createElement('td');
                        rhTd.style.cssText = 'width: 32px; min-width: 32px; text-align: center; border: 1px solid rgba(128,128,128,0.3); background: rgba(128,128,128,0.15);';
                        rhTd.innerHTML = `<span style="font-size:10px;color:rgba(160,160,160,0.8)">${index + 1}</span>`;
                        tr.appendChild(rhTd);
                        // Data cells
                        for (let c = 0; c < (this.layout[index]?.length ?? 0); c++) {
                            tr.appendChild(this.buildCloneCell(this.layout[index][c]));
                        }
                        tbody.appendChild(tr);
                        clone.appendChild(tbody);
                        document.body.appendChild(clone);

                        this.dragging.clone = clone;
                        this.dragging.dimmed = [];
                        // Hide entire row — opacity:0 on each cell + border:none
                        draggedRow.querySelectorAll('td').forEach(td => {
                            td.style.opacity = '0';
                            td.style.setProperty('border', 'none', 'important');
                            this.dragging.dimmed.push(td);
                        });

                    } else if (type === 'col') {
                        const headers = Array.from(table.querySelectorAll('thead th'));
                        const draggedHeader = headers[index + 1];
                        if (!draggedHeader) return;

                        const headerRect = draggedHeader.getBoundingClientRect();
                        const theadTop = table.querySelector('thead').getBoundingClientRect().top;
                        const tbodyBottom = table.querySelector('tbody').getBoundingClientRect().bottom;

                        this.dragging.startPos = event.clientX;
                        this.dragging.cloneOffset = headerRect.left;
                        this.dragging.minPos = headers[1].getBoundingClientRect().left;
                        this.dragging.maxPos = headers[headers.length - 1].getBoundingClientRect().right - headerRect.width;
                        this.dragging.dragSize = headerRect.width;
                        this.dragging.midpoints = headers.slice(1).map(h => {
                            const hr = h.getBoundingClientRect();
                            return hr.left + hr.width / 2;
                        });

                        // Build clone from layout data
                        const clone = document.createElement('table');
                        clone.style.cssText = `
                            position: fixed; left: ${headerRect.left}px; top: ${theadTop}px;
                            width: ${headerRect.width}px; height: ${tbodyBottom - theadTop}px;
                            z-index: 9999; pointer-events: none; opacity: 0.9;
                            box-shadow: 0 8px 25px rgba(0,0,0,0.35);
                            border-collapse: collapse; table-layout: fixed;
                        `;
                        const cg = document.createElement('colgroup');
                        const col = document.createElement('col');
                        col.style.width = headerRect.width + 'px';
                        cg.appendChild(col);
                        clone.appendChild(cg);

                        // Clone header
                        const thead = document.createElement('thead');
                        const htr = document.createElement('tr');
                        const hTh = document.createElement('th');
                        hTh.style.cssText = `height: ${headerRect.height}px; text-align: center; border: 1px solid rgba(128,128,128,0.3); background: rgba(128,128,128,0.15);`;
                        hTh.innerHTML = `<span style="font-size:10px;color:rgba(160,160,160,0.8)">${this.getColumnLabel(index)}</span>`;
                        htr.appendChild(hTh);
                        thead.appendChild(htr);
                        clone.appendChild(thead);

                        // Clone body cells from layout data
                        const tbodyEl = document.createElement('tbody');
                        const bodyRows = Array.from(table.querySelectorAll('tbody tr'));
                        const dimmed = [draggedHeader];

                        for (let r = 0; r < this.layout.length; r++) {
                            const tr = document.createElement('tr');
                            const rowEl = bodyRows[r];
                            tr.style.height = rowEl ? getComputedStyle(rowEl).height : 'auto';
                            tr.appendChild(this.buildCloneCell(this.layout[r]?.[index]));
                            tbodyEl.appendChild(tr);

                            // Dim original cell
                            if (rowEl) {
                                const tds = rowEl.querySelectorAll('td');
                                if (tds[index + 1]) dimmed.push(tds[index + 1]);
                            }
                        }

                        clone.appendChild(tbodyEl);
                        document.body.appendChild(clone);

                        this.dragging.clone = clone;
                        // Hide original column cells — opacity:0 + border:none
                        dimmed.forEach(el => {
                            el.style.opacity = '0';
                            el.style.setProperty('border', 'none', 'important');
                        });
                        this.dragging.dimmed = dimmed;
                    }
                },

                // ── Global mouse handlers for resize & drag ──

                onGlobalMouseMove(event) {
                    // Check pending drag threshold
                    if (this.dragPending) {
                        const dx = event.clientX - this.dragPending.startX;
                        const dy = event.clientY - this.dragPending.startY;
                        if (Math.abs(dx) > 5 || Math.abs(dy) > 5) {
                            const { type, index } = this.dragPending;
                            this.dragPending = null;
                            this.startHeaderDrag(type, index, event);
                        }
                        return;
                    }

                    if (this.resizing) {
                        const delta = this.resizing.type === 'col'
                            ? event.clientX - this.resizing.startPos
                            : event.clientY - this.resizing.startPos;
                        const newSize = Math.max(20, Math.round(this.resizing.startSize + delta));

                        if (this.resizing.type === 'col') {
                            this.setColumnWidth(this.resizing.index, newSize);
                        } else {
                            this.setRowHeight(this.resizing.index, newSize);
                        }
                        return;
                    }

                    if (this.dragging?.clone) {
                        const delta = (this.dragging.type === 'row' ? event.clientY : event.clientX) - this.dragging.startPos;
                        const newPos = Math.max(this.dragging.minPos, Math.min(this.dragging.maxPos, this.dragging.cloneOffset + delta));

                        if (this.dragging.type === 'row') {
                            this.dragging.clone.style.top = newPos + 'px';
                        } else {
                            this.dragging.clone.style.left = newPos + 'px';
                        }

                        // Calculate drop target from midpoints
                        const midpoints = this.dragging.midpoints;
                        const pos = this.dragging.type === 'row' ? event.clientY : event.clientX;
                        let targetIndex = midpoints.length - 1;
                        for (let i = 0; i < midpoints.length; i++) {
                            if (pos < midpoints[i]) {
                                targetIndex = i;
                                break;
                            }
                        }
                        this.dragTarget = { type: this.dragging.type, index: targetIndex };

                        // Slide other elements out of the way
                        const table = this.$refs.layoutTable;
                        const from = this.dragging.fromIndex;
                        const size = this.dragging.dragSize;

                        if (this.dragging.type === 'row') {
                            const rows = table.querySelectorAll('tbody tr');
                            rows.forEach((row, i) => {
                                if (i === from) return;
                                let shift = 0;
                                if (from < targetIndex) {
                                    if (i > from && i <= targetIndex) shift = -size;
                                } else if (from > targetIndex) {
                                    if (i >= targetIndex && i < from) shift = size;
                                }
                                row.style.transform = shift ? `translateY(${shift}px)` : '';
                                row.style.transition = 'transform 0.15s ease';
                            });
                        } else {
                            const headers = table.querySelectorAll('thead th');
                            const bodyRows = table.querySelectorAll('tbody tr');

                            headers.forEach((th, i) => {
                                if (i === 0) return; // corner
                                const colIdx = i - 1;
                                if (colIdx === from) return;
                                let shift = 0;
                                if (from < targetIndex) {
                                    if (colIdx > from && colIdx <= targetIndex) shift = -size;
                                } else if (from > targetIndex) {
                                    if (colIdx >= targetIndex && colIdx < from) shift = size;
                                }
                                th.style.transform = shift ? `translateX(${shift}px)` : '';
                                th.style.transition = 'transform 0.15s ease';
                            });

                            bodyRows.forEach(row => {
                                const tds = row.querySelectorAll('td');
                                tds.forEach((td, i) => {
                                    if (i === 0) return; // row header
                                    const colIdx = i - 1;
                                    if (colIdx === from) return;
                                    let shift = 0;
                                    if (from < targetIndex) {
                                        if (colIdx > from && colIdx <= targetIndex) shift = -size;
                                    } else if (from > targetIndex) {
                                        if (colIdx >= targetIndex && colIdx < from) shift = size;
                                    }
                                    td.style.transform = shift ? `translateX(${shift}px)` : '';
                                    td.style.transition = 'transform 0.15s ease';
                                });
                            });
                        }

                        return;
                    }
                },

                onGlobalMouseUp(event) {
                    // Click on header without dragging = select row/column
                    if (this.dragPending) {
                        const { type, index } = this.dragPending;
                        this.dragPending = null;
                        document.body.style.cursor = '';
                        document.body.style.userSelect = '';
                        if (type === 'row') {
                            this.selectedCells = [];
                            for (let c = 0; c < (this.layout[index]?.length ?? 0); c++) {
                                this.selectedCells.push([index, c]);
                            }
                        } else {
                            this.selectedCells = [];
                            for (let r = 0; r < this.layout.length; r++) {
                                this.selectedCells.push([r, index]);
                            }
                        }
                        // Load first cell data
                        const [fr, fc] = this.selectedCells[0] ?? [];
                        const cell = this.layout[fr]?.[fc];
                        if (cell) {
                            this.content = cell.displayName ?? null;
                            this.selectedColor = cell.color;
                            this.selectedRoom = cell.room;
                            this.selectedTime = cell.time;
                        }
                        this.oldContent = this.content;
                        this.oldSelectedColor = this.selectedColor;
                        this.oldSelectedRoom = this.selectedRoom;
                        this.oldSelectedTime = this.selectedTime;
                        return;
                    }

                    if (this.resizing) {
                        this.updateState();
                        this.resizing = null;
                        document.body.style.cursor = '';
                        document.body.style.userSelect = '';
                        return;
                    }

                    if (this.dragging) {
                        // Remove clone
                        if (this.dragging.clone) {
                            this.dragging.clone.remove();
                        }
                        // Restore hidden elements
                        if (this.dragging.dimmed) {
                            this.dragging.dimmed.forEach(el => {
                                el.style.opacity = '';
                                el.style.removeProperty('border');
                            });
                        }
                        // Clear all transforms and restore scroll
                        const table = this.$refs.layoutTable;
                        if (table) {
                            table.querySelectorAll('tr, th, td').forEach(el => {
                                el.style.transform = '';
                                el.style.transition = '';
                            });
                        }
                        this.$el.style.overflow = '';
                        // Perform reorder
                        if (this.dragTarget) {
                            const from = this.dragging.fromIndex;
                            const to = this.dragTarget.index;
                            if (from !== to) {
                                if (this.dragging.type === 'col') {
                                    this.reorderColumn(from, to);
                                } else {
                                    this.reorderRow(from, to);
                                }
                            }
                        }

                        this.dragging = null;
                        this.dragTarget = null;
                        document.body.style.cursor = '';
                        document.body.style.userSelect = '';
                        return;
                    }

                    this.dragging = null;
                    this.dragTarget = null;
                    document.body.style.cursor = '';
                    document.body.style.userSelect = '';
                },

                // ── Reorder (proper move, not just adjacent swap) ──

                selectRowWithoutClearing(rowIndex) {
                    this.selectedCells = [];
                    const colCount = this.layout[rowIndex]?.length ?? 0;
                    for (let c = 0; c < colCount; c++) {
                        this.selectedCells.push([rowIndex, c]);
                    }
                    // Sync old values to prevent handleUpdates from firing
                    this.oldContent = this.content;
                    this.oldSelectedColor = this.selectedColor;
                    this.oldSelectedRoom = this.selectedRoom;
                    this.oldSelectedTime = this.selectedTime;
                },

                selectColumnWithoutClearing(colIndex) {
                    this.selectedCells = [];
                    for (let r = 0; r < this.layout.length; r++) {
                        this.selectedCells.push([r, colIndex]);
                    }
                    this.oldContent = this.content;
                    this.oldSelectedColor = this.selectedColor;
                    this.oldSelectedRoom = this.selectedRoom;
                    this.oldSelectedTime = this.selectedTime;
                },

                // Split any merge that touches the given row or column index
                splitMergesInvolving(type, index) {
                    for (let row = 0; row < this.layout.length; row++) {
                        for (let col = 0; col < (this.layout[row]?.length ?? 0); col++) {
                            const cell = this.layout[row]?.[col];
                            if (!cell || cell.hidden) continue;
                            const rowspan = cell.rowspan || 1;
                            const colspan = cell.colspan || 1;
                            if (rowspan <= 1 && colspan <= 1) continue;

                            const involved = type === 'row'
                                ? (index >= row && index < row + rowspan)
                                : (index >= col && index < col + colspan);

                            if (involved) {
                                for (let r = row; r < row + rowspan; r++) {
                                    for (let c = col; c < col + colspan; c++) {
                                        const target = this.layout[r]?.[c];
                                        if (!target) continue;
                                        delete target.hidden;
                                        delete target.mergedTo;
                                        delete target.rowspan;
                                        delete target.colspan;
                                    }
                                }
                            }
                        }
                    }
                },

                reorderColumn(from, to) {
                    if (from === to) return;
                    const colCount = this.layout[0]?.length ?? 0;
                    if (from < 0 || to < 0 || from >= colCount || to >= colCount) return;

                    this.splitMergesInvolving('col', from);
                    const anchors = this.getMergeAnchors();
                    this.clearMergeFlags();

                    for (let row = 0; row < this.layout.length; row++) {
                        const [cell] = this.layout[row].splice(from, 1);
                        this.layout[row].splice(to, 0, cell);
                    }

                    for (const anchor of anchors) {
                        if (anchor.col === from) {
                            anchor.col = to;
                        } else if (from < to) {
                            if (anchor.col > from && anchor.col <= to) anchor.col--;
                        } else {
                            if (anchor.col >= to && anchor.col < from) anchor.col++;
                        }
                    }

                    this.applyAnchorsAndRebuild(anchors);
                    this.selectColumnWithoutClearing(to);
                    this.updateState();
                },

                reorderRow(from, to) {
                    if (from === to) return;
                    if (from < 0 || to < 0 || from >= this.layout.length || to >= this.layout.length) return;

                    this.splitMergesInvolving('row', from);
                    const anchors = this.getMergeAnchors();
                    this.clearMergeFlags();

                    const [row] = this.layout.splice(from, 1);
                    this.layout.splice(to, 0, row);

                    for (const anchor of anchors) {
                        if (anchor.row === from) {
                            anchor.row = to;
                        } else if (from < to) {
                            if (anchor.row > from && anchor.row <= to) anchor.row--;
                        } else {
                            if (anchor.row >= to && anchor.row < from) anchor.row++;
                        }
                    }

                    this.applyAnchorsAndRebuild(anchors);
                    this.selectRowWithoutClearing(to);
                    this.updateState();
                },

                // ── Merge infrastructure ──

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

                // ── Row / column operations ──

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
                    this.selectRowWithoutClearing(to);
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
                    this.selectColumnWithoutClearing(to);
                    this.updateState();
                },

                // ── Merge / Split ──

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

                    const rows = this.selectedCells.map(([r]) => r);
                    const cols = this.selectedCells.map(([, c]) => c);
                    const minRow = Math.min(...rows);
                    const maxRow = Math.max(...rows);
                    const minCol = Math.min(...cols);
                    const maxCol = Math.max(...cols);

                    const rowSpan = maxRow - minRow + 1;
                    const colSpan = maxCol - minCol + 1;

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
