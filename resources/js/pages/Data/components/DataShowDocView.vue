<script setup lang="ts">
import { Copy, Ellipsis, Mic, Pause, Pencil, Play, Search, Square, Upload } from 'lucide-vue-next';
import { computed, nextTick, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { renderMarkdown } from '@/lib/markdown';
import PaginationPills from './PaginationPills.vue';
import ReadAloudPlayingIcon from './ReadAloudPlayingIcon.vue';

const props = withDefaults(
    defineProps<{
        docSearch: string;
        /** Section texts for scroll-to-page (one long doc split into sections). */
        docSections: string[];
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
        canReadAloud?: boolean;
        readAloudPlaying?: boolean;
        readAloudPaused?: boolean;
        readAloudError?: string | null;
        readAloudVoices?: { lang: string; label: string }[];
        readAloudVoiceId?: string;
    }>(),
    {
        docSections: () => [],
        copyTooltipOpen: undefined,
        canReadAloud: false,
        readAloudPlaying: false,
        readAloudPaused: false,
        readAloudError: null,
        readAloudVoices: () => [],
        readAloudVoiceId: '',
    },
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
    'open-append-doc': [];
    'read-aloud': [];
    'read-aloud-pause': [];
    'read-aloud-resume': [];
    'read-aloud-stop': [];
    'update:read-aloud-voice': [voiceId: string];
}>();

function onDocSearchInput(e: Event) {
    emit('update:docSearch', (e.target as HTMLInputElement).value);
}

function onDocEditInput(e: Event) {
    emit('update:docEditContent', (e.target as HTMLTextAreaElement).value);
}

const q = computed(() => props.docSearch.trim().toLowerCase());

const renderedContent = computed(() => renderMarkdown(props.displayedContent || ''));

/** For sectioned view: filter each section by search and render. When searching, hide sections with no matching content. */
const sectionsToShow = computed(() => {
    const sections = props.docSections;
    if (!sections.length) return [];
    const withPageNum = (item: { text: string; rendered: string }, pageNum: number) => ({ ...item, pageNumber: pageNum });
    if (!q.value) {
        return sections.map((text, i) => withPageNum(
            { text, rendered: renderMarkdown(text || '') },
            i + 1,
        ));
    }
    const mapped = sections.map((text, i) => {
        const lines = text.split('\n');
        const matched = lines.filter((line) => line.toLowerCase().includes(q.value));
        const filtered = matched.length ? matched.join('\n') : '';
        return withPageNum(
            { text: filtered, rendered: renderMarkdown(filtered || '') },
            i + 1,
        );
    });
    return mapped.filter((item) => item.text.trim() !== '');
});

const readAloudModalOpen = ref(false);
const readAloudModalAccent = ref('');
const readAloudModalFocusGuardRef = ref<HTMLSpanElement | null>(null);

function openReadAloudModal() {
    readAloudModalAccent.value = props.readAloudVoiceId ?? '';
    readAloudModalOpen.value = true;
}

// Prevent accent <select> from stealing focus on mobile (which opens native picker)
watch(readAloudModalOpen, (open) => {
    if (open) {
        void nextTick(() => readAloudModalFocusGuardRef.value?.focus({ preventScroll: true }));
    }
});

function startReadAloudFromModal() {
    emit('update:read-aloud-voice', readAloudModalAccent.value);
    readAloudModalOpen.value = false;
    emit('read-aloud');
}
</script>

<template>
    <div class="content-paper space-y-5 rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:p-6">
        <div class="flex flex-nowrap items-center justify-between gap-2 sm:flex-wrap sm:gap-3">
            <div class="relative min-w-0 flex-1 sm:max-w-xs">
                <Search
                    class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-500"
                />
                <input
                    :value="docSearch"
                    type="search"
                    placeholder="Search in document"
                    class="content-paper__search w-full rounded-lg border border-gray-300 bg-gray-50 py-2 pl-9 pr-3 text-sm text-gray-900 placeholder:text-gray-500 focus:border-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-400/30"
                    @input="onDocSearchInput"
                />
            </div>
            <!-- Desktop: all action buttons -->
            <div class="hidden flex-wrap items-center gap-2 sm:flex">
                <template v-if="canReadAloud && readAloudPlaying">
                    <span class="flex items-center text-gray-500" aria-hidden="true">
                        <ReadAloudPlayingIcon :paused="readAloudPaused" />
                    </span>
                    <Tooltip>
                        <TooltipTrigger as-child>
                            <Button
                                variant="outline"
                                size="sm"
                                :title="readAloudPaused ? 'Continue' : 'Pause'"
                                class="content-paper__btn"
                                @click="readAloudPaused ? emit('read-aloud-resume') : emit('read-aloud-pause')"
                            >
                                <Pause v-if="!readAloudPaused" class="mr-1.5 h-3.5 w-3.5" />
                                <Play v-else class="mr-1.5 h-3.5 w-3.5" />
                                {{ readAloudPaused ? 'Continue' : 'Pause' }}
                            </Button>
                        </TooltipTrigger>
                        <TooltipContent>{{ readAloudPaused ? 'Continue reading' : 'Pause' }}</TooltipContent>
                    </Tooltip>
                    <Tooltip>
                        <TooltipTrigger as-child>
                            <Button
                                variant="outline"
                                size="sm"
                                title="Stop and start over"
                                class="content-paper__btn"
                                @click="emit('read-aloud-stop')"
                            >
                                <Square class="mr-1.5 h-3.5 w-3.5" />
                                Stop
                            </Button>
                        </TooltipTrigger>
                        <TooltipContent>Stop and start from beginning next time</TooltipContent>
                    </Tooltip>
                </template>
                <Tooltip v-if="canReadAloud">
                    <TooltipTrigger as-child>
                        <Button
                            variant="outline"
                            size="sm"
                            title="Read all pages aloud"
                            class="content-paper__btn"
                            :disabled="docPageLoading || docEditing"
                            @click="openReadAloudModal()"
                        >
                            <Mic class="mr-1.5 h-3.5 w-3.5" />
                            Read aloud
                        </Button>
                    </TooltipTrigger>
                    <TooltipContent>Read all pages aloud</TooltipContent>
                </Tooltip>
                <Button
                    v-if="!docEditing"
                    variant="outline"
                    size="sm"
                    title="Edit"
                    class="content-paper__btn"
                    @click="emit('start-edit')"
                >
                    <Pencil class="mr-1.5 h-3.5 w-3.5" />
                    Edit
                </Button>
                <Tooltip :open="copyTooltipOpen" @update:open="(v) => emit('update:copyTooltipOpen', v === false ? undefined : v)">
                    <TooltipTrigger as-child>
                        <Button
                            variant="outline"
                            size="sm"
                            class="content-paper__btn"
                            @click="emit('copy')"
                        >
                            <Copy class="mr-1.5 h-3.5 w-3.5" />
                            Copy
                        </Button>
                    </TooltipTrigger>
                    <TooltipContent>{{ copyFeedback ? 'Copied to clipboard' : 'Copy to clipboard' }}</TooltipContent>
                </Tooltip>
                <Button
                    variant="outline"
                    size="sm"
                    title="Add content from photo or video"
                    class="content-paper__btn"
                    @click="emit('open-append-doc')"
                >
                    <Upload class="mr-1.5 h-3.5 w-3.5" />
                    Add
                </Button>
            </div>
            <!-- Mobile: actions on same line as search -->
            <div class="shrink-0 sm:hidden">
                <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                        <Button
                            variant="outline"
                            size="sm"
                            title="Actions"
                            class="content-paper__btn"
                        >
                            <Ellipsis class="h-3.5 w-3.5 shrink-0" />
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" class="w-48">
                    <DropdownMenuItem
                        v-if="canReadAloud"
                        :disabled="docPageLoading || docEditing"
                        @click="openReadAloudModal()"
                    >
                        <Mic class="mr-2 h-3.5 w-3.5" />
                        Read aloud
                    </DropdownMenuItem>
                    <DropdownMenuItem v-if="!docEditing" @click="emit('start-edit')">
                        <Pencil class="mr-2 h-3.5 w-3.5" />
                        Edit
                    </DropdownMenuItem>
                    <DropdownMenuItem @click="emit('copy')">
                        <Copy class="mr-2 h-3.5 w-3.5" />
                        Copy
                    </DropdownMenuItem>
                    <DropdownMenuItem @click="emit('open-append-doc')">
                        <Upload class="mr-2 h-3.5 w-3.5" />
                        Add
                    </DropdownMenuItem>
                </DropdownMenuContent>
                </DropdownMenu>
            </div>
        </div>
        <!-- Mobile: Pause / Stop when read aloud is playing (desktop has these in the main row) -->
        <div
            v-if="canReadAloud && readAloudPlaying"
            class="flex flex-wrap items-center gap-2 sm:hidden"
        >
            <span class="flex items-center text-gray-500" aria-hidden="true">
                <ReadAloudPlayingIcon :paused="readAloudPaused" />
            </span>
            <Button
                variant="outline"
                size="sm"
                :title="readAloudPaused ? 'Continue' : 'Pause'"
                class="content-paper__btn"
                @click="readAloudPaused ? emit('read-aloud-resume') : emit('read-aloud-pause')"
            >
                <Pause v-if="!readAloudPaused" class="mr-1.5 h-3.5 w-3.5" />
                <Play v-else class="mr-1.5 h-3.5 w-3.5" />
                {{ readAloudPaused ? 'Continue' : 'Pause' }}
            </Button>
            <Button
                variant="outline"
                size="sm"
                title="Stop and start over"
                class="content-paper__btn"
                @click="emit('read-aloud-stop')"
            >
                <Square class="mr-1.5 h-3.5 w-3.5" />
                Stop
            </Button>
        </div>
        <!-- Read aloud modal: choose accent then start -->
        <Dialog :open="readAloudModalOpen" @update:open="readAloudModalOpen = $event">
            <DialogContent class="sm:max-w-sm">
                <span
                    ref="readAloudModalFocusGuardRef"
                    tabindex="0"
                    class="sr-only"
                    aria-hidden="true"
                />
                <DialogHeader>
                    <DialogTitle>Read aloud</DialogTitle>
                </DialogHeader>
                <p class="text-sm text-muted-foreground">
                    Choose accent (optional), then start. All pages will be read in order.
                </p>
                <select
                    v-if="readAloudVoices?.length"
                    v-model="readAloudModalAccent"
                    tabindex="-1"
                    class="mt-2 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-400/30"
                    aria-label="Choose accent (optional)"
                >
                    <option value="">Default accent</option>
                    <option
                        v-for="a in readAloudVoices"
                        :key="a.lang"
                        :value="a.lang"
                    >
                        {{ a.label }}
                    </option>
                </select>
                <DialogFooter class="mt-4 gap-2">
                    <Button
                        variant="outline"
                        @click="readAloudModalOpen = false"
                    >
                        Cancel
                    </Button>
                    <Button @click="startReadAloudFromModal">
                        <Mic class="mr-2 h-4 w-4" />
                        Start
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
        <p v-if="readAloudError" class="text-sm text-red-600">
            {{ readAloudError }}
        </p>
        <p v-if="docPageError" class="text-sm text-red-600">
            {{ docPageError }}
        </p>
        <div v-if="isMultiPage" class="content-paper__pagination">
            <PaginationPills
                :current="docPageCurrent"
                :total="docPageCount"
                slot-key-prefix="doc-top-"
                @go-to="emit('go-to-page', $event)"
            />
        </div>
        <div
            v-if="docPageLoading"
            class="py-12 text-center text-sm text-gray-500"
        >
            Loading document…
        </div>
        <div v-else class="min-w-0">
            <div v-if="docEditing" class="space-y-3">
                <textarea
                    :value="docEditContent"
                    class="min-h-[280px] w-full max-w-full resize-y rounded-lg border border-gray-300 bg-gray-50 p-4 text-sm text-gray-900 focus:border-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-400/30 sm:text-base"
                    placeholder="Document content…"
                    spellcheck="false"
                    @input="onDocEditInput"
                />
                <p v-if="docEditError" class="text-sm text-red-600">
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
                        class="content-paper__btn"
                        :disabled="docEditSaving"
                        @click="emit('cancel-edit')"
                    >
                        Cancel
                    </Button>
                </div>
            </div>
            <div
                v-else
                class="doc-prose max-w-full overflow-x-auto rounded-lg border border-gray-200 bg-gray-50 px-4 py-5 sm:px-6 sm:py-6"
                :class="{ 'max-h-[70vh] overflow-y-auto': isMultiPage && sectionsToShow.length > 1 }"
            >
                <template v-if="isMultiPage && sectionsToShow.length > 0">
                    <section
                        v-for="(item, i) in sectionsToShow"
                        :id="'doc-section-' + item.pageNumber"
                        :key="item.pageNumber"
                        class="doc-prose__section min-h-[2em] pb-6 last:pb-0"
                        :class="i < sectionsToShow.length - 1 ? 'border-b-2 border-gray-400 mb-6' : ''"
                    >
                        <p class="doc-prose__page-label mb-2 text-sm font-medium text-gray-500">
                            Page {{ item.pageNumber }}:
                        </p>
                        <div
                            v-if="item.rendered"
                            class="doc-prose__content text-gray-900"
                            v-html="item.rendered"
                        />
                        <p v-else class="text-gray-500">No content in this section.</p>
                    </section>
                </template>
                <template v-else>
                    <div
                        v-if="renderedContent"
                        class="doc-prose__content text-gray-900"
                        v-html="renderedContent"
                    />
                    <p v-else class="text-gray-500">No content.</p>
                </template>
            </div>
        </div>
        <div v-if="isMultiPage" class="content-paper__pagination py-3">
            <PaginationPills
                :current="docPageCurrent"
                :total="docPageCount"
                slot-key-prefix="doc-bottom-"
                @go-to="emit('go-to-page', $event)"
            />
        </div>
    </div>
</template>

<style scoped>
.content-paper__btn {
    border-color: rgb(209 213 219) !important;
    background-color: white !important;
    color: rgb(55 65 81) !important;
}
.content-paper__btn:hover:not(:disabled) {
    background-color: rgb(243 244 246) !important;
    color: rgb(17 24 39) !important;
}

.content-paper__pagination :deep(button) {
    border-color: rgb(209 213 219);
    color: rgb(17 24 39);
}
.content-paper__pagination :deep(button:hover:not(:disabled)) {
    background: rgb(243 244 246);
}
.content-paper__pagination :deep(button[class*='bg-primary']) {
    border-color: var(--color-primary);
    background: var(--color-primary);
    color: var(--color-primary-foreground);
}

.doc-prose__content :deep(h1) {
    font-size: 1.75rem;
    font-weight: 700;
    letter-spacing: -0.02em;
    line-height: 1.25;
    margin-bottom: 0.5rem;
    margin-top: 0;
}
.doc-prose__content :deep(h2) {
    font-size: 1.25rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
    margin-top: 1.25rem;
}
.doc-prose__content :deep(h3),
.doc-prose__content :deep(h4) {
    font-size: 1.1rem;
    font-weight: 600;
    margin-bottom: 0.375rem;
    margin-top: 1rem;
}
.doc-prose__content :deep(p) {
    margin-bottom: 0.75rem;
    margin-top: 0;
    line-height: 1.6;
}
.doc-prose__content :deep(p:last-child) {
    margin-bottom: 0;
}
.doc-prose__content :deep(strong) {
    font-weight: 600;
}
.doc-prose__content :deep(ul),
.doc-prose__content :deep(ol) {
    margin-bottom: 0.75rem;
    margin-top: 0;
    padding-left: 1.5rem;
}
.doc-prose__content :deep(li) {
    margin-bottom: 0.25rem;
    line-height: 1.5;
}
.doc-prose__content :deep(blockquote) {
    border-left: 4px solid rgb(209 213 219);
    margin: 0.75rem 0;
    padding-left: 1rem;
    color: rgb(107 114 128);
}
.doc-prose__content :deep(pre),
.doc-prose__content :deep(code) {
    font-family: ui-monospace, monospace;
    font-size: 0.9em;
}
.doc-prose__content :deep(pre) {
    background: rgb(229 231 235);
    border-radius: 0.5rem;
    overflow-x: auto;
    padding: 0.75rem 1rem;
    margin: 0.75rem 0;
}
</style>
