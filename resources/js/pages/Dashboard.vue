<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, onMounted, ref, watch } from 'vue';
import { ChevronDown, FileText, Plus, Search, Table as TableIcon, Trash2, Upload } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import api from '@/lib/api';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type DigitalizedItem = {
    id: number;
    name: string;
    type: string | null;
    ai_provider: string | null;
    ai_model: string | null;
    created_at: string | null;
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
    },
];

const items = ref<DigitalizedItem[]>([]);
const loading = ref(true);
const error = ref<string | null>(null);
const listPage = ref(1);
const listSearch = ref('');
const listMeta = ref<{
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
} | null>(null);
let listSearchDebounce: ReturnType<typeof setTimeout> | null = null;
const uploadLoading = ref(false);
const uploadProgress = ref(0);
const uploadPhase = ref<'uploading' | 'extracting'>('uploading');
const uploadError = ref<string | null>(null);
const uploadSuccess = ref(false);
const fileInput = ref<HTMLInputElement | null>(null);

const pendingFile = ref<File | null>(null);
const uploadName = ref('');

const deleteModalOpen = ref(false);
const itemToDelete = ref<DigitalizedItem | null>(null);
const deleteConfirmName = ref('');
const deleteLoading = ref(false);
const deleteError = ref<string | null>(null);

const UPLOAD_SEEN_KEY = 'dashboard_upload_seen';
const uploadSectionOpen = ref(!localStorage.getItem(UPLOAD_SEEN_KEY));

const ACCEPT =
    'image/jpeg,image/png,image/gif,image/webp,video/mp4,video/webm';

async function loadList() {
    loading.value = true;
    error.value = null;
    try {
        const params = new URLSearchParams({
            page: String(listPage.value),
            per_page: '15',
        });
        if (listSearch.value.trim()) params.set('search', listSearch.value.trim());
        const { data } = await api.get<{
            data: DigitalizedItem[];
            meta: { current_page: number; last_page: number; per_page: number; total: number };
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
    localStorage.setItem(UPLOAD_SEEN_KEY, '1');
});

function formatDate(iso: string | null): string {
    if (!iso) return '—';
    const d = new Date(iso);
    if (Number.isNaN(d.getTime())) return '—';
    return d.toLocaleString();
}

function viewUrl(id: number): string {
    return `/dashboard/data/${id}`;
}

function openFilePicker() {
    uploadError.value = null;
    uploadSuccess.value = false;
    fileInput.value?.click();
}

function onFileChange(e: Event) {
    const input = e.target as HTMLInputElement;
    const file = input.files?.[0];
    if (!file) return;
    pendingFile.value = file;
    if (!uploadName.value?.trim()) {
        uploadName.value = file.name.replace(/\.[^.]+$/, '') || file.name;
    }
}

function onDrop(e: DragEvent) {
    e.preventDefault();
    const file = e.dataTransfer?.files?.[0];
    if (!file) return;
    uploadError.value = null;
    uploadSuccess.value = false;
    pendingFile.value = file;
    if (!uploadName.value?.trim()) {
        uploadName.value = file.name.replace(/\.[^.]+$/, '') || file.name;
    }
}

function resetUploadForm() {
    pendingFile.value = null;
    uploadName.value = '';
    if (fileInput.value) fileInput.value.value = '';
}

function startUpload() {
    const file = pendingFile.value;
    const name = uploadName.value?.trim();
    if (!file || !name) return;
    upload(null, file, name);
}

function onDragOver(e: DragEvent) {
    e.preventDefault();
}

async function upload(_input: HTMLInputElement | null, file: File, nameOverride?: string) {
    const allowed = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'video/mp4',
        'video/webm',
    ];
    if (!allowed.includes(file.type)) {
        uploadError.value = 'Allowed: images (JPEG, PNG, GIF, WebP) or video (MP4, WebM).';
        return;
    }
    if (file.size > 20 * 1024 * 1024) {
        uploadError.value = 'File must be under 20 MB.';
        return;
    }

    uploadLoading.value = true;
    uploadProgress.value = 0;
    uploadPhase.value = 'uploading';
    uploadError.value = null;
    uploadSuccess.value = false;

    const base = nameOverride ?? (file.name.replace(/\.[^.]+$/, '') || file.name);
    const name = (base && base.trim()) ? base.trim() : file.name;
    const formData = new FormData();
    formData.append('file', file);
    formData.append('name', name);

    try {
        const { data } = await api.post<{
            id: number;
            name: string;
            digital_data?: { type?: string };
        }>('/dashboard/digitalize', formData, {
            timeout: 300000,
            onUploadProgress(ev: { loaded: number; total?: number }) {
                if (ev.total && ev.total > 0) {
                    uploadProgress.value = Math.round((ev.loaded / ev.total) * 100);
                    if (uploadProgress.value >= 100) {
                        uploadPhase.value = 'extracting';
                    }
                }
            },
        });
        uploadProgress.value = 100;
        uploadPhase.value = 'extracting';
        uploadSuccess.value = true;
        listPage.value = 1;
        await loadList();
        resetUploadForm();
        setTimeout(() => { uploadSuccess.value = false; }, 3000);
    } catch (e: unknown) {
        const err = e as { response?: { data?: { message?: string } }; message?: string };
        uploadError.value =
            err.response?.data?.message ||
            err.message ||
            'Upload failed';
    } finally {
        uploadLoading.value = false;
        uploadProgress.value = 0;
        uploadPhase.value = 'uploading';
    }
}

function openDeleteModal(item: DigitalizedItem) {
    itemToDelete.value = item;
    deleteConfirmName.value = '';
    deleteError.value = null;
    deleteModalOpen.value = true;
}

function closeDeleteModal() {
    deleteModalOpen.value = false;
    itemToDelete.value = null;
    deleteConfirmName.value = '';
    deleteError.value = null;
}

const canConfirmDelete = computed(() => {
    const item = itemToDelete.value;
    if (!item) return false;
    return deleteConfirmName.value.trim() === item.name;
});

async function confirmDelete() {
    const item = itemToDelete.value;
    if (!item || !canConfirmDelete.value) return;
    deleteLoading.value = true;
    deleteError.value = null;
    try {
        await api.delete(`/dashboard/api/data/${item.id}`);
        await loadList();
        closeDeleteModal();
    } catch (e: unknown) {
        const err = e as { response?: { data?: { message?: string } }; message?: string };
        deleteError.value = err.response?.data?.message ?? err.message ?? 'Failed to delete';
    } finally {
        deleteLoading.value = false;
    }
}
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="dashboard-page flex min-h-full flex-1 flex-col overflow-x-auto">
            <!-- Page header -->
            <header class="dashboard-header shrink-0 border-b border-border/80 bg-background/95 px-4 py-5 backdrop-blur sm:px-6">
                <h1 class="text-xl font-semibold tracking-tight text-foreground sm:text-2xl">
                    Dashboard
                </h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    Upload photos or videos to digitalize notes, tables, and documents. Your items appear below.
                </p>
            </header>

            <div class="flex flex-1 flex-col gap-6 p-4 sm:p-6">
                <!-- Upload section -->
                <section class="upload-section" aria-labelledby="upload-heading">
                    <Button
                        type="button"
                        variant="outline"
                        size="lg"
                        class="w-full cursor-pointer justify-between rounded-xl border-border bg-card px-4 py-3 text-left font-medium shadow-sm transition-colors hover:bg-muted/50 sm:w-auto sm:min-w-[200px]"
                        @click="uploadSectionOpen = !uploadSectionOpen"
                    >
                        <span class="flex items-center gap-2">
                            <Plus class="h-5 w-5 shrink-0 text-primary" />
                            Add new
                        </span>
                        <ChevronDown class="h-4 w-4 shrink-0 text-muted-foreground transition-transform duration-200" :class="{ 'rotate-180': uploadSectionOpen }" />
                    </Button>
                    <div
                        v-show="uploadSectionOpen"
                        id="upload-heading"
                        class="upload-card mt-4 rounded-2xl border border-border bg-card p-5 shadow-sm sm:p-6"
                    >
                        <p class="mb-4 text-sm text-muted-foreground">
                            Add a photo or video — we’ll extract the text or table.
                        </p>
                        <div class="mb-4">
                            <Label for="upload-name-inline" class="text-xs font-medium text-muted-foreground">Name</Label>
                            <Input
                                id="upload-name-inline"
                                v-model="uploadName"
                                placeholder="e.g. Sales log March 2024"
                                class="mt-1 rounded-lg"
                                :disabled="uploadLoading"
                                @keydown.enter.prevent="uploadName.trim() && pendingFile && startUpload()"
                            />
                        </div>
                        <input
                            ref="fileInput"
                            type="file"
                            :accept="ACCEPT"
                            class="hidden"
                            @change="onFileChange"
                        />
                        <div
                            class="upload-zone cursor-pointer rounded-xl border-2 border-dashed border-border bg-muted/20 p-6 text-center transition-colors hover:border-primary/40 hover:bg-muted/40"
                            :class="{ 'border-primary/50 bg-primary/5': uploadLoading }"
                            role="button"
                            tabindex="0"
                            @click="openFilePicker"
                            @drop="onDrop"
                            @dragover="onDragOver"
                            @keydown.enter="openFilePicker"
                            @keydown.space.prevent="openFilePicker"
                        >
                            <Upload class="mx-auto mb-2 h-8 w-8 text-muted-foreground" />
                            <p class="text-sm font-medium text-foreground">
                                {{ pendingFile ? pendingFile.name : 'Drop a photo or video here, or click to choose' }}
                            </p>
                            <p v-if="!pendingFile" class="mt-1 text-xs text-muted-foreground">
                                Images, MP4, WebM · max 20 MB
                            </p>
                            <div v-if="uploadLoading" class="mt-4 space-y-2">
                                <div class="flex items-center justify-between text-sm text-muted-foreground">
                                    <span>{{ uploadPhase === 'uploading' ? `Uploading… ${uploadProgress}%` : 'Extracting…' }}</span>
                                    <span v-if="uploadPhase === 'uploading'" class="tabular-nums">{{ uploadProgress }}%</span>
                                </div>
                                <div class="h-1.5 w-full overflow-hidden rounded-full bg-muted">
                                    <div
                                        class="h-full rounded-full bg-primary transition-[width] duration-300 ease-out"
                                        :style="{ width: uploadPhase === 'extracting' ? '100%' : `${uploadProgress}%` }"
                                    />
                                </div>
                            </div>
                            <p v-else-if="uploadError" class="mt-3 text-sm text-destructive">
                                {{ uploadError }}
                            </p>
                            <p v-else-if="uploadSuccess" class="mt-3 text-sm font-medium text-green-600 dark:text-green-400">
                                ✓ Added below.
                            </p>
                        </div>
                        <div class="mt-4 flex items-center gap-3">
                            <Button
                                type="button"
                                class="rounded-lg"
                                :disabled="!uploadName.trim() || !pendingFile || uploadLoading"
                                @click="startUpload"
                            >
                                {{ pendingFile ? 'Extract & add' : 'Upload' }}
                            </Button>
                            <span v-if="!pendingFile" class="text-xs text-muted-foreground">
                                Tip: Multiple pages? One photo per page or pause 1–2 sec per page in video.
                            </span>
                        </div>
                    </div>
                </section>

                <!-- Data list -->
                <section class="data-section rounded-2xl border border-border bg-card shadow-sm" aria-labelledby="data-heading">
                    <div class="flex flex-col gap-4 border-b border-border/80 p-4 sm:flex-row sm:items-center sm:justify-between sm:gap-0 sm:px-5 sm:py-4">
                        <h2 id="data-heading" class="text-base font-semibold text-foreground sm:text-lg">
                            Your digitalized data
                        </h2>
                        <div class="flex flex-wrap items-center gap-3">
                            <div class="relative min-w-0 flex-1 sm:max-w-[220px]">
                                <Search
                                    class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 shrink-0 text-muted-foreground"
                                    aria-hidden
                                />
                                <input
                                    v-model="listSearch"
                                    type="search"
                                    placeholder="Search by name…"
                                    class="w-full rounded-xl border border-border bg-background py-2.5 pl-9 pr-3 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring"
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
                        <div v-else class="-mx-1 overflow-x-auto overscroll-x-contain sm:mx-0">
                            <table class="w-full min-w-[320px] text-left text-sm sm:min-w-[400px]" role="grid">
                                <thead>
                                    <tr class="border-b border-border">
                                        <th class="pb-3 pr-3 font-medium text-muted-foreground sm:pr-4">Name</th>
                                        <th class="pb-3 pr-3 font-medium text-muted-foreground sm:pr-4">Type</th>
                                        <th class="hidden pb-3 pr-3 font-medium text-muted-foreground md:table-cell sm:pr-4">AI</th>
                                        <th class="hidden pb-3 pr-3 font-medium text-muted-foreground sm:table-cell sm:pr-4">Created</th>
                                        <th class="pb-3 pr-3 font-medium text-muted-foreground sm:pr-4"> </th>
                                        <th class="w-10 pb-3 pl-0 font-medium text-muted-foreground" aria-label="Actions"> </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="(item, index) in items"
                                        :key="item.id"
                                        class="group border-b border-border/80 transition-colors last:border-b-0 hover:bg-muted/40"
                                        :class="{ 'bg-muted/20': index % 2 === 1 }"
                                        @click="router.visit(viewUrl(item.id))"
                                    >
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
                                        <td class="hidden py-3.5 pr-3 text-muted-foreground md:table-cell sm:pr-4">
                                            <span v-if="item.ai_provider || item.ai_model" class="text-xs">
                                                {{ [item.ai_provider, item.ai_model].filter(Boolean).join(' · ') }}
                                            </span>
                                            <span v-else class="text-muted-foreground">—</span>
                                        </td>
                                        <td class="hidden py-3.5 pr-3 text-muted-foreground sm:table-cell sm:pr-4">
                                            {{ formatDate(item.created_at) }}
                                        </td>
                                        <td class="py-3.5 pr-3 sm:pr-2">
                                            <Link
                                                :href="viewUrl(item.id)"
                                                class="text-primary font-medium hover:underline"
                                                @click.stop
                                            >
                                                View
                                            </Link>
                                        </td>
                                        <td class="w-10 py-3.5 pl-0">
                                            <button
                                                type="button"
                                                class="rounded-lg p-2 text-muted-foreground opacity-70 transition-opacity hover:bg-destructive/10 hover:text-destructive hover:opacity-100"
                                                title="Delete"
                                                aria-label="Delete this item"
                                                @click.stop="openDeleteModal(item)"
                                            >
                                                <Trash2 class="h-4 w-4" />
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div
                            v-if="listMeta && listMeta.last_page > 1 && !loading && items.length > 0"
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
                    </div>
                </section>
            </div>
        </div>

        <!-- Delete: require typing data name to confirm -->
        <Dialog :open="deleteModalOpen" @update:open="deleteModalOpen = $event">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Delete this data?</DialogTitle>
                </DialogHeader>
                <p class="text-sm text-muted-foreground">
                    This cannot be undone. Type the data name below to confirm.
                </p>
                <p v-if="itemToDelete" class="text-sm font-medium text-foreground">
                    Name: <span class="font-mono">{{ itemToDelete.name }}</span>
                </p>
                <div class="grid gap-2 py-2">
                    <Label for="delete-confirm-name">Type the name to confirm</Label>
                    <Input
                        id="delete-confirm-name"
                        v-model="deleteConfirmName"
                        placeholder="Enter the exact name"
                        class="font-mono"
                        @keydown.enter.prevent="canConfirmDelete ? confirmDelete() : null"
                    />
                </div>
                <p v-if="deleteError" class="text-sm text-destructive">
                    {{ deleteError }}
                </p>
                <DialogFooter class="gap-2">
                    <DialogClose as-child>
                        <Button variant="secondary" @click="closeDeleteModal">
                            Cancel
                        </Button>
                    </DialogClose>
                    <Button
                        variant="destructive"
                        :disabled="!canConfirmDelete || deleteLoading"
                        @click="confirmDelete"
                    >
                        {{ deleteLoading ? 'Deleting…' : 'Delete' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
