<script setup lang="ts">
import { useMediaQuery } from '@vueuse/core';
import { computed, ref, watch } from 'vue';
import { Copy, Ellipsis, Mic, Pause, Pencil, Play, Search, Square, Table as TableIcon, Trash2 } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import PaginationPills from './PaginationPills.vue';
import type { RowsMeta, TableRowRecord } from '../types';

const props = defineProps<{
    tableSearch: string;
    tableHeaders: string[];
    tableRows: TableRowRecord[];
    rowsMeta: RowsMeta | null;
    rowsLoading: boolean;
    rowsError: string | null;
    tablePage: number;
    tablePerPage: number;
    copyFeedback: boolean;
    copyTooltipOpen?: boolean;
    canReadAloud?: boolean;
    readAloudPlaying?: boolean;
    readAloudPaused?: boolean;
    readAloudError?: string | null;
    readAloudVoices?: { lang: string; label: string }[];
    readAloudVoiceId?: string;
}>();

const emit = defineEmits<{
    'update:tableSearch': [v: string];
    'update:tablePage': [v: number];
    'go-to-page': [page: number];
    'add-rows': [];
    'copy': [];
    'edit-row': [row: TableRowRecord];
    'delete-row': [row: TableRowRecord];
    'delete-selected-rows': [rows: TableRowRecord[]];
    'update:copyTooltipOpen': [v: boolean | undefined];
    'read-aloud': [];
    'read-aloud-pause': [];
    'read-aloud-resume': [];
    'read-aloud-stop': [];
    'update:read-aloud-voice': [voiceId: string];
}>();

const selectedRowIds = ref<Set<number>>(new Set());

watch(
    () => props.tableRows.map((r) => r.id),
    () => {
        selectedRowIds.value = new Set();
    },
);

const selectedRows = () => props.tableRows.filter((r) => selectedRowIds.value.has(r.id));
const allRowsSelected = () =>
    props.tableRows.length > 0 && selectedRowIds.value.size === props.tableRows.length;
const someRowsSelected = () => selectedRowIds.value.size > 0;

function headerCheckboxState(): boolean | 'indeterminate' {
    if (allRowsSelected()) return true;
    if (someRowsSelected()) return 'indeterminate';
    return false;
}

function toggleSelectAllRows(checked: boolean | 'indeterminate') {
    if (checked === true) {
        selectedRowIds.value = new Set(props.tableRows.map((r) => r.id));
    } else {
        selectedRowIds.value = new Set();
    }
}

function toggleSelectRow(id: number, checked: boolean) {
    const next = new Set(selectedRowIds.value);
    if (checked) next.add(id);
    else next.delete(id);
    selectedRowIds.value = next;
}

function onDeleteSelectedRows() {
    const rows = selectedRows();
    if (rows.length === 0) return;
    emit('delete-selected-rows', rows);
}

function onTableSearchInput(e: Event) {
    emit('update:tableSearch', (e.target as HTMLInputElement).value);
}

const isDesktop = useMediaQuery('(min-width: 640px)');
const tablePaginationSlots = computed(() => {
    const total = props.rowsMeta?.last_page ?? 0;
    if (total <= 0) return [];
    if (isDesktop.value) return Array.from({ length: total }, (_, i) => i + 1);
    if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1);
    const current = props.tablePage;
    const slots: (number | 'ellipsis')[] = [1];
    const windowStart = Math.max(2, current - 1);
    const windowEnd = Math.min(total - 1, current + 1);
    if (windowStart > 2) slots.push('ellipsis');
    for (let p = windowStart; p <= windowEnd; p++) {
        if (p !== 1 && p !== total) slots.push(p);
    }
    if (windowEnd < total - 1) slots.push('ellipsis');
    if (total > 1) slots.push(total);
    return slots;
});

const readAloudModalOpen = ref(false);
const readAloudModalAccent = ref('');

function openReadAloudModal() {
    readAloudModalAccent.value = props.readAloudVoiceId ?? '';
    readAloudModalOpen.value = true;
}

function startReadAloudFromModal() {
    emit('update:read-aloud-voice', readAloudModalAccent.value);
    readAloudModalOpen.value = false;
    emit('read-aloud');
}
</script>

<template>
    <div class="table-paper space-y-4 rounded-xl bg-white p-4 text-gray-900 shadow-sm sm:p-5">
        <div class="flex flex-wrap items-center gap-2 sm:gap-3">
            <div class="flex min-w-0 w-full flex-1 basis-0 sm:w-1/2 sm:max-w-[50%]">
                <div class="relative min-w-0 flex-1">
                    <Search
                        class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-500"
                    />
                    <input
                        :value="tableSearch"
                        type="search"
                        placeholder="Search for anything in the table"
                        class="w-full rounded-lg border-2 border-gray-300 bg-gray-50 py-2 pl-9 pr-3 text-sm text-gray-900 placeholder:text-gray-500 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary focus:ring-offset-0"
                        @input="onTableSearchInput"
                    />
                </div>
            </div>
            <!-- Desktop: all action buttons -->
            <div class="ml-auto hidden flex-wrap items-center gap-2 sm:flex">
                <template v-if="canReadAloud && readAloudPlaying">
                    <Tooltip>
                        <TooltipTrigger as-child>
                            <button
                                type="button"
                                class="inline-flex items-center gap-1.5 rounded border border-gray-300 px-2 py-1.5 text-sm text-gray-500 hover:bg-gray-100 hover:text-gray-900"
                                :title="readAloudPaused ? 'Continue' : 'Pause'"
                                @click="readAloudPaused ? emit('read-aloud-resume') : emit('read-aloud-pause')"
                            >
                                <Pause v-if="!readAloudPaused" class="h-3.5 w-3.5" />
                                <Play v-else class="h-3.5 w-3.5" />
                                {{ readAloudPaused ? 'Continue' : 'Pause' }}
                            </button>
                        </TooltipTrigger>
                        <TooltipContent>{{ readAloudPaused ? 'Continue reading' : 'Pause' }}</TooltipContent>
                    </Tooltip>
                    <Tooltip>
                        <TooltipTrigger as-child>
                            <button
                                type="button"
                                class="inline-flex items-center gap-1.5 rounded border border-gray-300 px-2 py-1.5 text-sm text-gray-500 hover:bg-gray-100 hover:text-gray-900"
                                title="Stop and start over"
                                @click="emit('read-aloud-stop')"
                            >
                                <Square class="h-3.5 w-3.5" />
                                Stop
                            </button>
                        </TooltipTrigger>
                        <TooltipContent>Stop and start from beginning next time</TooltipContent>
                    </Tooltip>
                </template>
                <Tooltip v-if="canReadAloud">
                    <TooltipTrigger as-child>
                        <button
                            type="button"
                            class="inline-flex items-center gap-1.5 rounded border border-gray-300 px-2 py-1.5 text-sm text-gray-500 hover:bg-gray-100 hover:text-gray-900 disabled:opacity-50"
                            :disabled="rowsLoading"
                            title="Read all pages aloud"
                            @click="openReadAloudModal()"
                        >
                            <Mic class="h-3.5 w-3.5" />
                            Read aloud
                        </button>
                    </TooltipTrigger>
                    <TooltipContent>Read all pages aloud</TooltipContent>
                </Tooltip>
                <Button
                    size="sm"
                    variant="outline"
                    class="gap-1.5 border-gray-300 bg-white text-gray-900 hover:bg-gray-100"
                    @click="emit('add-rows')"
                >
                    <TableIcon class="h-3.5 w-3.5" />
                    Add rows
                </Button>
                <Tooltip :open="copyTooltipOpen" @update:open="(v) => emit('update:copyTooltipOpen', v === false ? undefined : v)">
                    <TooltipTrigger as-child>
                        <button
                            type="button"
                            class="inline-flex items-center gap-1.5 rounded border border-gray-300 px-2 py-1.5 text-sm text-gray-500 hover:bg-gray-100 hover:text-gray-900"
                            @click="emit('copy')"
                        >
                            <Copy class="h-3.5 w-3.5" />
                            Copy
                        </button>
                    </TooltipTrigger>
                    <TooltipContent>{{ copyFeedback ? 'Copied to clipboard' : 'Copy to clipboard' }}</TooltipContent>
                </Tooltip>
                <template v-if="someRowsSelected()">
                    <Button
                        size="sm"
                        variant="outline"
                        class="border-gray-300 text-gray-700 hover:bg-gray-100"
                        @click="toggleSelectAllRows(false)"
                    >
                        Clear
                    </Button>
                    <Button
                        size="sm"
                        variant="destructive"
                        @click="onDeleteSelectedRows"
                    >
                        <Trash2 class="h-3.5 w-3.5" />
                        Delete selected
                    </Button>
                </template>
            </div>
            <!-- Mobile: Actions dropdown only -->
            <DropdownMenu class="ml-auto sm:hidden">
                <DropdownMenuTrigger as-child>
                    <Button
                        size="sm"
                        variant="outline"
                        class="border-gray-300 bg-white text-gray-900 hover:bg-gray-100"
                        title="Actions"
                    >
                        <Ellipsis class="h-3.5 w-3.5 shrink-0" />
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end" class="w-48">
                    <DropdownMenuItem
                        v-if="canReadAloud"
                        :disabled="rowsLoading"
                        @click="openReadAloudModal()"
                    >
                        <Mic class="mr-2 h-3.5 w-3.5" />
                        Read aloud
                    </DropdownMenuItem>
                    <DropdownMenuItem @click="emit('add-rows')">
                        <TableIcon class="mr-2 h-3.5 w-3.5" />
                        Add rows
                    </DropdownMenuItem>
                    <DropdownMenuItem @click="emit('copy')">
                        <Copy class="mr-2 h-3.5 w-3.5" />
                        Copy
                    </DropdownMenuItem>
                    <DropdownMenuItem
                        v-if="someRowsSelected()"
                        @click="toggleSelectAllRows(false)"
                    >
                        Clear selection
                    </DropdownMenuItem>
                    <DropdownMenuItem
                        v-if="someRowsSelected()"
                        variant="destructive"
                        @click="onDeleteSelectedRows"
                    >
                        <Trash2 class="mr-2 h-3.5 w-3.5" />
                        Delete selected
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>
        </div>
        <!-- Read aloud modal: choose accent then start -->
        <Dialog :open="readAloudModalOpen" @update:open="readAloudModalOpen = $event">
            <DialogContent class="sm:max-w-sm">
                <DialogHeader>
                    <DialogTitle>Read aloud</DialogTitle>
                </DialogHeader>
                <p class="text-sm text-muted-foreground">
                    Choose accent (optional), then start. All pages will be read in order.
                </p>
                <select
                    v-if="readAloudVoices?.length"
                    v-model="readAloudModalAccent"
                    class="mt-2 w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-primary"
                >
                    <option value="">Default accent</option>
                    <option
                        v-for="a in readAloudVoices"
                        :key="a.lang"
                        :value="a.lang"
                    >
                        {{ a.label }}
                    </option>
                </select>
                <DialogFooter class="mt-4 gap-2">
                    <Button
                        variant="outline"
                        @click="readAloudModalOpen = false"
                    >
                        Cancel
                    </Button>
                    <Button @click="startReadAloudFromModal">
                        <Mic class="mr-2 h-4 w-4" />
                        Start
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
        <p v-if="readAloudError" class="text-sm text-destructive">
            {{ readAloudError }}
        </p>
        <p v-if="rowsError" class="text-sm text-destructive">
            {{ rowsError }}
        </p>
        <div
            v-if="rowsMeta && rowsMeta.last_page > 1"
            class="flex flex-wrap items-center justify-center gap-1 py-2"
        >
            <button
                type="button"
                class="rounded-lg border border-gray-300 bg-white px-2.5 py-1.5 text-sm font-medium text-gray-900 hover:bg-gray-100 disabled:opacity-50"
                :disabled="tablePage <= 1"
                @click="emit('go-to-page', tablePage - 1)"
            >
                Previous
            </button>
            <template v-for="(slot, idx) in tablePaginationSlots" :key="idx">
                <button
                    v-if="slot !== 'ellipsis'"
                    type="button"
                    class="min-w-[2.25rem] rounded-lg border px-2.5 py-1.5 text-sm font-medium transition-colors"
                    :class="tablePage === slot ? 'border-primary bg-primary text-primary-foreground' : 'border-gray-300 bg-white text-gray-900 hover:bg-gray-100'"
                    @click="emit('go-to-page', slot)"
                >
                    {{ slot }}
                </button>
                <span v-else class="px-1 text-gray-500">…</span>
            </template>
            <button
                type="button"
                class="rounded-lg border border-gray-300 bg-white px-2.5 py-1.5 text-sm font-medium text-gray-900 hover:bg-gray-100 disabled:opacity-50"
                :disabled="tablePage >= (rowsMeta?.last_page ?? 1)"
                @click="emit('go-to-page', tablePage + 1)"
            >
                Next
            </button>
        </div>
        <div v-if="rowsLoading" class="py-8 text-center text-sm text-gray-600">
            Loading rows…
        </div>
        <div class="-mx-3 overflow-x-auto overscroll-x-contain sm:mx-0">
            <table
                class="w-full min-w-[280px] border-collapse text-left text-sm text-gray-900 sm:min-w-[300px]"
            >
                <thead>
                    <tr class="border-b border-gray-200 bg-gray-100">
                        <th class="w-10 px-2 py-2 sm:px-3 sm:py-3">
                            <Checkbox
                                class="table-view__checkbox"
                                :checked="headerCheckboxState()"
                                aria-label="Select all rows"
                                @update:checked="(v) => toggleSelectAllRows(v === true)"
                            />
                        </th>
                        <th
                            v-for="(h, i) in tableHeaders"
                            :key="i"
                            class="px-2 py-2 font-medium text-gray-900 sm:px-4 sm:py-3"
                        >
                            {{ h }}
                        </th>
                        <th
                            class="w-20 px-2 py-2 font-medium text-gray-900 sm:w-24 sm:px-4 sm:py-3"
                        >
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="row in tableRows"
                        :key="row.id"
                        class="border-b border-gray-200 hover:bg-gray-50"
                        :class="{ 'bg-primary/5': selectedRowIds.has(row.id) }"
                    >
                        <td class="w-10 px-2 py-2 sm:px-3">
                            <Checkbox
                                class="table-view__checkbox"
                                :checked="selectedRowIds.has(row.id)"
                                aria-label="Select row"
                                @update:checked="(v) => toggleSelectRow(row.id, !!v)"
                            />
                        </td>
                        <td
                            v-for="(cell, ci) in (row.cells ?? [])"
                            :key="ci"
                            class="max-w-[120px] truncate px-2 py-2 text-gray-700 sm:max-w-none sm:px-4 sm:py-3"
                            :title="cell != null ? String(cell) : undefined"
                        >
                            {{ cell == null ? '—' : cell }}
                        </td>
                        <td class="px-2 py-2 sm:px-4">
                            <div class="flex items-center gap-0.5 sm:gap-1">
                                <button
                                    type="button"
                                    class="min-h-[44px] min-w-[44px] rounded p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-900 sm:min-h-0 sm:min-w-0 sm:p-1.5"
                                    title="Edit row"
                                    @click="emit('edit-row', row)"
                                >
                                    <Pencil class="h-4 w-4" />
                                </button>
                                <button
                                    type="button"
                                    class="min-h-[44px] min-w-[44px] rounded p-2 text-muted-foreground hover:bg-destructive/10 hover:text-destructive sm:min-h-0 sm:min-w-0 sm:p-1.5"
                                    title="Delete row"
                                    @click="emit('delete-row', row)"
                                >
                                    <Trash2 class="h-4 w-4" />
                                </button>
                            </div>
                        </td>
                    </tr>
                    <tr
                        v-if="!rowsLoading && tableRows.length === 0 && tableHeaders.length > 0"
                        class="border-b border-gray-200"
                    >
                        <td
                            :colspan="tableHeaders.length + 2"
                            class="px-4 py-8 text-center text-sm text-muted-foreground"
                        >
                            No results.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div
            v-if="rowsMeta && rowsMeta.last_page > 1"
            class="flex flex-wrap items-center justify-center gap-1 py-3 text-sm"
        >
            <button
                type="button"
                class="rounded-lg border border-gray-300 bg-white px-2.5 py-1.5 font-medium text-gray-900 hover:bg-gray-100 disabled:opacity-50"
                :disabled="tablePage <= 1"
                @click="emit('go-to-page', tablePage - 1)"
            >
                Previous
            </button>
            <template v-for="(slot, idx) in tablePaginationSlots" :key="'bottom-' + idx">
                <button
                    v-if="slot !== 'ellipsis'"
                    type="button"
                    class="min-w-[2.25rem] rounded-lg border px-2.5 py-1.5 font-medium transition-colors"
                    :class="tablePage === slot ? 'border-primary bg-primary text-primary-foreground' : 'border-gray-300 bg-white text-gray-900 hover:bg-gray-100'"
                    @click="emit('go-to-page', slot)"
                >
                    {{ slot }}
                </button>
                <span v-else class="px-1 text-gray-500">…</span>
            </template>
            <button
                type="button"
                class="rounded-lg border border-gray-300 bg-white px-2.5 py-1.5 font-medium text-gray-900 hover:bg-gray-100 disabled:opacity-50"
                :disabled="tablePage >= (rowsMeta?.last_page ?? 1)"
                @click="emit('go-to-page', tablePage + 1)"
            >
                Next
            </button>
        </div>
    </div>
</template>

<style scoped>
.table-view__checkbox {
    background-color: white !important;
    border-color: rgb(209 213 219) !important;
}
.table-view__checkbox[data-state='checked'],
.table-view__checkbox[data-state='indeterminate'] {
    background-color: var(--color-primary) !important;
    border-color: var(--color-primary) !important;
}
</style>
