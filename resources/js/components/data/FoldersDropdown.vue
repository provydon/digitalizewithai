<script setup lang="ts">
import { FileText, Folder, FolderPlus } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import NewFolderModal from '@/components/data/NewFolderModal.vue';
import type { FolderItem } from '@/types';

const props = withDefaults(
    defineProps<{
        /** List of folders to show (plus Uncategorized + New folder). */
        folders: FolderItem[];
        /** Currently selected folder id (for dropdown label and disabling that option). */
        currentFolderId?: number | null;
        /** Disable all actions (e.g. while moving). */
        disabled?: boolean;
        /** 'dropdown' = trigger + dropdown; 'list' = vertical list of buttons (e.g. in a modal). */
        variant?: 'dropdown' | 'list';
        /** For list variant: optional label above the list. */
        listLabel?: string;
    }>(),
    {
        currentFolderId: null,
        disabled: false,
        variant: 'dropdown',
        listLabel: undefined,
    }
);

const emit = defineEmits<{
    select: [folderId: number | null];
    'update:folders': [folders: FolderItem[]];
}>();

const newFolderOpen = ref(false);

const currentFolderLabel = computed(() => {
    if (props.currentFolderId == null) return 'Uncategorized';
    const f = props.folders.find((x) => x.id === props.currentFolderId);
    return f?.name ?? 'Uncategorized';
});

function onSelect(folderId: number | null) {
    if (props.disabled) return;
    emit('select', folderId);
}

function openNewFolder() {
    if (props.disabled) return;
    newFolderOpen.value = true;
}

function onFolderCreated(folder: FolderItem) {
    emit('update:folders', [...props.folders, folder]);
    emit('select', folder.id);
    newFolderOpen.value = false;
}
</script>

<template>
    <template v-if="variant === 'dropdown'">
        <DropdownMenu>
            <DropdownMenuTrigger as-child>
                <slot name="trigger">
                    <Button
                        variant="outline"
                        size="sm"
                        class="h-8 gap-1.5 text-xs font-normal"
                        :disabled="disabled"
                    >
                        <Folder class="h-3.5 w-3.5 shrink-0" aria-hidden />
                        {{ currentFolderLabel }}
                    </Button>
                </slot>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="start" class="max-h-[min(16rem,60vh)] overflow-y-auto">
                <DropdownMenuItem
                    :disabled="disabled || currentFolderId == null"
                    @select="onSelect(null)"
                >
                    <FileText class="h-3.5 w-3.5 shrink-0 opacity-60 mr-2" aria-hidden />
                    Uncategorized
                </DropdownMenuItem>
                <DropdownMenuItem
                    v-for="f in folders"
                    :key="f.id"
                    :disabled="disabled || currentFolderId === f.id"
                    @select="onSelect(f.id)"
                >
                    <Folder class="h-3.5 w-3.5 shrink-0 mr-2" aria-hidden />
                    <span class="min-w-0 truncate">{{ f.name }}</span>
                </DropdownMenuItem>
                <DropdownMenuItem @select="openNewFolder">
                    <FolderPlus class="h-3.5 w-3.5 shrink-0 mr-2" aria-hidden />
                    New folder
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    </template>

    <template v-else>
        <!-- List variant: for use inside modals -->
        <div class="flex flex-col gap-0.5">
            <p v-if="listLabel" class="text-sm text-muted-foreground pb-1">
                {{ listLabel }}
            </p>
            <button
                type="button"
                class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm font-medium text-foreground hover:bg-muted disabled:opacity-50"
                :disabled="disabled"
                @click="onSelect(null)"
            >
                <FileText class="h-4 w-4 shrink-0 opacity-60" aria-hidden />
                Uncategorized
            </button>
            <button
                v-for="f in folders"
                :key="f.id"
                type="button"
                class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm font-medium text-foreground hover:bg-muted disabled:opacity-50"
                :disabled="disabled"
                @click="onSelect(f.id)"
            >
                <Folder class="h-4 w-4 shrink-0" aria-hidden />
                <span class="min-w-0 truncate">{{ f.name }}</span>
            </button>
            <button
                type="button"
                class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm font-medium text-primary hover:bg-primary/10 disabled:opacity-50"
                :disabled="disabled"
                @click="openNewFolder"
            >
                <FolderPlus class="h-4 w-4 shrink-0" aria-hidden />
                New folder
            </button>
        </div>
    </template>

    <NewFolderModal
        :open="newFolderOpen"
        @update:open="newFolderOpen = $event"
        @created="onFolderCreated"
    />
</template>
