<script setup lang="ts">
import { Copy, Pencil, Search, Table as TableIcon, Trash2 } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
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
}>();

const emit = defineEmits<{
    'update:tableSearch': [v: string];
    'update:tablePage': [v: number];
    'update:tablePerPage': [v: number];
    'go-to-page': [page: number];
    'add-rows': [];
    'copy': [];
    'edit-row': [row: TableRowRecord];
    'delete-row': [row: TableRowRecord];
    'update:copyTooltipOpen': [v: boolean | undefined];
}>();

function onTableSearchInput(e: Event) {
    emit('update:tableSearch', (e.target as HTMLInputElement).value);
}

const tablePaginationSlots = () => {
    const total = props.rowsMeta?.last_page ?? 0;
    if (total <= 0) return [];
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
};
</script>

<template>
    <div class="table-paper space-y-4 rounded-xl bg-card p-4 text-card-foreground shadow-sm sm:p-5">
        <div class="flex flex-wrap items-center gap-2 sm:gap-3">
            <div class="relative min-w-0 flex-1 basis-full sm:basis-0 sm:max-w-sm">
                <Search
                    class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                />
                <input
                    :value="tableSearch"
                    type="search"
                    placeholder="Search for anything in the table"
                    class="w-full rounded-lg border-2 border-input bg-muted/50 py-2 pl-9 pr-3 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary focus:ring-offset-0"
                    @input="onTableSearchInput"
                />
            </div>
            <span
                v-if="rowsMeta"
                class="text-sm text-muted-foreground"
            >
                {{ rowsMeta.total.toLocaleString() }} row{{ rowsMeta.total !== 1 ? 's' : '' }}
            </span>
            <div v-if="rowsMeta && rowsMeta.total > 0" class="flex items-center gap-1.5 text-sm">
                <label for="table-per-page" class="text-muted-foreground">Rows per page</label>
                <select
                    id="table-per-page"
                    :value="tablePerPage"
                    class="rounded-lg border border-input bg-background px-2 py-1.5 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary"
                    @change="emit('update:tablePerPage', Number(($event.target as HTMLSelectElement).value))"
                >
                    <option :value="25">25</option>
                    <option :value="50">50</option>
                    <option :value="100">100</option>
                </select>
            </div>
            <div class="ml-auto flex items-center gap-2">
                <Button
                    size="sm"
                    variant="outline"
                    class="gap-1.5 border-border text-foreground hover:bg-muted"
                    @click="emit('add-rows')"
                >
                    <TableIcon class="h-3.5 w-3.5" />
                    Add rows
                </Button>
                <Tooltip
                    :open="copyTooltipOpen"
                    @update:open="(v) => emit('update:copyTooltipOpen', v === false ? undefined : v)"
                >
                    <TooltipTrigger as-child>
                        <button
                            type="button"
                            class="inline-flex items-center gap-1.5 rounded border border-border px-2 py-1.5 text-sm text-muted-foreground hover:bg-muted hover:text-foreground"
                            @click="emit('copy')"
                        >
                            Copy
                            <Copy class="h-3.5 w-3.5" />
                        </button>
                    </TooltipTrigger>
                    <TooltipContent>
                        {{ copyFeedback ? 'Copied to clipboard' : 'Copy to clipboard' }}
                    </TooltipContent>
                </Tooltip>
            </div>
        </div>
        <p v-if="rowsError" class="text-sm text-destructive">
            {{ rowsError }}
        </p>
        <div
            v-if="rowsMeta && rowsMeta.last_page > 1"
            class="flex flex-wrap items-center justify-center gap-1 py-2"
        >
            <button
                type="button"
                class="rounded-lg border border-input bg-background px-2.5 py-1.5 text-sm font-medium text-foreground hover:bg-muted disabled:opacity-50"
                :disabled="tablePage <= 1"
                @click="emit('go-to-page', tablePage - 1)"
            >
                Previous
            </button>
            <template v-for="(slot, idx) in tablePaginationSlots()" :key="idx">
                <button
                    v-if="slot !== 'ellipsis'"
                    type="button"
                    class="min-w-[2.25rem] rounded-lg border px-2.5 py-1.5 text-sm font-medium transition-colors"
                    :class="tablePage === slot ? 'border-primary bg-primary text-primary-foreground' : 'border-input bg-background text-foreground hover:bg-muted'"
                    @click="emit('go-to-page', slot)"
                >
                    {{ slot }}
                </button>
                <span v-else class="px-1 text-muted-foreground">…</span>
            </template>
            <button
                type="button"
                class="rounded-lg border border-input bg-background px-2.5 py-1.5 text-sm font-medium text-foreground hover:bg-muted disabled:opacity-50"
                :disabled="tablePage >= (rowsMeta?.last_page ?? 1)"
                @click="emit('go-to-page', tablePage + 1)"
            >
                Next
            </button>
        </div>
        <div v-if="rowsLoading" class="py-8 text-center text-sm text-muted-foreground">
            Loading rows…
        </div>
        <div class="-mx-3 overflow-x-auto overscroll-x-contain sm:mx-0">
            <table
                class="w-full min-w-[280px] border-collapse text-left text-sm text-foreground sm:min-w-[300px]"
            >
                <thead>
                    <tr class="border-b border-border bg-muted">
                        <th
                            v-for="(h, i) in tableHeaders"
                            :key="i"
                            class="px-2 py-2 font-medium text-foreground sm:px-4 sm:py-3"
                        >
                            {{ h }}
                        </th>
                        <th
                            class="w-20 px-2 py-2 font-medium text-foreground sm:w-24 sm:px-4 sm:py-3"
                        >
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="row in tableRows"
                        :key="row.id"
                        class="border-b border-border hover:bg-muted/50"
                    >
                        <td
                            v-for="(cell, ci) in (row.cells ?? [])"
                            :key="ci"
                            class="max-w-[120px] truncate px-2 py-2 text-muted-foreground sm:max-w-none sm:px-4 sm:py-3"
                            :title="cell != null ? String(cell) : undefined"
                        >
                            {{ cell == null ? '—' : cell }}
                        </td>
                        <td class="px-2 py-2 sm:px-4">
                            <div class="flex items-center gap-0.5 sm:gap-1">
                                <button
                                    type="button"
                                    class="min-h-[44px] min-w-[44px] rounded p-2 text-muted-foreground hover:bg-muted hover:text-foreground sm:min-h-0 sm:min-w-0 sm:p-1.5"
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
                </tbody>
            </table>
        </div>
        <div
            v-if="rowsMeta && rowsMeta.last_page > 1"
            class="flex flex-wrap items-center justify-center gap-1 py-3 text-sm"
        >
            <button
                type="button"
                class="rounded-lg border border-input bg-background px-2.5 py-1.5 font-medium text-foreground hover:bg-muted disabled:opacity-50"
                :disabled="tablePage <= 1"
                @click="emit('go-to-page', tablePage - 1)"
            >
                Previous
            </button>
            <template v-for="(slot, idx) in tablePaginationSlots()" :key="'bottom-' + idx">
                <button
                    v-if="slot !== 'ellipsis'"
                    type="button"
                    class="min-w-[2.25rem] rounded-lg border px-2.5 py-1.5 font-medium transition-colors"
                    :class="tablePage === slot ? 'border-primary bg-primary text-primary-foreground' : 'border-input bg-background text-foreground hover:bg-muted'"
                    @click="emit('go-to-page', slot)"
                >
                    {{ slot }}
                </button>
                <span v-else class="px-1 text-muted-foreground">…</span>
            </template>
            <button
                type="button"
                class="rounded-lg border border-input bg-background px-2.5 py-1.5 font-medium text-foreground hover:bg-muted disabled:opacity-50"
                :disabled="tablePage >= (rowsMeta?.last_page ?? 1)"
                @click="emit('go-to-page', tablePage + 1)"
            >
                Next
            </button>
        </div>
    </div>
</template>
