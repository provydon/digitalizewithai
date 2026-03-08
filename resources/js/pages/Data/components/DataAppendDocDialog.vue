<script setup lang="ts">
import { Camera, Upload, Video } from 'lucide-vue-next';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';

const props = defineProps<{
    open: boolean;
    appendFile: File | null;
    appendLoading: boolean;
    appendProgress: number;
    appendPhase: 'uploading' | 'extracting';
    appendError: string | null;
    appendSuccess: boolean;
}>();

const emit = defineEmits<{
    'update:open': [v: boolean];
    'append-file-change': [e: Event];
    'append-drop': [e: DragEvent];
    'open-append-file-picker': [];
    'open-append-camera-photo': [];
    'open-append-camera-video': [];
    'submit-append-doc': [];
    'close': [];
}>();
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="sm:max-w-lg">
            <DialogHeader>
                <DialogTitle>Append to document</DialogTitle>
            </DialogHeader>
            <p class="mb-3 text-sm text-muted-foreground">
                Upload a photo or video — we'll extract the text and append it to this document. The AI will avoid duplicating content already in the doc.
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
                    Choose a file
                </Button>
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
                    Or drag a file here
                </p>
                <p v-if="appendFile" class="mb-2 text-sm font-medium text-foreground">
                    {{ appendFile.name }}
                </p>
                <p class="mb-2 text-xs text-muted-foreground">
                    Images: JPEG, PNG, GIF, WebP. Video: MP4, WebM. Max 20 MB.
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
                    Content appended. It appears at the end of the document.
                </p>
            </div>
            <DialogFooter class="mt-2 gap-2">
                <DialogClose as-child>
                    <Button variant="secondary" @click="emit('close')">
                        Close
                    </Button>
                </DialogClose>
                <Button
                    :disabled="!appendFile || appendLoading"
                    @click="emit('submit-append-doc')"
                >
                    {{ appendLoading ? 'Adding…' : 'Append to document' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
