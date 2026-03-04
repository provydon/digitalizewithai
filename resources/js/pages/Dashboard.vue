<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, onMounted, ref, watch } from 'vue';
import { FileText, Search, Table as TableIcon, Trash2, Upload } from 'lucide-vue-next';
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

const uploadModalOpen = ref(false);
const pendingFile = ref<File | null>(null);
const uploadName = ref('');

const deleteModalOpen = ref(false);
const itemToDelete = ref<DigitalizedItem | null>(null);
const deleteConfirmName = ref('');
const deleteLoading = ref(false);
const deleteError = ref<string | null>(null);

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

onMounted(loadList);

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
    input.value = '';
    openUploadModal(file);
}

function onDrop(e: DragEvent) {
    e.preventDefault();
    const file = e.dataTransfer?.files?.[0];
    if (!file) return;
    uploadError.value = null;
    uploadSuccess.value = false;
    openUploadModal(file);
}

function openUploadModal(file: File) {
    const baseName = file.name.replace(/\.[^.]+$/, '') || file.name;
    uploadName.value = baseName;
    pendingFile.value = file;
    uploadModalOpen.value = true;
}

function closeUploadModal() {
    uploadModalOpen.value = false;
    pendingFile.value = null;
    uploadName.value = '';
}

function startUpload() {
    const file = pendingFile.value;
    const name = uploadName.value?.trim();
    if (!file || !name) return;
    closeUploadModal();
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
        <div
            class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl px-3 py-4 sm:p-4"
        >
            <!-- Upload: digitalize from UI (uses same API flow via POST /dashboard/digitalize) -->
            <div
                class="rounded-xl border border-sidebar-border/70 bg-card p-3 shadow-sm dark:border-sidebar-border sm:p-4"
            >
                <h2 class="mb-3 text-lg font-semibold text-foreground">
                    Add a handwritten note or table
                </h2>
                <p class="mb-3 text-sm text-muted-foreground">
                    Upload a photo or video of handwritten notes, sales figures, logs, records, or any table — we'll extract the content and add it below.
                </p>
                <input
                    ref="fileInput"
                    type="file"
                    :accept="ACCEPT"
                    class="hidden"
                    @change="onFileChange"
                />
                <div
                    class="cursor-pointer rounded-lg border-2 border-dashed border-sidebar-border/70 bg-muted/30 p-6 text-center transition-colors dark:border-sidebar-border"
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
                    <p class="mb-3 text-sm text-muted-foreground">
                        Add a handwritten note or table of sales, logs, records, etc.
                        Drag a file here or
                        <button
                            type="button"
                            class="font-medium text-primary underline-offset-4 hover:underline"
                            :disabled="uploadLoading"
                            @click="openFilePicker"
                        >
                            choose a file
                        </button>
                    </p>
                    <p class="mb-3 text-xs text-muted-foreground">
                        Images: JPEG, PNG, GIF, WebP. Video: MP4, WebM. Max 20 MB.
                    </p>
                    <div v-if="uploadLoading" class="mt-2 space-y-2">
                        <div class="flex items-center justify-between text-sm text-muted-foreground">
                            <span>{{ uploadPhase === 'uploading' ? `Uploading… ${uploadProgress}%` : 'Extracting content…' }}</span>
                            <span v-if="uploadPhase === 'uploading'" class="tabular-nums">{{ uploadProgress }}%</span>
                        </div>
                        <div class="h-2 w-full overflow-hidden rounded-full bg-muted">
                            <div
                                class="h-full rounded-full bg-primary transition-[width] duration-300 ease-out"
                                :style="{ width: uploadPhase === 'extracting' ? '100%' : `${uploadProgress}%` }"
                            />
                        </div>
                    </div>
                    <p v-else-if="uploadError" class="text-sm text-destructive">
                        {{ uploadError }}
                    </p>
                    <p v-else-if="uploadSuccess" class="text-sm text-green-600 dark:text-green-400">
                        Added. It appears in the list below.
                    </p>
                </div>
            </div>

            <!-- List: backend pagination + search -->
            <div class="rounded-xl border border-sidebar-border/70 bg-card p-3 shadow-sm dark:border-sidebar-border sm:p-4">
                <h2 class="mb-3 text-lg font-semibold text-foreground">
                    Digitalized data
                </h2>
                <div class="mb-3 flex flex-wrap items-center gap-2">
                    <div class="relative min-w-0 flex-1 basis-full sm:max-w-xs">
                        <Search
                            class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 shrink-0 text-muted-foreground"
                        />
                        <input
                            v-model="listSearch"
                            type="search"
                            placeholder="Search by name..."
                            class="w-full rounded-lg border border-sidebar-border/70 bg-background py-2 pl-9 pr-3 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring dark:border-sidebar-border"
                        />
                    </div>
                    <span
                        v-if="listMeta && !loading"
                        class="text-sm text-muted-foreground"
                    >
                        {{ listMeta.total.toLocaleString() }} item{{ listMeta.total !== 1 ? 's' : '' }}
                    </span>
                </div>
                <div
                    v-if="loading"
                    class="rounded-lg border border-dashed border-sidebar-border/70 py-12 text-center text-muted-foreground dark:border-sidebar-border"
                >
                    Loading…
                </div>
                <div
                    v-else-if="error"
                    class="rounded-lg border border-dashed border-sidebar-border/70 py-12 text-center text-destructive dark:border-sidebar-border"
                >
                    {{ error }}
                </div>
                <div
                    v-else-if="items.length === 0"
                    class="rounded-lg border border-dashed border-sidebar-border/70 py-12 text-center text-muted-foreground dark:border-sidebar-border"
                >
                    {{ listSearch.trim() ? 'No items match your search.' : 'No digitalized items yet. Upload a file above or use the API.' }}
                </div>
                <div v-else class="-mx-1 overflow-x-auto overscroll-x-contain sm:mx-0">
                    <table class="w-full min-w-[320px] text-left text-sm sm:min-w-[400px]">
                        <thead>
                            <tr class="border-b border-sidebar-border/70 dark:border-sidebar-border">
                                <th class="pb-3 pr-2 font-medium text-muted-foreground sm:pr-4">Name</th>
                                <th class="pb-3 pr-2 font-medium text-muted-foreground sm:pr-4">Type</th>
                                <th class="hidden pb-3 pr-2 font-medium text-muted-foreground sm:table-cell sm:pr-4">Created</th>
                                <th class="pb-3 pr-2 font-medium text-muted-foreground sm:pr-4"> </th>
                                <th class="w-10 pb-3 pl-0 font-medium text-muted-foreground" aria-label="Actions"> </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="item in items"
                                :key="item.id"
                                class="cursor-pointer border-b border-sidebar-border/70 transition-colors hover:bg-muted/50 dark:border-sidebar-border"
                                @click="router.visit(viewUrl(item.id))"
                            >
                                <td class="py-2.5 pr-2 sm:py-3 sm:pr-4">
                                    <Link
                                        :href="viewUrl(item.id)"
                                        class="font-medium text-foreground underline-offset-4 hover:underline"
                                        @click.stop
                                    >
                                        {{ item.name }}
                                    </Link>
                                </td>
                                <td class="py-2.5 pr-2 sm:py-3 sm:pr-4">
                                    <span
                                        v-if="item.type === 'doc'"
                                        class="inline-flex items-center gap-1 rounded-md bg-muted px-2 py-0.5 text-muted-foreground"
                                    >
                                        <FileText class="h-3.5 w-3.5" />
                                        doc
                                    </span>
                                    <span
                                        v-else-if="item.type === 'table'"
                                        class="inline-flex items-center gap-1 rounded-md bg-muted px-2 py-0.5 text-muted-foreground"
                                    >
                                        <TableIcon class="h-3.5 w-3.5" />
                                        table
                                    </span>
                                    <span v-else class="text-muted-foreground">—</span>
                                </td>
                                <td class="hidden py-2.5 pr-2 text-muted-foreground sm:table-cell sm:py-3 sm:pr-4">
                                    {{ formatDate(item.created_at) }}
                                </td>
                                <td class="py-2.5 pr-2 sm:py-3 sm:pr-2">
                                    <Link
                                        :href="viewUrl(item.id)"
                                        class="text-primary hover:underline"
                                        @click.stop
                                    >
                                        View
                                    </Link>
                                </td>
                                <td class="w-10 py-2.5 pl-0 sm:py-3">
                                    <button
                                        type="button"
                                        class="rounded p-1.5 text-muted-foreground hover:bg-destructive/10 hover:text-destructive"
                                        title="Delete"
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
                    class="mt-3 flex flex-wrap items-center gap-2 text-sm"
                >
                    <button
                        type="button"
                        class="rounded-lg border border-sidebar-border/70 px-3 py-1.5 text-foreground hover:bg-muted/60 disabled:opacity-50 dark:border-sidebar-border"
                        :disabled="listPage <= 1"
                        @click="goToListPage(listPage - 1)"
                    >
                        Previous
                    </button>
                    <span class="text-muted-foreground">
                        Page {{ listMeta.current_page }} of {{ listMeta.last_page }}
                    </span>
                    <button
                        type="button"
                        class="rounded-lg border border-sidebar-border/70 px-3 py-1.5 text-foreground hover:bg-muted/60 disabled:opacity-50 dark:border-sidebar-border"
                        :disabled="listPage >= listMeta.last_page"
                        @click="goToListPage(listPage + 1)"
                    >
                        Next
                    </button>
                </div>
            </div>
        </div>

        <!-- Upload: require name before starting -->
        <Dialog :open="uploadModalOpen" @update:open="uploadModalOpen = $event">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Name this data</DialogTitle>
                </DialogHeader>
                <p class="text-sm text-muted-foreground">
                    Give a name for the uploaded file. Upload will start when you click Start.
                </p>
                <div class="grid gap-2 py-2">
                    <Label for="upload-name">Name</Label>
                    <Input
                        id="upload-name"
                        v-model="uploadName"
                        placeholder="e.g. Sales log March 2024"
                        @keydown.enter.prevent="startUpload()"
                    />
                    <p v-if="pendingFile" class="text-xs text-muted-foreground">
                        File: {{ pendingFile.name }}
                    </p>
                </div>
                <DialogFooter class="gap-2">
                    <DialogClose as-child>
                        <Button variant="secondary" @click="closeUploadModal">
                            Cancel
                        </Button>
                    </DialogClose>
                    <Button
                        :disabled="!uploadName.trim()"
                        @click="startUpload"
                    >
                        Start upload
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

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
