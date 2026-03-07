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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { TableRowRecord } from '../types';

const props = defineProps<{
    open: boolean;
    editRow: TableRowRecord | null;
    tableHeaders: string[];
    editCells: string[];
    editSaving: boolean;
}>();

const emit = defineEmits<{
    'update:open': [v: boolean];
    'update:editCells': [v: string[]];
    save: [];
    close: [];
}>();

function updateCell(index: number, value: string) {
    const next = [...props.editCells];
    next[index] = value;
    emit('update:editCells', next);
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="sm:max-w-lg">
            <DialogHeader>
                <DialogTitle>Edit row</DialogTitle>
            </DialogHeader>
            <div
                v-if="editRow && tableHeaders.length"
                class="grid gap-3 py-2"
            >
                <div
                    v-for="(header, i) in tableHeaders"
                    :key="i"
                    class="grid gap-1.5"
                >
                    <Label :for="`edit-cell-${i}`">{{ header }}</Label>
                    <Input
                        :id="`edit-cell-${i}`"
                        :model-value="editCells[i]"
                        class="w-full"
                        @update:model-value="updateCell(i, $event)"
                    />
                </div>
            </div>
            <DialogFooter class="gap-2">
                <DialogClose as-child>
                    <Button variant="secondary" @click="emit('close')">
                        Cancel
                    </Button>
                </DialogClose>
                <Button
                    :disabled="editSaving"
                    @click="emit('save')"
                >
                    {{ editSaving ? 'Saving…' : 'Save' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
