<script setup lang="ts">
import { ref, watch } from 'vue';
import api from '@/lib/api';
import type { DigitalizedItem } from '@/types';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

const props = defineProps<{
    open: boolean;
    item: DigitalizedItem | null;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
    deleted: [];
}>();

const deleteLoading = ref(false);
const deleteError = ref<string | null>(null);

watch(
    () => props.open,
    (open: boolean) => {
        if (open) {
            deleteError.value = null;
        }
    }
);

function close() {
    emit('update:open', false);
}

async function confirmDelete() {
    const item = props.item;
    if (!item) return;
    deleteLoading.value = true;
    deleteError.value = null;
    try {
        await api.delete(`/dashboard/api/data/${item.id}`);
        emit('deleted');
        close();
    } catch (e: unknown) {
        const err = e as { response?: { data?: { message?: string } }; message?: string };
        deleteError.value = err.response?.data?.message ?? err.message ?? 'Failed to delete';
    } finally {
        deleteLoading.value = false;
    }
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>Delete this data?</DialogTitle>
            </DialogHeader>
            <p class="text-sm text-muted-foreground">
                This cannot be undone.
            </p>
            <p v-if="deleteError" class="text-sm text-destructive">
                {{ deleteError }}
            </p>
            <DialogFooter class="gap-2">
                <DialogClose as-child>
                    <Button variant="secondary" @click="close">
                        Cancel
                    </Button>
                </DialogClose>
                <Button
                    variant="destructive"
                    :disabled="deleteLoading"
                    @click="confirmDelete"
                >
                    {{ deleteLoading ? 'Deleting…' : 'Delete' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
