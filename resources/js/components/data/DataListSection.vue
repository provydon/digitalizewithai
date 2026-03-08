<script setup lang="ts">
import { isBroadcastingEnabled, subscribeDataRecord } from '@/lib/echo';
import { Link } from '@inertiajs/vue3';
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { FileText, Search } from 'lucide-vue-next';
import api from '@/lib/api';
import type { DataListMeta, DigitalizedItem } from '@/types';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Trash2 } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import DataListTable from '@/components/data/DataListTable.vue';
import DeleteDataModal from '@/components/data/DeleteDataModal.vue';

const props = withDefaults(
    defineProps<{
        /** 'preview' = first N items + See more; 'full' = search + pagination */
        mode: 'preview' | 'full';
        /** Items per page (preview uses this as limit, full uses for pagination) */
        perPage?: number;
        /** For preview mode: URL for "See more" link (e.g. /data) */
        seeMoreHref?: string;
        /** Base path for view links, e.g. /dashboard/data */
        viewBasePath?: string;
        /** Context for item links so Show page can show correct back/breadcrumb: 'dashboard' | 'data' */
        fromContext?: 'dashboard' | 'data';
    }>(),
    {
        perPage: 15,
        viewBasePath: '/dashboard/data',
        fromContext: 'dashboard',
    }
);

const emit = defineEmits<{
    refreshed: [];
}>();

const items = ref<DigitalizedItem[]>([]);
const loading = ref(true);
const error = ref<string | null>(null);
const listPage = ref(1);
const listSearch = ref('');
const listMeta = ref<DataListMeta | null>(null);
let listSearchDebounce: ReturnType<typeof setTimeout> | null = null;

const deleteModalOpen = ref(false);
const itemToDelete = ref<DigitalizedItem | null>(null);
const deleteSelectedModalOpen = ref(false);
const selectedItemsToDelete = ref<DigitalizedItem[]>([]);
const deleteSelectedLoading = ref(false);
const deleteSelectedError = ref<string | null>(null);
const selectedIds = ref<number[]>([]);

const effectivePerPage = props.mode === 'preview' ? 10 : props.perPage;

function viewUrl(id: number): string {
    const base = `${props.viewBasePath.replace(/\/$/, '')}/${id}`;
    return props.fromContext ? `${base}?from=${props.fromContext}` : base;
}

async function loadList() {
    loading.value = true;
    error.value = null;
    try {
    const params = new URLSearchParams({
        page: String(listPage.value),
        per_page: String(effectivePerPage),
    });
    if (listSearch.value.trim()) {
        params.set('search', listSearch.value.trim());
    }
    const { data } = await api.get<{
        data: DigitalizedItem[];
        meta: DataListMeta;
    }>(`/dashboard/api/data?${params}`);
    items.value = Array.isArray(data?.data) ? data.data : [];
    listMeta.value = data?.meta ?? null;
    selectedIds.value = selectedIds.value.filter((id) => items.value.some((i) => i.id === id));
    } catch (e: unknown) {
    const err = e as { response?: { data?: { message?: string } }; message?: string };
    error.value =
        err.response?.data?.message ||
        err.message ||
        'Failed to load';
    } finally {
    loading.value = false;
    }
}

function goToListPage(page: number) {
    listPage.value = Math.max(1, Math.min(listMeta.value?.last_page ?? 1, page));
}

function openDeleteModal(item: DigitalizedItem) {
    itemToDelete.value = item;
    deleteModalOpen.value = true;
}

function onDeleted() {
    loadList();
    emit('refreshed');
}

function openDeleteSelectedModal(items: DigitalizedItem[]) {
    selectedItemsToDelete.value = items;
    deleteSelectedError.value = null;
    deleteSelectedModalOpen.value = true;
}

function selectedItemsForBulkDelete(): DigitalizedItem[] {
    return items.value.filter((i) => selectedIds.value.includes(i.id));
}

function updateSelectedIds(ids: number[]) {
    selectedIds.value = ids;
}

function openBulkDeleteFromHeader() {
    const toDelete = selectedItemsForBulkDelete();
    if (toDelete.length === 0) return;
    openDeleteSelectedModal(toDelete);
}

async function confirmDeleteSelected() {
    const toDelete = selectedItemsToDelete.value;
    if (!toDelete.length) return;
    deleteSelectedLoading.value = true;
    deleteSelectedError.value = null;
    try {
        for (const item of toDelete) {
            await api.delete(`/dashboard/api/data/${item.id}`);
        }
        deleteSelectedModalOpen.value = false;
        selectedItemsToDelete.value = [];
        selectedIds.value = [];
        loadList();
        emit('refreshed');
    } catch (e: unknown) {
        const err = e as { response?: { data?: { message?: string } }; message?: string };
        deleteSelectedError.value = err.response?.data?.message ?? err.message ?? 'Failed to delete';
    } finally {
        deleteSelectedLoading.value = false;
    }
}

watch(listPage, () => loadList());

watch(listSearch, () => {
    if (listSearchDebounce) clearTimeout(listSearchDebounce);
    listSearchDebounce = setTimeout(() => {
        listPage.value = 1;
        loadList();
    }, 300);
});

let processingPollTimer: ReturnType<typeof setInterval> | null = null;
const broadcastUnsubs = ref<Map<number, () => void>>(new Map());

function startProcessingPoll() {
    if (processingPollTimer) return;
    processingPollTimer = setInterval(() => {
        if (items.value.some((i) => i.processing)) loadList();
        else stopProcessingPoll();
    }, 2000);
}

function stopProcessingPoll() {
    if (processingPollTimer) {
        clearInterval(processingPollTimer);
        processingPollTimer = null;
    }
}

/** Map single-record API response to list row shape. */
function recordToListItem(r: {
    id: number;
    name: string;
    status: string;
    digital_data?: { type?: string; status?: string; processing_batches_done?: number; processing_batches_total?: number } | null;
    ai_provider: string | null;
    ai_model: string | null;
    created_at: string | null;
    extraction_duration_seconds?: number | null;
    extraction_started_at?: string | null;
}): DigitalizedItem {
    const dd = r.digital_data ?? {};
    const processing = (dd.status ?? '') === 'processing';
    return {
        id: r.id,
        name: r.name,
        type: dd.type ?? null,
        status: r.status as 'ready' | 'processing' | 'failed',
        processing,
        processing_batches_done: processing ? (dd.processing_batches_done ?? 0) : null,
        processing_batches_total: processing ? (dd.processing_batches_total ?? 0) : null,
        ai_provider: r.ai_provider,
        ai_model: r.ai_model,
        extraction_duration_seconds: r.extraction_duration_seconds ?? null,
        extraction_started_at: r.extraction_started_at ?? null,
        created_at: r.created_at,
    };
}

async function patchRowFromApi(dataId: number) {
    try {
        const { data: record } = await api.get<Parameters<typeof recordToListItem>[0]>(`/dashboard/api/data/${dataId}`);
        const idx = items.value.findIndex((i) => i.id === dataId);
        if (idx === -1) return;
        const next = [...items.value];
        next[idx] = recordToListItem(record);
        items.value = next;
    } catch {
        // Fallback: refresh full list if single fetch fails (e.g. 404)
        await loadList();
    }
}

function updateBroadcastSubscriptions() {
    const processingIds = new Set(items.value.filter((i) => i.processing).map((i) => i.id));
    const current = broadcastUnsubs.value;
    current.forEach((unsub, id) => {
        if (!processingIds.has(id)) {
            unsub();
            current.delete(id);
        }
    });
    processingIds.forEach((id) => {
        if (current.has(id)) return;
        console.debug('[Echo] DataListSection: subscribing to record', id);
        current.set(id, subscribeDataRecord(id, () => {
            console.debug('[Echo] DataListSection: got update for record', id, '→ patching row');
            void patchRowFromApi(id);
        }));
    });
    broadcastUnsubs.value = new Map(current);
}

onMounted(() => {
    loadList();
});

watch(items, (newItems) => {
    const hasProcessing = newItems.some((i) => i.processing);
    if (isBroadcastingEnabled()) {
        stopProcessingPoll();
        if (hasProcessing) updateBroadcastSubscriptions();
        else broadcastUnsubs.value.forEach((unsub) => unsub()), broadcastUnsubs.value.clear();
    } else {
        if (hasProcessing) startProcessingPoll();
        else stopProcessingPoll();
    }
}, { deep: true });

onBeforeUnmount(() => {
    stopProcessingPoll();
    broadcastUnsubs.value.forEach((unsub) => unsub());
    broadcastUnsubs.value.clear();
});

defineExpose({
    loadList,
});
</script>

<template>
    <section class="data-section rounded-2xl border border-border bg-card shadow-sm" aria-labelledby="data-heading">
        <div class="flex flex-col gap-3 border-b border-border/80 p-4 sm:px-5 sm:py-4">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between sm:gap-3">
                <div class="flex min-w-0 flex-1 flex-wrap items-center gap-3 sm:max-w-[50%]">
                    <div class="relative min-w-0 flex-1 sm:min-w-[180px]">
                        <Search
                            class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 shrink-0 text-muted-foreground"
                            aria-hidden
                        />
                        <input
                            v-model="listSearch"
                            type="search"
                            placeholder="Search by name…"
                            class="w-full rounded-lg border-2 border-primary/60 bg-primary/5 py-2 pl-9 pr-3 text-sm font-medium text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-primary focus:bg-background dark:border-primary/50 dark:bg-primary/10 dark:focus:border-primary dark:focus:bg-background"
                            aria-label="Search your items"
                        />
                    </div>
                    <span
                        v-if="listMeta && !loading"
                        class="text-sm text-muted-foreground"
                    >
                        {{ listMeta.total.toLocaleString() }} item{{ listMeta.total !== 1 ? 's' : '' }}
                    </span>
                </div>
                <Link
                    v-if="seeMoreHref"
                    :href="seeMoreHref"
                    class="hidden shrink-0 text-sm font-bold text-primary underline-offset-4 hover:underline sm:inline sm:text-base"
                >
                    View All
                </Link>
            </div>
            <div
                v-if="selectedIds.length > 0"
                class="flex flex-wrap items-center gap-2 rounded-lg border border-border bg-muted/40 px-3 py-2"
            >
                <span class="text-sm font-medium text-foreground">{{ selectedIds.length }} selected</span>
                <Button variant="outline" size="sm" class="h-8" @click="updateSelectedIds([])">
                    Clear
                </Button>
                <button
                    type="button"
                    class="inline-flex items-center gap-1.5 rounded-lg p-2 text-destructive hover:bg-destructive/10"
                    title="Delete selected"
                    aria-label="Delete selected items"
                    @click="openBulkDeleteFromHeader"
                >
                    <Trash2 class="h-4 w-4" />
                </button>
            </div>
        </div>
        <div class="p-4 sm:px-5 sm:pb-5">
            <div
                v-if="loading"
                class="flex flex-col items-center justify-center gap-3 rounded-xl bg-muted/30 py-16 text-center"
            >
                <div class="h-8 w-8 animate-spin rounded-full border-2 border-primary border-t-transparent" aria-hidden />
                <p class="text-sm text-muted-foreground">Loading…</p>
            </div>
            <div
                v-else-if="error"
                class="flex flex-col items-center gap-3 rounded-xl bg-destructive/10 py-16 text-center"
            >
                <p class="text-sm font-medium text-destructive">{{ error }}</p>
            </div>
            <div
                v-else-if="items.length === 0"
                class="flex flex-col items-center gap-3 rounded-xl bg-muted/30 py-16 text-center"
            >
                <FileText class="h-12 w-12 text-muted-foreground/70" aria-hidden />
                <p class="text-sm text-muted-foreground">
                    {{ listSearch.trim() ? 'No items match your search.' : 'No items yet. Add one above.' }}
                </p>
            </div>
            <template v-else>
                <DataListTable
                    :items="items"
                    :selected-ids="selectedIds"
                    :view-url="viewUrl"
                    @update:selected-ids="updateSelectedIds"
                    @delete-request="openDeleteModal"
                    @delete-selected-request="openDeleteSelectedModal"
                />
                <!-- Preview: See more -->
                <div
                    v-if="mode === 'preview' && seeMoreHref && listMeta && listMeta.total > effectivePerPage"
                    class="mt-4 flex justify-center"
                >
                    <Button variant="outline" size="sm" class="rounded-lg" as-child>
                        <Link :href="seeMoreHref">View All</Link>
                    </Button>
                </div>
                <!-- Full: Pagination -->
                <div
                    v-if="mode === 'full' && listMeta && listMeta.last_page > 1 && !loading && items.length > 0"
                    class="mt-4 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-border/80 bg-muted/20 px-4 py-3 sm:justify-end"
                >
                    <span class="text-sm text-muted-foreground sm:order-2">
                        Page {{ listMeta.current_page }} of {{ listMeta.last_page }}
                    </span>
                    <div class="flex gap-2 sm:order-1">
                        <Button
                            variant="outline"
                            size="sm"
                            class="rounded-lg"
                            :disabled="listPage <= 1"
                            @click="goToListPage(listPage - 1)"
                        >
                            Previous
                        </Button>
                        <Button
                            variant="outline"
                            size="sm"
                            class="rounded-lg"
                            :disabled="listPage >= listMeta.last_page"
                            @click="goToListPage(listPage + 1)"
                        >
                            Next
                        </Button>
                    </div>
                </div>
            </template>
        </div>
    </section>

    <DeleteDataModal
        :open="deleteModalOpen"
        :item="itemToDelete"
        @update:open="deleteModalOpen = $event"
        @deleted="onDeleted"
    />

    <Dialog :open="deleteSelectedModalOpen" @update:open="deleteSelectedModalOpen = $event">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>Delete selected items?</DialogTitle>
            </DialogHeader>
            <p class="text-sm text-muted-foreground">
                {{ selectedItemsToDelete.length }} item{{ selectedItemsToDelete.length !== 1 ? 's' : '' }} will be
                permanently deleted. This cannot be undone.
            </p>
            <p v-if="deleteSelectedError" class="text-sm text-destructive">
                {{ deleteSelectedError }}
            </p>
            <DialogFooter class="gap-2">
                <DialogClose as-child>
                    <Button variant="secondary" @click="deleteSelectedModalOpen = false">
                        Cancel
                    </Button>
                </DialogClose>
                <Button
                    variant="destructive"
                    :disabled="deleteSelectedLoading"
                    @click="confirmDeleteSelected"
                >
                    {{ deleteSelectedLoading ? 'Deleting…' : 'Delete' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
