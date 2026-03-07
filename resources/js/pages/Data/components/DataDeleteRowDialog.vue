<script setup lang="ts">
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import type { TableRowRecord } from '../types';

defineProps<{
    open: boolean;
    rowToDelete: TableRowRecord | null;
    deleteConfirming: boolean;
}>();

const emit = defineEmits<{
    'update:open': [v: boolean];
    confirm: [];
    close: [];
}>();
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>Delete row?</DialogTitle>
            </DialogHeader>
            <p class="text-sm text-muted-foreground">
                This row will be permanently removed. This cannot be undone.
            </p>
            <DialogFooter class="gap-2">
                <DialogClose as-child>
                    <Button variant="secondary" @click="emit('close')">
                        Cancel
                    </Button>
                </DialogClose>
                <Button
                    variant="destructive"
                    :disabled="deleteConfirming"
                    @click="emit('confirm')"
                >
                    {{ deleteConfirming ? 'Deleting…' : 'Delete' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
