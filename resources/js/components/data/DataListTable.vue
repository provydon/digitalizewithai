<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { AlertCircle, Check, FileText, Loader2, Table as TableIcon, Trash2 } from 'lucide-vue-next';
import { Checkbox } from '@/components/ui/checkbox';
import type { DigitalizedItem } from '@/types';

const props = defineProps<{
    items: DigitalizedItem[];
    /** Selected item IDs (controlled by parent). */
    selectedIds: number[];
    /** Function to get view URL for an item, e.g. (id) => `/dashboard/data/${id}` */
    viewUrl: (id: number) => string;
}>();

const emit = defineEmits<{
    'update:selectedIds': [ids: number[]];
    deleteRequest: [item: DigitalizedItem];
    deleteSelectedRequest: [items: DigitalizedItem[]];
}>();

const selectedItems = () => props.items.filter((i) => props.selectedIds.includes(i.id));
const allSelected = () => props.items.length > 0 && props.selectedIds.length === props.items.length;
const someSelected = () => props.selectedIds.length > 0;

/** Header checkbox state: true | false | 'indeterminate' for tri-state. */
function headerCheckboxState(): boolean | 'indeterminate' {
    if (allSelected()) return true;
    if (someSelected()) return 'indeterminate';
    return false;
}

function toggleSelectAll(checked: boolean | 'indeterminate') {
    if (checked === true) {
        emit('update:selectedIds', props.items.map((i) => i.id));
    } else {
        emit('update:selectedIds', []);
    }
}

function toggleSelect(id: number, checked: boolean) {
    const next = new Set(props.selectedIds);
    if (checked) next.add(id);
    else next.delete(id);
    emit('update:selectedIds', Array.from(next));
}

function onDeleteSelected() {
    const items = selectedItems();
    if (items.length === 0) return;
    emit('deleteSelectedRequest', items);
}

function formatDate(iso: string | null): string {
    if (!iso) return '—';
    const d = new Date(iso);
    if (Number.isNaN(d.getTime())) return '—';
    return d.toLocaleString();
}

function formatDuration(seconds: number | null | undefined): string {
    if (seconds == null) return '—';
    const s = Math.max(0, Math.floor(Math.abs(Number(seconds))));
    if (s < 60) return `${s}s`;
    const m = Math.floor(s / 60) % 60;
    const sec = s % 60;
    const h = Math.floor(s / 3600) % 24;
    const d = Math.floor(s / 86400);
    const parts: string[] = [];
    if (d > 0) parts.push(`${d}d`);
    if (h > 0) parts.push(`${h}h`);
    if (m > 0) parts.push(`${m}m`);
    if (sec > 0 || parts.length === 0) parts.push(`${sec}s`);
    return parts.join(' ');
}

/** Compute when extraction ended: started_at + duration_seconds (uses abs(duration) for bad data). */
function endedAt(item: DigitalizedItem): Date | null {
    const started = item.extraction_started_at;
    const secs = item.extraction_duration_seconds;
    if (!started || secs == null) return null;
    const d = new Date(started);
    if (Number.isNaN(d.getTime())) return null;
    return new Date(d.getTime() + Math.abs(Number(secs)) * 1000);
}

function formatEndedAt(item: DigitalizedItem): string {
    const end = endedAt(item);
    if (!end) return '—';
    return end.toLocaleString();
}

function onRowClick(item: DigitalizedItem) {
    router.visit(props.viewUrl(item.id));
}
</script>

<template>
    <div class="-mx-1 overflow-x-auto overscroll-x-contain sm:mx-0">
        <table class="w-full min-w-[320px] text-left text-sm text-foreground sm:min-w-[400px]" role="grid">
            <thead>
                <tr class="border-b border-border">
                    <th class="w-10 pb-3 pr-2 font-medium text-muted-foreground sm:pr-3">
                        <Checkbox
                            :checked="headerCheckboxState()"
                            aria-label="Select all"
                            @update:checked="(v) => toggleSelectAll(v === true)"
                        />
                    </th>
                    <th class="pb-3 pr-3 font-medium text-muted-foreground sm:pr-4">ID</th>
                    <th class="pb-3 pr-3 font-medium text-muted-foreground sm:pr-4">Name</th>
                    <th class="pb-3 pr-3 font-medium text-muted-foreground sm:pr-4">Type</th>
                    <th class="pb-3 pr-3 font-medium text-muted-foreground sm:pr-4">Status</th>
                    <th class="hidden pb-3 pr-3 font-medium text-muted-foreground md:table-cell sm:pr-4">AI</th>
                    <th class="pb-3 pr-3 font-medium text-muted-foreground sm:pr-4">Duration</th>
                    <th class="hidden pb-3 pr-3 font-medium text-muted-foreground md:table-cell sm:pr-4">Ended</th>
                    <th class="hidden pb-3 pr-3 font-medium text-muted-foreground sm:table-cell sm:pr-4">Created</th>
                    <th class="w-10 pb-3 pl-0 font-medium text-muted-foreground" aria-label="Actions"> </th>
                </tr>
            </thead>
            <tbody>
                <tr
                    v-for="(item, index) in items"
                    :key="item.id"
                    class="group border-b border-border/80 transition-colors last:border-b-0 hover:bg-muted/40 cursor-pointer"
                    :class="{ 'bg-muted/20': index % 2 === 1, 'bg-primary/5': selectedIds.includes(item.id) }"
                    @click="onRowClick(item)"
                >
                    <td class="w-10 py-3.5 pr-2" @click.stop>
                        <Checkbox
                            :checked="selectedIds.includes(item.id)"
                            aria-label="Select row"
                            @update:checked="(v) => toggleSelect(item.id, !!v)"
                        />
                    </td>
                    <td class="py-3.5 pr-3 font-mono text-xs text-muted-foreground sm:pr-4">
                        {{ item.id }}
                    </td>
                    <td class="py-3.5 pr-3 sm:pr-4">
                        <Link
                            :href="viewUrl(item.id)"
                            class="font-medium text-foreground underline-offset-4 hover:underline"
                            @click.stop
                        >
                            {{ item.name }}
                        </Link>
                    </td>
                    <td class="py-3.5 pr-3 sm:pr-4">
                        <span
                            v-if="item.type === 'doc'"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-muted px-2.5 py-1 text-xs font-medium text-muted-foreground"
                        >
                            <FileText class="h-3.5 w-3.5" />
                            doc
                        </span>
                        <span
                            v-else-if="item.type === 'table'"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-muted px-2.5 py-1 text-xs font-medium text-muted-foreground"
                        >
                            <TableIcon class="h-3.5 w-3.5" />
                            table
                        </span>
                        <span v-else class="text-muted-foreground">—</span>
                    </td>
                    <td class="py-3.5 pr-3 sm:pr-4">
                        <span
                            v-if="item.status === 'failed'"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-red-100 px-2 py-1 text-xs font-medium text-red-800 dark:bg-red-900/40 dark:text-red-300"
                        >
                            <AlertCircle class="h-3.5 w-3.5" />
                            Failed
                        </span>
                        <span
                            v-else-if="item.status === 'processing'"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-amber-100 px-2 py-1 text-xs font-medium text-amber-800 dark:bg-amber-900/40 dark:text-amber-300"
                        >
                            <Loader2 class="h-3.5 w-3.5 shrink-0 animate-spin" />
                            {{ (item.processing_batches_total ?? 0) > 0 ? `Extracting… ${item.processing_batches_done ?? 0}/${item.processing_batches_total}` : 'Processing…' }}
                        </span>
                        <span
                            v-else
                            class="inline-flex items-center gap-1.5 rounded-lg bg-green-100 px-2 py-1 text-xs font-medium text-green-800 dark:bg-green-900/40 dark:text-green-300"
                        >
                            <Check class="h-3.5 w-3.5" />
                            Ready
                        </span>
                    </td>
                    <td class="hidden py-3.5 pr-3 text-muted-foreground md:table-cell sm:pr-4">
                        <span v-if="item.ai_provider || item.ai_model" class="text-xs">
                            {{ [item.ai_provider, item.ai_model].filter(Boolean).join(' · ') }}
                        </span>
                        <span v-else class="text-muted-foreground">—</span>
                    </td>
                    <td class="py-3.5 pr-3 text-muted-foreground sm:pr-4">
                        {{ formatDuration(item.extraction_duration_seconds) }}
                    </td>
                    <td class="hidden py-3.5 pr-3 text-muted-foreground md:table-cell sm:pr-4">
                        {{ formatEndedAt(item) }}
                    </td>
                    <td class="hidden py-3.5 pr-3 text-muted-foreground sm:table-cell sm:pr-4">
                        {{ formatDate(item.created_at) }}
                    </td>
                    <td class="w-10 py-3.5 pl-0">
                        <button
                            type="button"
                            class="rounded-lg p-2 text-muted-foreground opacity-70 transition-opacity hover:bg-destructive/10 hover:text-destructive hover:opacity-100"
                            title="Delete"
                            aria-label="Delete this item"
                            @click.stop="emit('deleteRequest', item)"
                        >
                            <Trash2 class="h-4 w-4" />
                        </button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
