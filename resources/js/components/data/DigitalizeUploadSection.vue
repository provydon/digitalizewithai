<script setup lang="ts">
import { ChevronDown, Plus, Upload } from 'lucide-vue-next';
import { onMounted, ref } from 'vue';
import { Button } from '@/components/ui/button';
import api from '@/lib/api';

const props = withDefaults(
    defineProps<{
        /** localStorage key to remember if user has seen the upload section (collapse by default after first visit) */
        storageKey?: string;
    }>(),
    { storageKey: 'dashboard_upload_seen' }
);

const emit = defineEmits<{
    /** Emitted when backend creates a new data row (202) – parent should refresh list so the new row appears. */
    'item-created': [];
    uploaded: [];
}>();

const ACCEPT = 'image/*,video/*';

/** Max file size in bytes; synced from backend via digitalize-options (default 100 MB). */
const maxFileSizeBytes = ref(100 * 1024 * 1024);

const uploadSectionOpen = ref(false);
const uploadLoading = ref(false);
const uploadProgress = ref(0);
const uploadPhase = ref<'uploading' | 'extracting'>('uploading');
const uploadError = ref<string | null>(null);
const uploadSuccess = ref(false);
const uploadCount = ref<{ done: number; total: number }>({ done: 0, total: 0 });
const extractingBatches = ref<{ done: number; total: number } | null>(null);
const fileInput = ref<HTMLInputElement | null>(null);
const pendingFiles = ref<File[]>([]);

/** Allow any file within size limit; backend validates type and returns a clear error if unsupported. */
function filterFiles(files: FileList | File[]): File[] {
    const max = maxFileSizeBytes.value;
    return Array.from(files).filter((file) => file.size > 0 && file.size <= max);
}

function openFilePicker() {
    uploadError.value = null;
    uploadSuccess.value = false;
    fileInput.value?.click();
}

function onFileChange(e: Event) {
    const input = e.target as HTMLInputElement;
    const files = input.files;
    if (!files?.length) return;
    const valid = filterFiles(files);
    if (valid.length) {
        pendingFiles.value = valid;
        uploadError.value = null;
    } else {
        const maxMb = Math.round(maxFileSizeBytes.value / (1024 * 1024));
        uploadError.value = `File may be too large. Max ${maxMb} MB.`;
    }
    input.value = '';
}

function onDrop(e: DragEvent) {
    e.preventDefault();
    const files = e.dataTransfer?.files;
    if (!files?.length) return;
    const valid = filterFiles(files);
    if (valid.length) {
        uploadError.value = null;
        uploadSuccess.value = false;
        pendingFiles.value = valid;
    } else {
        const maxMb = Math.round(maxFileSizeBytes.value / (1024 * 1024));
        uploadError.value = `File may be too large. Max ${maxMb} MB.`;
    }
}

function removePendingFile(index: number) {
    pendingFiles.value = pendingFiles.value.filter((_f: File, i: number) => i !== index);
}

function resetUploadForm() {
    pendingFiles.value = [];
    if (fileInput.value) fileInput.value.value = '';
}

function startUpload() {
    const files = pendingFiles.value;
    if (!files.length) return;
    if (files.length === 1) {
        doUpload(files[0]);
        return;
    }
    doUploadMultiple(files);
}

function onDragOver(e: DragEvent) {
    e.preventDefault();
}

type DigitalizeResponse = {
    id: number;
    name: string;
    status?: string;
    digital_data?: {
        type?: string;
        status?: string;
        error?: string;
        processing_batches_done?: number;
        processing_batches_total?: number;
    };
};

async function postOneFile(file: File): Promise<{ status: number; data: DigitalizeResponse }> {
    const formData = new FormData();
    formData.append('file', file);
    const res = await api.post<DigitalizeResponse>('/dashboard/digitalize', formData, {
        timeout: 120000,
        onUploadProgress(ev: { loaded: number; total?: number }) {
            if (ev.total && ev.total > 0) {
                uploadProgress.value = Math.round((ev.loaded / ev.total) * 100);
                if (uploadProgress.value >= 100) {
                    uploadPhase.value = 'extracting';
                }
            }
        },
    });
    return { status: res.status, data: res.data };
}

/** Upload multiple files as a single data record (one extraction for all). */
async function postBatchFiles(files: File[]): Promise<{ status: number; data: DigitalizeResponse }> {
    const formData = new FormData();
    for (const file of files) {
        formData.append('files[]', file);
    }
    const res = await api.post<DigitalizeResponse>('/dashboard/digitalize/batch', formData, {
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
    return { status: res.status, data: res.data };
}

async function doUpload(file: File) {
    const maxMb = Math.round(maxFileSizeBytes.value / (1024 * 1024));
    if (file.size > maxFileSizeBytes.value) {
        uploadError.value = `File must be under ${maxMb} MB.`;
        return;
    }

    uploadLoading.value = true;
    uploadProgress.value = 0;
    uploadPhase.value = 'uploading';
    uploadError.value = null;
    uploadSuccess.value = false;
    uploadCount.value = { done: 0, total: 1 };

    try {
        const { status, data } = await postOneFile(file);
        uploadProgress.value = 100;
        if (status === 202 && data.id) {
            emit('item-created');
        }
        uploadSuccess.value = true;
        resetUploadForm();
        emit('uploaded');
        setTimeout(() => { uploadSuccess.value = false; }, 2000);
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
        extractingBatches.value = null;
    }
}

async function doUploadMultiple(files: File[]) {
    uploadLoading.value = true;
    uploadPhase.value = 'uploading';
    uploadProgress.value = 0;
    uploadError.value = null;
    uploadSuccess.value = false;
    uploadCount.value = { done: 0, total: files.length };

    try {
        const { status, data } = await postBatchFiles(files);
        uploadProgress.value = 100;
        uploadPhase.value = 'extracting';
        if (status === 202 && data.id) {
            emit('item-created');
        }
        uploadSuccess.value = true;
        resetUploadForm();
        emit('uploaded');
        setTimeout(() => { uploadSuccess.value = false; }, 3000);
    } catch (e: unknown) {
        const err = e as { response?: { data?: { message?: string } }; message?: string };
        uploadError.value =
            err.response?.data?.message ||
            err.message ||
            'Upload failed. All files are processed together as one item.';
    } finally {
        uploadLoading.value = false;
        uploadProgress.value = 0;
        uploadPhase.value = 'uploading';
        uploadCount.value = { done: 0, total: 0 };
    }
}

onMounted(async () => {
    if (props.storageKey && typeof localStorage !== 'undefined') {
        localStorage.setItem(props.storageKey, '1');
    }
    try {
        const res = await api.get<{ max_file_size_bytes?: number }>('/dashboard/api/digitalize-options');
        if (typeof res.data?.max_file_size_bytes === 'number' && res.data.max_file_size_bytes > 0) {
            maxFileSizeBytes.value = res.data.max_file_size_bytes;
        }
    } catch {
        // keep default 20 MB
    }
});
</script>

<template>
    <section class="upload-section" aria-labelledby="upload-heading">
        <Button
            type="button"
            variant="outline"
            size="lg"
            class="w-full cursor-pointer justify-center rounded-xl border border-gray-200 bg-white px-4 py-3 text-left font-medium text-gray-900 shadow-sm transition-colors hover:bg-gray-50 sm:w-auto sm:min-w-[200px]"
            @click="uploadSectionOpen = !uploadSectionOpen"
        >
                <span class="flex items-center gap-2">
                <Plus class="h-5 w-5 shrink-0 text-primary" />
                Add Data
            </span>
            <ChevronDown class="h-4 w-4 shrink-0 font-bold transition-transform duration-200" :class="{ 'rotate-180': uploadSectionOpen }" />
        </Button>
        <div
            v-show="uploadSectionOpen"
            id="upload-heading"
            class="upload-card mt-4 rounded-2xl border border-border bg-card p-5 shadow-sm sm:p-6"
        >
            <p class="mb-4 text-sm text-muted-foreground">
                Add a photo or video — we’ll extract the text or table and save it as an item in your workspace. You can edit it, chart it, and ask AI to change it anytime.
            </p>
            <input
                ref="fileInput"
                type="file"
                :accept="ACCEPT"
                multiple
                class="hidden"
                @change="onFileChange"
            />
            <div
                class="upload-zone rounded-xl border-2 border-dashed border-primary/60 bg-primary/5 p-6 text-center transition-colors"
                :class="[
                    uploadLoading ? 'cursor-not-allowed border-primary bg-primary/10 pointer-events-none' : 'cursor-pointer hover:border-primary hover:bg-primary/10',
                ]"
                :aria-disabled="uploadLoading"
                role="button"
                :tabindex="uploadLoading ? -1 : 0"
                @click="openFilePicker"
                @drop="onDrop"
                @dragover="onDragOver"
                @keydown.enter="openFilePicker"
                @keydown.space.prevent="openFilePicker"
            >
                <Upload class="mx-auto mb-2 h-8 w-8 text-muted-foreground" />
                <p class="text-sm font-medium text-foreground">
                    {{ pendingFiles.length ? (pendingFiles.length === 1 ? pendingFiles[0].name : `${pendingFiles.length} files selected`) : 'Drop photos, videos, or click to choose' }}
                </p>
                <p v-if="!pendingFiles.length" class="mt-1 text-xs text-muted-foreground">
                    Photos or video · max {{ Math.round(maxFileSizeBytes / (1024 * 1024)) }} MB each · multiple allowed
                </p>
                <ul v-else-if="pendingFiles.length > 1 && !uploadLoading" class="mt-3 max-h-32 list-inside list-disc overflow-y-auto text-left text-xs text-muted-foreground">
                    <li v-for="(f, i) in pendingFiles" :key="i" class="flex items-center justify-between gap-2">
                        <span class="truncate">{{ f.name }}</span>
                        <button
                            type="button"
                            class="shrink-0 rounded p-0.5 text-destructive hover:bg-destructive/10"
                            aria-label="Remove"
                            @click.stop="removePendingFile(i)"
                        >
                            ×
                        </button>
                    </li>
                </ul>
                <div v-if="uploadLoading" class="mt-4 space-y-2">
                    <div class="flex items-center justify-between text-sm text-muted-foreground">
                        <span>
                            {{ uploadCount.total > 1
                                ? (uploadPhase === 'uploading' ? `Uploading ${uploadCount.done + (uploadProgress >= 100 ? 1 : 0)} of ${uploadCount.total}…` : (extractingBatches ? `Extracting… ${extractingBatches.done}/${extractingBatches.total} batches` : `Extracting… ${uploadCount.done}/${uploadCount.total}`))
                                : (uploadPhase === 'uploading' ? `Uploading… ${uploadProgress}%` : (extractingBatches ? `Extracting… ${extractingBatches.done}/${extractingBatches.total} batches` : 'Extracting…'))
                            }}
                        </span>
                        <span v-if="uploadPhase === 'uploading' && uploadCount.total <= 1" class="tabular-nums">{{ uploadProgress }}%</span>
                        <span v-else-if="extractingBatches && extractingBatches.total > 0" class="tabular-nums">{{ extractingBatches.done }}/{{ extractingBatches.total }}</span>
                    </div>
                    <div class="h-1.5 w-full overflow-hidden rounded-full bg-muted">
                        <div
                            class="h-full rounded-full bg-primary transition-[width] duration-300 ease-out"
                            :style="{ width: extractingBatches && extractingBatches.total > 0
                                ? `${(extractingBatches.done / extractingBatches.total) * 100}%`
                                : (uploadCount.total > 1 ? `${(uploadCount.done / uploadCount.total) * 100}%` : (uploadPhase === 'extracting' ? '100%' : `${uploadProgress}%`)) }"
                        />
                    </div>
                </div>
                <p v-else-if="uploadError" class="mt-3 text-sm text-destructive">
                    {{ uploadError }}
                </p>
                <p v-else-if="uploadSuccess" class="mt-3 text-sm font-medium text-success">
                    {{ uploadCount.total > 1 ? `✓ ${uploadCount.done} item${uploadCount.done !== 1 ? 's' : ''} added.` : '✓ Added below.' }}
                </p>
            </div>
            <div class="mt-4 flex flex-col justify-center items-center gap-3">
                <Button
                    type="button"
                    class="rounded-lg"
                    :disabled="!pendingFiles.length || uploadLoading"
                    @click="startUpload"
                >
                    {{ pendingFiles.length ? (pendingFiles.length === 1 ? 'Extract & save' : `Extract & save ${pendingFiles.length} files`) : 'Upload' }}
                </Button>
                <span v-if="!pendingFiles.length" class="text-xs text-muted-foreground">
                    Tip: Multiple pages? One photo per page or pause 1–2 sec per page in video.
                </span>
            </div>
        </div>
    </section>
</template>
