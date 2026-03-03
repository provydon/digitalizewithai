<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
import { FileText, Table as TableIcon, Upload } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import api from '@/lib/api';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

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
const uploadLoading = ref(false);
const uploadProgress = ref(0);
const uploadPhase = ref<'uploading' | 'extracting'>('uploading');
const uploadError = ref<string | null>(null);
const uploadSuccess = ref(false);
const fileInput = ref<HTMLInputElement | null>(null);

const ACCEPT =
    'image/jpeg,image/png,image/gif,image/webp,video/mp4,video/webm';

async function loadList() {
    loading.value = true;
    error.value = null;
    try {
        const { data } = await api.get<{ data: DigitalizedItem[] }>('/dashboard/api/data');
        items.value = Array.isArray(data?.data) ? data.data : [];
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
    upload(input, file);
    input.value = '';
}

function onDrop(e: DragEvent) {
    e.preventDefault();
    const file = e.dataTransfer?.files?.[0];
    if (!file) return;
    uploadError.value = null;
    uploadSuccess.value = false;
    upload(null, file);
}

function onDragOver(e: DragEvent) {
    e.preventDefault();
}

async function upload(_input: HTMLInputElement | null, file: File) {
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

    const formData = new FormData();
    formData.append('file', file);
    formData.append('name', file.name.replace(/\.[^.]+$/, '') || file.name);

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
        items.value = [
            {
                id: data.id,
                name: data.name,
                type: data.digital_data?.type ?? null,
                created_at: new Date().toISOString(),
            },
            ...items.value,
        ];
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
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
        >
            <!-- Upload: digitalize from UI (uses same API flow via POST /dashboard/digitalize) -->
            <div
                class="rounded-xl border border-sidebar-border/70 bg-card p-4 shadow-sm dark:border-sidebar-border"
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

            <!-- List -->
            <div class="rounded-xl border border-sidebar-border/70 bg-card p-4 shadow-sm dark:border-sidebar-border">
                <h2 class="mb-4 text-lg font-semibold text-foreground">
                    Digitalized data
                </h2>
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
                    No digitalized items yet. Upload a file above or use the API.
                </div>
                <div v-else class="overflow-x-auto">
                    <table class="w-full min-w-[400px] text-left text-sm">
                        <thead>
                            <tr class="border-b border-sidebar-border/70 dark:border-sidebar-border">
                                <th class="pb-3 pr-4 font-medium text-muted-foreground">Name</th>
                                <th class="pb-3 pr-4 font-medium text-muted-foreground">Type</th>
                                <th class="pb-3 pr-4 font-medium text-muted-foreground">Created</th>
                                <th class="pb-3 font-medium text-muted-foreground"> </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="item in items"
                                :key="item.id"
                                class="cursor-pointer border-b border-sidebar-border/70 transition-colors hover:bg-muted/50 dark:border-sidebar-border"
                                @click="router.visit(viewUrl(item.id))"
                            >
                                <td class="py-3 pr-4">
                                    <Link
                                        :href="viewUrl(item.id)"
                                        class="font-medium text-foreground underline-offset-4 hover:underline"
                                        @click.stop
                                    >
                                        {{ item.name }}
                                    </Link>
                                </td>
                                <td class="py-3 pr-4">
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
                                <td class="py-3 pr-4 text-muted-foreground">
                                    {{ formatDate(item.created_at) }}
                                </td>
                                <td class="py-3">
                                    <Link
                                        :href="viewUrl(item.id)"
                                        class="text-primary hover:underline"
                                        @click.stop
                                    >
                                        View
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
