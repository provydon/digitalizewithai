<script setup lang="ts">
import { Folder, Pencil } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import api from '@/lib/api';
import type { FolderItem } from '@/types';
import type { DataRecord } from '../types';

const props = defineProps<{
    record: DataRecord;
    folders?: FolderItem[];
}>();

const emit = defineEmits<{
    'update:name': [name: string];
    'update:folderId': [folderId: number | null];
}>();

const currentFolderLabel = computed(() => {
    const fid = props.record.folder_id;
    if (fid == null) return 'Uncategorized';
    const f = props.folders?.find((x) => x.id === fid);
    return f?.name ?? 'Uncategorized';
});

const moveToLoading = ref(false);

async function moveToFolder(folderId: number | null) {
    moveToLoading.value = true;
    try {
        await api.patch(`/dashboard/api/data/${props.record.id}`, { folder_id: folderId });
        emit('update:folderId', folderId);
    } finally {
        moveToLoading.value = false;
    }
}

const aiModelLabel = computed(() => {
    const r = props.record;
    const p = r.ai_provider?.trim();
    const m = r.ai_model?.trim();
    if (p && m) return `${p} · ${m}`;
    if (m) return m;
    if (p) return p;
    return '';
});

const formattedDate = computed(() => {
    if (!props.record.created_at) return '';
    return new Date(props.record.created_at).toLocaleString(undefined, {
        dateStyle: 'short',
        timeStyle: 'short',
    });
});

const nameEditing = ref(false);
const nameEditValue = ref('');
const nameEditSaving = ref(false);
const nameEditError = ref<string | null>(null);

function startNameEdit() {
    nameEditError.value = null;
    nameEditValue.value = props.record.name;
    nameEditing.value = true;
}

function cancelNameEdit() {
    nameEditing.value = false;
    nameEditValue.value = '';
    nameEditError.value = null;
}

async function saveNameEdit() {
    const name = nameEditValue.value.trim();
    if (!name) return;
    nameEditSaving.value = true;
    nameEditError.value = null;
    try {
        const { data } = await api.patch<{ name: string }>(
            `/dashboard/api/data/${props.record.id}`,
            { name },
        );
        emit('update:name', data.name);
        cancelNameEdit();
    } catch (e: unknown) {
        const err = e as { response?: { data?: { message?: string } }; message?: string };
        nameEditError.value = err.response?.data?.message ?? err.message ?? 'Failed to save name';
    } finally {
        nameEditSaving.value = false;
    }
}
</script>

<template>
    <div class="mb-2 flex min-w-0 items-center gap-1.5">
        <template v-if="!nameEditing">
            <h1
                class="min-w-0 truncate text-lg font-semibold text-foreground sm:text-xl"
                @dblclick="startNameEdit"
            >
                {{ record.name }}
            </h1>
            <button
                type="button"
                class="shrink-0 rounded p-1 text-muted-foreground hover:bg-muted/50 hover:text-foreground"
                title="Edit name"
                aria-label="Edit name"
                @click="startNameEdit"
            >
                <Pencil class="h-3.5 w-3.5" />
            </button>
        </template>
        <template v-else>
            <input
                v-model="nameEditValue"
                type="text"
                class="min-w-0 flex-1 rounded-lg border border-border bg-background px-2 py-1 text-lg font-semibold text-foreground focus:outline-none focus:ring-2 focus:ring-ring sm:text-xl"
                placeholder="Name"
                @keydown.enter.prevent="saveNameEdit()"
                @keydown.escape.prevent="cancelNameEdit()"
            />
            <Button
                size="sm"
                class="shrink-0"
                :disabled="nameEditSaving || !nameEditValue.trim()"
                @click="saveNameEdit"
            >
                {{ nameEditSaving ? '…' : 'Save' }}
            </Button>
            <Button
                size="sm"
                variant="secondary"
                class="shrink-0"
                :disabled="nameEditSaving"
                @click="cancelNameEdit"
            >
                Cancel
            </Button>
        </template>
    </div>
    <p v-if="nameEditError" class="mb-1 text-sm text-destructive">
        {{ nameEditError }}
    </p>
    <p class="mb-4 flex flex-nowrap items-center justify-between gap-x-2 overflow-x-auto text-xs text-muted-foreground sm:justify-start">
        <span class="shrink-0 font-mono">ID: {{ record.id }}</span>
        <span v-if="formattedDate" class="shrink-0">{{ formattedDate }}</span>
        <span v-if="aiModelLabel" class="min-w-0 shrink truncate sm:text-left text-right" :title="aiModelLabel">
            {{ aiModelLabel }}
        </span>
    </p>
    <div v-if="folders !== undefined" class="mb-4 flex items-center gap-2">
        <span class="text-xs text-muted-foreground">Location:</span>
        <DropdownMenu>
            <DropdownMenuTrigger as-child>
                <Button
                    variant="outline"
                    size="sm"
                    class="h-8 gap-1.5 text-xs font-normal"
                    :disabled="moveToLoading"
                >
                    <Folder class="h-3.5 w-3.5 shrink-0" aria-hidden />
                    {{ currentFolderLabel }}
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="start" class="max-h-[min(16rem,60vh)] overflow-y-auto">
                <DropdownMenuItem
                    :disabled="moveToLoading || record.folder_id == null"
                    @select="moveToFolder(null)"
                >
                    Uncategorized
                </DropdownMenuItem>
                <DropdownMenuItem
                    v-for="f in (folders || [])"
                    :key="f.id"
                    :disabled="moveToLoading || record.folder_id === f.id"
                    @select="moveToFolder(f.id)"
                >
                    {{ f.name }}
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    </div>
</template>
