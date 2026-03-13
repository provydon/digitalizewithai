<script setup lang="ts">
import { Camera, Upload, Video, X } from 'lucide-vue-next';
import { TabsContent, TabsList, TabsRoot, TabsTrigger } from 'reka-ui';
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

const props = defineProps<{
    open: boolean;
    tableHeaders: string[];
    addRowCells: string[];
    addRowSaving: boolean;
    addRowsTab: 'manual' | 'upload';
    appendFiles: File[];
    appendLoading: boolean;
    appendProgress: number;
    appendPhase: 'uploading' | 'extracting';
    appendError: string | null;
    appendSuccess: boolean;
}>();

const emit = defineEmits<{
    'update:open': [v: boolean];
    'update:addRowCells': [v: string[]];
    'update:addRowsTab': [v: 'manual' | 'upload'];
    'save-add-row': [];
    'close': [];
    'append-file-change': [e: Event];
    'append-drop': [e: DragEvent];
    'remove-append-file': [index: number];
    'open-append-file-picker': [];
    'open-append-camera-photo': [];
    'open-append-camera-video': [];
    'submit-append-upload': [];
}>();

function updateAddCell(index: number, value: string | number) {
    const next = [...props.addRowCells];
    next[index] = String(value);
    emit('update:addRowCells', next);
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="sm:max-w-lg">
            <DialogHeader>
                <DialogTitle>Add rows</DialogTitle>
            </DialogHeader>
            <TabsRoot :model-value="addRowsTab" @update:model-value="emit('update:addRowsTab', $event)" class="w-full">
                <TabsList class="grid w-full grid-cols-2">
                    <TabsTrigger value="manual">Manual</TabsTrigger>
                    <TabsTrigger value="upload">From photo or video</TabsTrigger>
                </TabsList>
                <TabsContent value="manual" class="mt-3">
                    <div
                        v-if="tableHeaders.length"
                        class="grid gap-3 py-2"
                    >
                        <div
                            v-for="(header, i) in tableHeaders"
                            :key="i"
                            class="grid gap-1.5"
                        >
                            <Label :for="`add-cell-${i}`">{{ header }}</Label>
                            <Input
                                :id="`add-cell-${i}`"
                                :model-value="addRowCells[i]"
                                class="w-full"
                                @update:model-value="updateAddCell(i, $event)"
                            />
                        </div>
                    </div>
                    <DialogFooter class="mt-4 gap-2">
                        <DialogClose as-child>
                            <Button variant="secondary" @click="emit('close')">
                                Cancel
                            </Button>
                        </DialogClose>
                        <Button
                            :disabled="addRowSaving"
                            @click="emit('save-add-row')"
                        >
                            {{ addRowSaving ? 'Adding…' : 'Add row' }}
                        </Button>
                    </DialogFooter>
                </TabsContent>
                <TabsContent value="upload" class="mt-3">
                    <p class="mb-3 text-sm text-muted-foreground">
                        Upload photos or videos of a table. Take or add multiple — each new one is added to the list. We'll extract the rows and append them to this table.
                    </p>
                    <slot name="file-inputs" />
                    <div class="mb-3 flex flex-wrap gap-2">
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            :disabled="appendLoading"
                            @click="emit('open-append-camera-photo')"
                        >
                            <Camera class="mr-1.5 h-4 w-4" />
                            Take a picture
                        </Button>
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            :disabled="appendLoading"
                            @click="emit('open-append-camera-video')"
                        >
                            <Video class="mr-1.5 h-4 w-4" />
                            Record video
                        </Button>
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            :disabled="appendLoading"
                            @click="emit('open-append-file-picker')"
                        >
                            <Upload class="mr-1.5 h-4 w-4" />
                            Choose file(s)
                        </Button>
                    </div>
                    <div
                        v-if="appendFiles.length > 0"
                        class="mb-3 flex flex-wrap gap-2"
                    >
                        <div
                            v-for="(file, i) in appendFiles"
                            :key="`${file.name}-${i}`"
                            class="flex items-center gap-1.5 rounded-md border border-sidebar-border/70 bg-muted/40 px-2.5 py-1.5 text-sm"
                        >
                            <span class="truncate max-w-[180px]" :title="file.name">{{ file.name }}</span>
                            <button
                                type="button"
                                class="shrink-0 rounded p-0.5 text-muted-foreground hover:bg-muted hover:text-foreground"
                                aria-label="Remove"
                                :disabled="appendLoading"
                                @click="emit('remove-append-file', i)"
                            >
                                <X class="h-3.5 w-3.5" />
                            </button>
                        </div>
                    </div>
                    <div
                        class="cursor-pointer rounded-lg border-2 border-dashed border-sidebar-border/70 bg-muted/30 p-4 text-center transition-colors dark:border-sidebar-border"
                        :class="{ 'border-primary/50 bg-primary/5': appendLoading }"
                        role="button"
                        tabindex="0"
                        @click="emit('open-append-file-picker')"
                        @drop.prevent="emit('append-drop', $event)"
                        @dragover.prevent
                        @keydown.enter="emit('open-append-file-picker')"
                        @keydown.space.prevent="emit('open-append-file-picker')"
                    >
                        <Upload class="mx-auto mb-2 h-6 w-6 text-muted-foreground" />
                        <p class="mb-1 text-sm text-muted-foreground">
                            Or drag files here
                        </p>
                        <p v-if="appendFiles.length" class="mb-2 text-sm font-medium text-foreground">
                            {{ appendFiles.length }} file(s) selected
                        </p>
                        <p class="mb-2 text-xs text-muted-foreground">
                            Images: JPEG, PNG, GIF, WebP. Video: MP4, WebM. Max 20 MB each.
                        </p>
                        <div v-if="appendLoading" class="mt-2 space-y-2">
                            <div class="flex justify-between text-sm text-muted-foreground">
                                <span>{{ appendPhase === 'uploading' ? `Uploading… ${appendProgress}%` : 'Extracting…' }}</span>
                                <span v-if="appendPhase === 'uploading'" class="tabular-nums">{{ appendProgress }}%</span>
                            </div>
                            <div class="h-2 w-full overflow-hidden rounded-full bg-muted">
                                <div
                                    class="h-full rounded-full bg-primary transition-[width] duration-300"
                                    :style="{ width: appendPhase === 'extracting' ? '100%' : `${appendProgress}%` }"
                                />
                            </div>
                        </div>
                        <p v-else-if="appendError" class="text-sm text-destructive">
                            {{ appendError }}
                        </p>
                        <p v-else-if="appendSuccess" class="text-sm text-success">
                            Rows appended. They appear in the table.
                        </p>
                    </div>
                    <DialogFooter class="mt-2 gap-2">
                        <DialogClose as-child>
                            <Button variant="secondary" @click="emit('close')">
                                Close
                            </Button>
                        </DialogClose>
                        <Button
                            :disabled="appendFiles.length === 0 || appendLoading"
                            @click="emit('submit-append-upload')"
                        >
                            {{ appendLoading ? 'Adding…' : appendFiles.length > 1 ? `Add rows from ${appendFiles.length} files` : 'Add rows from file' }}
                        </Button>
                    </DialogFooter>
                </TabsContent>
            </TabsRoot>
        </DialogContent>
    </Dialog>
</template>
