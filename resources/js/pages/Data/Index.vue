<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Folder, FolderPlus, Inbox } from 'lucide-vue-next';
import { ref } from 'vue';
import DataListSection from '@/components/data/DataListSection.vue';
import AppLayout from '@/layouts/AppLayout.vue';
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
import { dashboard } from '@/routes';
import type { BreadcrumbItem, FolderItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard() },
    { title: 'Data', href: '/data' },
];

const folders = ref<FolderItem[]>([]);
type FolderFilter = 'all' | 'uncategorized' | number;
const selectedFolderId = ref<FolderFilter>('all');

const listRef = ref<InstanceType<typeof DataListSection> | null>(null);

function onUpdateFolders(newFolders: FolderItem[]) {
    folders.value = newFolders;
}

function selectFolder(id: FolderFilter) {
    selectedFolderId.value = id;
}

const newFolderOpen = ref(false);
const newFolderName = ref('');
const newFolderLoading = ref(false);
const newFolderError = ref<string | null>(null);

async function createFolder() {
    const name = newFolderName.value.trim();
    if (!name) return;
    newFolderLoading.value = true;
    newFolderError.value = null;
    try {
        const { data } = await api.post<FolderItem & { created_at?: string }>('/dashboard/api/folders', { name });
        folders.value = [...folders.value, { id: data.id, parent_id: data.parent_id ?? null, name: data.name }];
        newFolderOpen.value = false;
        newFolderName.value = '';
        selectFolder(data.id);
    } catch (e: unknown) {
        const err = e as { response?: { data?: { message?: string } }; message?: string };
        newFolderError.value = err.response?.data?.message ?? err.message ?? 'Failed to create folder';
    } finally {
        newFolderLoading.value = false;
    }
}
</script>

<template>
    <Head title="Data" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="data-page flex min-h-full flex-1 flex-col overflow-x-auto">
            <header class="shrink-0 border-b border-border/80 bg-background/95 px-4 py-5 backdrop-blur sm:px-6">
                <h1 class="text-xl font-semibold tracking-tight text-foreground sm:text-2xl">
                    Data
                </h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    Search and manage everything you've added.
                </p>
            </header>

            <div class="flex flex-1 flex-col gap-4 p-4 sm:p-6 lg:flex-row lg:gap-8">
                <!-- Mobile: horizontal folder chips (scrollable) -->
                <div class="flex shrink-0 overflow-x-auto overscroll-x-contain pb-1 lg:hidden" aria-label="Folder filter">
                    <div class="flex gap-2">
                        <button
                            type="button"
                            class="shrink-0 rounded-full px-3 py-1.5 text-sm font-medium transition-colors"
                            :class="selectedFolderId === 'all' ? 'bg-primary text-primary-foreground' : 'bg-muted text-foreground hover:bg-muted/80'"
                            @click="selectFolder('all')"
                        >
                            All
                        </button>
                        <button
                            type="button"
                            class="shrink-0 rounded-full px-3 py-1.5 text-sm font-medium transition-colors"
                            :class="selectedFolderId === 'uncategorized' ? 'bg-primary text-primary-foreground' : 'bg-muted text-foreground hover:bg-muted/80'"
                            @click="selectFolder('uncategorized')"
                        >
                            Uncategorized
                        </button>
                        <button
                            v-for="f in folders"
                            :key="f.id"
                            type="button"
                            class="shrink-0 max-w-[8rem] truncate rounded-full px-3 py-1.5 text-sm font-medium transition-colors"
                            :class="selectedFolderId === f.id ? 'bg-primary text-primary-foreground' : 'bg-muted text-foreground hover:bg-muted/80'"
                            :title="f.name"
                            @click="selectFolder(f.id)"
                        >
                            {{ f.name }}
                        </button>
                        <Button
                            variant="outline"
                            size="sm"
                            class="shrink-0 rounded-full gap-1.5 px-3 py-1.5 text-sm"
                            @click="newFolderOpen = true"
                        >
                            <FolderPlus class="h-3.5 w-3.5" aria-hidden />
                            New
                        </Button>
                    </div>
                </div>

                <!-- Desktop: folder sidebar -->
                <aside class="hidden shrink-0 lg:block lg:w-56">
                    <div class="rounded-xl border border-border bg-card p-2 shadow-sm">
                        <p class="mb-2 px-2 text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                            Folders
                        </p>
                        <nav class="flex flex-col gap-0.5">
                            <button
                                type="button"
                                class="flex items-center gap-2 rounded-lg px-2 py-1.5 text-left text-sm font-medium transition-colors hover:bg-muted"
                                :class="selectedFolderId === 'all' ? 'bg-primary/10 text-primary' : 'text-foreground'"
                                @click="selectFolder('all')"
                            >
                                <Inbox class="h-4 w-4 shrink-0" aria-hidden />
                                All
                            </button>
                            <button
                                type="button"
                                class="flex items-center gap-2 rounded-lg px-2 py-1.5 text-left text-sm font-medium transition-colors hover:bg-muted"
                                :class="selectedFolderId === 'uncategorized' ? 'bg-primary/10 text-primary' : 'text-foreground'"
                                @click="selectFolder('uncategorized')"
                            >
                                <Folder class="h-4 w-4 shrink-0 opacity-60" aria-hidden />
                                Uncategorized
                            </button>
                            <template v-for="f in folders" :key="f.id">
                                <button
                                    type="button"
                                    class="flex items-center gap-2 rounded-lg px-2 py-1.5 text-left text-sm font-medium transition-colors hover:bg-muted"
                                    :class="selectedFolderId === f.id ? 'bg-primary/10 text-primary' : 'text-foreground'"
                                    @click="selectFolder(f.id)"
                                >
                                    <Folder class="h-4 w-4 shrink-0" aria-hidden />
                                    <span class="min-w-0 truncate">{{ f.name }}</span>
                                </button>
                            </template>
                        </nav>
                        <div class="mt-2 border-t border-border pt-2">
                            <Button
                                variant="ghost"
                                size="sm"
                                class="w-full justify-start gap-2 text-muted-foreground hover:text-foreground"
                                @click="newFolderOpen = true"
                            >
                                <FolderPlus class="h-4 w-4 shrink-0" aria-hidden />
                                New folder
                            </Button>
                        </div>
                    </div>
                </aside>

                <div class="min-w-0 flex-1">
                    <DataListSection
                        ref="listRef"
                        mode="full"
                        :per-page="15"
                        :folder-id="selectedFolderId"
                        :folders="folders"
                        view-base-path="/dashboard/data"
                        from-context="data"
                        @update:folders="onUpdateFolders"
                    />
                </div>
            </div>
        </div>
    </AppLayout>

    <Dialog :open="newFolderOpen" @update:open="newFolderOpen = $event">
        <DialogContent class="sm:max-w-sm">
            <DialogHeader>
                <DialogTitle>New folder</DialogTitle>
            </DialogHeader>
            <form class="flex flex-col gap-3" @submit.prevent="createFolder">
                <input
                    v-model="newFolderName"
                    type="text"
                    placeholder="Folder name"
                    class="rounded-lg border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring"
                    aria-label="Folder name"
                />
                <p v-if="newFolderError" class="text-sm text-destructive">
                    {{ newFolderError }}
                </p>
                <DialogFooter class="gap-2">
                    <DialogClose as-child>
                        <Button type="button" variant="secondary">
                            Cancel
                        </Button>
                    </DialogClose>
                    <Button type="submit" :disabled="!newFolderName.trim() || newFolderLoading">
                        {{ newFolderLoading ? 'Creating…' : 'Create' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
