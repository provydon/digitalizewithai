<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { ChevronDown, Plus, Upload } from 'lucide-vue-next';
import api from '@/lib/api';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';

export type DigitalizeProviderOption = { id: string; name: string };
export type DigitalizeOptionsResponse = { providers: DigitalizeProviderOption[]; default_provider: string };

const props = withDefaults(
    defineProps<{
        /** localStorage key to remember if user has seen the upload section (collapse by default after first visit) */
        storageKey?: string;
    }>(),
    { storageKey: 'dashboard_upload_seen' }
);

const emit = defineEmits<{
    uploaded: [];
}>();

const ACCEPT =
    'image/jpeg,image/png,image/gif,image/webp,video/mp4,video/webm';

const uploadSectionOpen = ref(!(props.storageKey && typeof localStorage !== 'undefined' ? localStorage.getItem(props.storageKey) : null));
const uploadLoading = ref(false);
const uploadProgress = ref(0);
const uploadPhase = ref<'uploading' | 'extracting'>('uploading');
const uploadError = ref<string | null>(null);
const uploadSuccess = ref(false);
const fileInput = ref<HTMLInputElement | null>(null);
const pendingFile = ref<File | null>(null);

const digitalizeOptions = ref<DigitalizeOptionsResponse | null>(null);
const selectedProvider = ref('');

const providerOptions = computed(() => digitalizeOptions.value?.providers ?? []);

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
}

function onDrop(e: DragEvent) {
    e.preventDefault();
    const file = e.dataTransfer?.files?.[0];
    if (!file) return;
    uploadError.value = null;
    uploadSuccess.value = false;
    pendingFile.value = file;
}

function resetUploadForm() {
    pendingFile.value = null;
    if (fileInput.value) fileInput.value.value = '';
}

function startUpload() {
    const file = pendingFile.value;
    if (!file) return;
    doUpload(file);
}

function onDragOver(e: DragEvent) {
    e.preventDefault();
}

async function doUpload(file: File) {
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
    if (selectedProvider.value) {
        formData.append('ai_provider', selectedProvider.value);
    }

    try {
        await api.post<{ id: number; name: string }>('/dashboard/digitalize', formData, {
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
        resetUploadForm();
        emit('uploaded');
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

async function fetchDigitalizeOptions() {
    try {
        const { data } = await api.get<DigitalizeOptionsResponse>('/dashboard/api/digitalize-options');
        digitalizeOptions.value = data;
        if (selectedProvider.value) return;
        const defaultId = data.providers.some((p: DigitalizeProviderOption) => p.id === data.default_provider)
            ? data.default_provider
            : data.providers[0]?.id ?? '';
        selectedProvider.value = defaultId;
    } catch {
        digitalizeOptions.value = { providers: [], default_provider: '' };
    }
}

onMounted(() => {
    if (props.storageKey && typeof localStorage !== 'undefined') {
        localStorage.setItem(props.storageKey, '1');
    }
    fetchDigitalizeOptions();
});
</script>

<template>
    <section class="upload-section" aria-labelledby="upload-heading">
        <Button
            type="button"
            variant="outline"
            size="lg"
            class="w-full cursor-pointer justify-center rounded-xl border-border bg-card px-4 py-3 text-left font-medium shadow-sm transition-colors hover:bg-muted/50 sm:w-auto sm:min-w-[200px]"
            @click="uploadSectionOpen = !uploadSectionOpen"
        >
            <span class="flex items-center gap-2">
                <Plus class="h-5 w-5 shrink-0 text-primary" />
                Add new
            </span>
            <ChevronDown class="h-4 w-4 shrink-0 font-bold transition-transform duration-200" :class="{ 'rotate-180': uploadSectionOpen }" />
        </Button>
        <div
            v-show="uploadSectionOpen"
            id="upload-heading"
            class="upload-card mt-4 rounded-2xl border border-border bg-card p-5 shadow-sm sm:p-6"
        >
            <p class="mb-4 text-sm text-muted-foreground">
                Add a photo or video — we’ll extract the text or table and name it for you.
            </p>
            <div v-if="providerOptions.length > 0" class="mb-4 w-full">
                <Label for="upload-ai-provider" class="text-xs font-medium text-muted-foreground">AI provider</Label>
                <select
                    id="upload-ai-provider"
                    v-model="selectedProvider"
                    class="mt-1 block w-full rounded-lg border-2 border-primary/50 bg-background px-3 py-2 text-sm ring-2 ring-primary/20 focus:border-primary focus:outline-none focus:ring-primary/40 disabled:opacity-50"
                    :disabled="uploadLoading"
                >
                    <option v-for="p in providerOptions" :key="p.id" :value="p.id">{{ p.name }}</option>
                </select>
            </div>
            <input
                ref="fileInput"
                type="file"
                :accept="ACCEPT"
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
            <div class="mt-4 flex flex-col justify-center items-center gap-3">
                <Button
                    type="button"
                    class="rounded-lg"
                    :disabled="!pendingFile || uploadLoading"
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
</template>
