<script setup lang="ts">
import { Copy, Pencil, Search } from 'lucide-vue-next';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import PaginationPills from './PaginationPills.vue';

withDefaults(
    defineProps<{
        docSearch: string;
        displayedContent: string;
        docPageCount: number;
        docPageCurrent: number;
        isMultiPage: boolean;
        docPageLoading: boolean;
        docPageError: string | null;
        docEditing: boolean;
        docEditContent: string;
        docEditSaving: boolean;
        docEditError: string | null;
        copyFeedback: boolean;
        copyTooltipOpen?: boolean;
    }>(),
    { copyTooltipOpen: undefined },
);

const emit = defineEmits<{
    'update:docSearch': [v: string];
    'update:docEditContent': [v: string];
    'start-edit': [];
    'cancel-edit': [];
    'save-edit': [];
    'go-to-page': [page: number];
    'copy': [];
    'update:copyTooltipOpen': [v: boolean | undefined];
}>();

function onDocSearchInput(e: Event) {
    emit('update:docSearch', (e.target as HTMLInputElement).value);
}

function onDocEditInput(e: Event) {
    emit('update:docEditContent', (e.target as HTMLTextAreaElement).value);
}
</script>

<template>
    <div class="content-paper space-y-4 rounded-xl bg-white p-4 text-gray-900 shadow-sm sm:p-5">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div class="relative min-w-0 flex-1 basis-full sm:basis-0 sm:max-w-sm">
                <Search
                    class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-500"
                />
                <input
                    :value="docSearch"
                    type="search"
                    placeholder="Search for anything in the document"
                    class="w-full rounded-lg border-2 border-gray-300 bg-gray-50 py-2 pl-9 pr-3 text-sm text-gray-900 placeholder:text-gray-500 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary"
                    @input="onDocSearchInput"
                />
            </div>
            <div class="ml-auto flex items-center gap-2">
                <button
                    v-if="!docEditing"
                    type="button"
                    class="inline-flex items-center gap-1.5 rounded border border-gray-300 bg-white px-2 py-1.5 text-sm text-gray-700 hover:bg-gray-100"
                    title="Edit"
                    @click="emit('start-edit')"
                >
                    Edit
                    <Pencil class="h-3.5 w-3.5" />
                </button>
                <Tooltip
                    :open="copyTooltipOpen"
                    @update:open="(v) => emit('update:copyTooltipOpen', v === false ? undefined : v)"
                >
                    <TooltipTrigger as-child>
                        <button
                            type="button"
                            class="inline-flex items-center gap-1.5 rounded border border-gray-300 bg-white px-2 py-1.5 text-sm text-gray-700 hover:bg-gray-100"
                            @click="emit('copy')"
                        >
                            Copy
                            <Copy class="h-3.5 w-3.5" />
                        </button>
                    </TooltipTrigger>
                    <TooltipContent>
                        {{ copyFeedback ? 'Copied to clipboard' : 'Copy to clipboard' }}
                    </TooltipContent>
                </Tooltip>
            </div>
        </div>
        <p v-if="docPageError" class="text-sm text-destructive">
            {{ docPageError }}
        </p>
        <PaginationPills
            v-if="isMultiPage"
            :current="docPageCurrent"
            :total="docPageCount"
            slot-key-prefix="doc-top-"
            @go-to="emit('go-to-page', $event)"
        />
        <div
            v-if="docPageLoading && isMultiPage"
            class="py-8 text-center text-sm text-gray-600"
        >
            Loading page…
        </div>
        <div v-else class="min-w-0">
            <div v-if="docEditing" class="space-y-3">
                <textarea
                    :value="docEditContent"
                    class="min-h-[240px] w-full max-w-full resize-y rounded-lg border border-gray-300 bg-white p-3 font-sans text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-0 sm:p-4 sm:text-base"
                    placeholder="Document content…"
                    spellcheck="false"
                    @input="onDocEditInput"
                />
                <p v-if="docEditError" class="text-sm text-destructive">
                    {{ docEditError }}
                </p>
                <div class="flex flex-wrap gap-2">
                    <Button
                        size="sm"
                        :disabled="docEditSaving"
                        @click="emit('save-edit')"
                    >
                        {{ docEditSaving ? 'Saving…' : 'Save' }}
                    </Button>
                    <Button
                        size="sm"
                        variant="secondary"
                        :disabled="docEditSaving"
                        @click="emit('cancel-edit')"
                    >
                        Cancel
                    </Button>
                </div>
            </div>
            <template v-else>
                <pre
                    class="max-w-full overflow-x-auto whitespace-pre-wrap rounded-lg border border-gray-200 bg-gray-50 p-3 font-sans text-sm text-gray-900 sm:p-4 sm:text-base"
                >{{ displayedContent || ' ' }}</pre>
            </template>
        </div>
        <PaginationPills
            v-if="isMultiPage"
            :current="docPageCurrent"
            :total="docPageCount"
            slot-key-prefix="doc-bottom-"
            class="py-3"
            @go-to="emit('go-to-page', $event)"
        />
    </div>
</template>
