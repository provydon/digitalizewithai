<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { onMounted, ref, watch } from 'vue';
import { FileText, Search } from 'lucide-vue-next';
import api from '@/lib/api';
import type { DataListMeta, DigitalizedItem } from '@/types';
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

watch(listPage, () => loadList());

watch(listSearch, () => {
    if (listSearchDebounce) clearTimeout(listSearchDebounce);
    listSearchDebounce = setTimeout(() => {
        listPage.value = 1;
        loadList();
    }, 300);
});

onMounted(() => {
    loadList();
});

defineExpose({
    loadList,
});
</script>

<template>
    <section class="data-section rounded-2xl border border-border bg-card shadow-sm" aria-labelledby="data-heading">
        <div class="flex flex-col gap-4 border-b border-border/80 p-4 sm:flex-row sm:items-center sm:justify-between sm:gap-3 sm:px-5 sm:py-4">
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
                        aria-label="Search digitalized items"
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
                    :view-url="viewUrl"
                    @delete-request="openDeleteModal"
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
                    <span class="text-sm text-muted-foreground dark:text-gray-400 sm:order-2">
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
</template>
