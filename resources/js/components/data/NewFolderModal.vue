<script setup lang="ts">
import { ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import api from '@/lib/api';
import type { FolderItem } from '@/types';

const props = defineProps<{
    open: boolean;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
    created: [folder: FolderItem];
}>();

const name = ref('');
const loading = ref(false);
const error = ref<string | null>(null);

watch(
    () => props.open,
    (isOpen) => {
        if (isOpen) {
            name.value = '';
            error.value = null;
        }
    },
);

async function createFolder() {
    const trimmed = name.value.trim();
    if (!trimmed) return;
    loading.value = true;
    error.value = null;
    try {
        const { data } = await api.post<FolderItem & { created_at?: string }>('/dashboard/api/folders', {
            name: trimmed,
        });
        const folder: FolderItem = { id: data.id, parent_id: data.parent_id ?? null, name: data.name };
        emit('created', folder);
        emit('update:open', false);
    } catch (e: unknown) {
        const err = e as { response?: { data?: { message?: string } }; message?: string };
        error.value = err.response?.data?.message ?? err.message ?? 'Failed to create folder';
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="sm:max-w-sm">
            <DialogHeader>
                <DialogTitle>New folder</DialogTitle>
            </DialogHeader>
            <form class="flex flex-col gap-3" @submit.prevent="createFolder">
                <input
                    v-model="name"
                    type="text"
                    placeholder="Folder name"
                    class="rounded-lg border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring"
                    aria-label="Folder name"
                />
                <p v-if="error" class="text-sm text-destructive">
                    {{ error }}
                </p>
                <DialogFooter class="gap-2">
                    <DialogClose as-child>
                        <Button type="button" variant="secondary">
                            Cancel
                        </Button>
                    </DialogClose>
                    <Button type="submit" :disabled="!name.trim() || loading">
                        {{ loading ? 'Creating…' : 'Create' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
