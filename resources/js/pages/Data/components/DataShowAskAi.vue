<script setup lang="ts">
import { ChevronDown, ChevronRight, ExternalLink, FileText, Mic, Paperclip, Pencil, Square, Trash2, X } from 'lucide-vue-next';
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue';
import { renderMarkdown } from '@/lib/markdown';
import type { ChatMessage, SavedChat } from '../types';

const props = defineProps<{
    recordName: string;
    aiModelLabel: string;
    messages: ChatMessage[];
    /** When set, parent has truncated messages and wants this text in the input for editing. */
    editMessageContent: string | null;
    suggestedPrompts: string[];
    insights: string[];
    askLoading: boolean;
    askError: string | null;
    savedChats: SavedChat[];
    saveChatLoading: boolean;
    savedChatError: string | null;
    canSaveChat: boolean;
}>();

const emit = defineEmits<{
    ask: [prompt?: string, attachments?: File[]];
    stop: [];
    editMessage: [index: number];
    clearEdit: [];
    saveChat: [name?: string];
    loadChat: [chat: SavedChat];
    deleteChat: [chat: SavedChat];
    newChat: [];
}>();

const savedChatsOpen = ref(false);
const question = ref('');
const chatScrollRef = ref<HTMLElement | null>(null);
const attachments = ref<File[]>([]);
const attachmentPreviewUrls = ref<string[]>([]);
const fileInputRef = ref<HTMLInputElement | null>(null);

function addAttachments(files: FileList | File[]) {
    const list = Array.from(files);
    for (const file of list) {
        attachments.value.push(file);
        attachmentPreviewUrls.value.push(file.type.startsWith('image/') ? URL.createObjectURL(file) : '');
    }
}

function removeAttachment(index: number) {
    if (attachmentPreviewUrls.value[index]) {
        URL.revokeObjectURL(attachmentPreviewUrls.value[index]);
    }
    attachments.value.splice(index, 1);
    attachmentPreviewUrls.value.splice(index, 1);
}

const speechSupported = computed(() => {
    if (typeof window === 'undefined') return false;
    const w = window as unknown as { SpeechRecognition?: unknown; webkitSpeechRecognition?: unknown };
    return !!(w.SpeechRecognition ?? w.webkitSpeechRecognition);
});

const isListening = ref(false);
const sendAfterStop = ref(false);
const speechError = ref<string | null>(null);
let recognition: { start: () => void; stop: () => void; abort: () => void } | null = null;

function startSpeechInput() {
    if (isListening.value || props.askLoading) return;
    speechError.value = null;

    if (typeof window === 'undefined') {
        speechError.value = 'Speech not available.';
        return;
    }
    const w = window as unknown as { SpeechRecognition?: new () => SpeechRecognition; webkitSpeechRecognition?: new () => SpeechRecognition };
    const Ctor = w.SpeechRecognition ?? w.webkitSpeechRecognition;
    if (!Ctor) {
        speechError.value = 'Speech recognition is not supported in this browser.';
        return;
    }
    if (!(window as unknown as { isSecureContext?: boolean }).isSecureContext) {
        speechError.value = 'Microphone requires HTTPS or localhost.';
        return;
    }

    // Show recording UI immediately so user sees feedback
    isListening.value = true;

    try {
        const rec = new Ctor();
        recognition = rec as unknown as { start: () => void; stop: () => void; abort: () => void };
        (rec as unknown as { continuous: boolean }).continuous = true;
        (rec as unknown as { interimResults: boolean }).interimResults = true;
        (rec as unknown as { lang: string }).lang = 'en-US';

        rec.addEventListener('result', (e: Event) => {
            const ev = e as unknown as { results: { length: number; [i: number]: { isFinal?: boolean; 0?: { transcript?: string } } } };
            const results = ev.results;
            if (!results) return;
            for (let i = 0; i < results.length; i++) {
                const r = results[i];
                const isFinal = r?.isFinal !== false;
                const item = r?.[0];
                if (item?.transcript && isFinal) question.value = (question.value ? question.value + ' ' : '') + item.transcript;
            }
        });
        rec.addEventListener('end', () => {
            isListening.value = false;
            if (sendAfterStop.value) {
                sendAfterStop.value = false;
                sendMessage(question.value.trim());
            }
        });
        rec.addEventListener('error', (e: Event) => {
            const ev = e as unknown as { error?: string };
            speechError.value = ev.error === 'not-allowed' ? 'Microphone access denied.' : 'Speech recognition error.';
            isListening.value = false;
            sendAfterStop.value = false;
        });

        // Must call start() in same turn as user gesture for permission prompt
        rec.start();
    } catch (err) {
        isListening.value = false;
        speechError.value = err instanceof Error ? err.message : 'Could not start microphone.';
    }
}

function stopRecording() {
    if (!recognition || !isListening.value) return;
    try {
        recognition.stop();
    } catch {
        isListening.value = false;
    }
}

onBeforeUnmount(() => {
    if (recognition) try { recognition.abort(); } catch { /* noop */ }
    attachmentPreviewUrls.value.forEach((url) => { if (url) URL.revokeObjectURL(url); });
});

function clearAttachments() {
    attachmentPreviewUrls.value.forEach((url) => { if (url) URL.revokeObjectURL(url); });
    attachments.value = [];
    attachmentPreviewUrls.value = [];
    if (fileInputRef.value) fileInputRef.value.value = '';
}

function onFileChange(e: Event) {
    const input = e.target as HTMLInputElement;
    const files = input.files;
    if (files?.length) {
        addAttachments(files);
        input.value = '';
    }
}

function scrollToBottom() {
    nextTick(() => {
        const el = chatScrollRef.value;
        if (el) {
            el.scrollTo({
                top: el.scrollHeight,
                behavior: 'smooth',
            });
        }
    });
}

watch(
    () => props.messages.length,
    scrollToBottom,
);

// Prefill input when parent sets edit content (after truncating messages)
watch(
    () => props.editMessageContent,
    (content: string | null | undefined) => {
        if (content != null && content !== '') {
            question.value = content;
            emit('clearEdit');
        }
    },
);

// Scroll as streaming content grows
watch(
    () => {
        const msgs = props.messages;
        const last = msgs[msgs.length - 1];
        return last?.role === 'assistant' ? last.content.length : 0;
    },
    scrollToBottom,
);

function sendMessage(q: string) {
    if (!q) return;
    const files = attachments.value.length > 0 ? [...attachments.value] : undefined;
    question.value = '';
    clearAttachments();
    emit('ask', q, files);
}

function submitQuestion() {
    const q = question.value.trim();
    if (isListening.value) {
        sendAfterStop.value = true;
        stopRecording();
        return;
    }
    if (!q) return;
    sendMessage(q);
}

function chatTitle(chat: SavedChat): string {
    if (chat.name?.trim()) return chat.name;
    const first = chat.messages?.[0];
    const text = first?.content?.trim();
    return text ? (text.slice(0, 40) + (text.length > 40 ? '…' : '')) : 'Untitled chat';
}
</script>

<template>
    <div class="flex flex-col p-3 pb-4 sm:p-4 min-h-[280px] max-h-[45vh] sm:min-h-[320px] sm:h-[70vh] sm:max-h-[560px]">
        <div
            ref="chatScrollRef"
            class="flex min-h-0 flex-1 flex-col gap-3 overflow-y-auto rounded-lg py-2"
        >
            <template v-if="messages.length > 0">
                <div
                    v-for="(msg, i) in messages"
                    :key="i"
                    class="px-2"
                    :class="msg.role === 'user' ? 'text-right' : ''"
                >
                    <span
                        class="inline-block max-w-[85%] rounded-xl px-3 py-2 text-left text-sm shadow-sm sm:px-4 sm:py-3"
                        :class="
                            msg.role === 'user'
                                ? 'bg-primary text-primary-foreground'
                                : 'ask-response-bubble border border-border/80 bg-card text-foreground dark:border-border dark:bg-card'
                        "
                    >
                        <template v-if="msg.role === 'user'">
                            <div class="flex items-start justify-end gap-1">
                                <span class="min-w-0 flex-1">
                                    <div
                                        v-if="msg.attachment_previews?.length"
                                        class="mb-2 flex flex-wrap gap-1.5"
                                    >
                                        <template
                                            v-for="(preview, pIdx) in msg.attachment_previews"
                                            :key="pIdx"
                                        >
                                            <div
                                                v-if="preview.type === 'image'"
                                                class="h-14 w-14 shrink-0 overflow-hidden rounded border border-white/30 bg-black/20"
                                            >
                                                <img
                                                    :src="preview.url"
                                                    :alt="preview.name"
                                                    class="h-full w-full object-cover"
                                                />
                                            </div>
                                            <div
                                                v-else
                                                class="flex h-14 w-14 shrink-0 flex-col items-center justify-center gap-0.5 overflow-hidden rounded border border-white/30 bg-black/20 px-1"
                                            >
                                                <FileText class="h-5 w-5 shrink-0 opacity-90" />
                                                <span class="truncate text-[10px] leading-tight opacity-90">{{ preview.name.slice(-8) }}</span>
                                            </div>
                                        </template>
                                    </div>
                                    <span class="whitespace-pre-wrap">{{ msg.content }}</span>
                                </span>
                                <button
                                    type="button"
                                    class="shrink-0 rounded p-1 text-primary-foreground/80 hover:bg-white/20 hover:text-primary-foreground"
                                    title="Edit and resend"
                                    aria-label="Edit message"
                                    :disabled="askLoading"
                                    @click="emit('editMessage', i)"
                                >
                                    <Pencil class="h-3.5 w-3.5" />
                                </button>
                            </div>
                        </template>
                        <template v-else>
                            <div
                                v-if="!msg.content && askLoading"
                                class="flex items-center gap-1 text-muted-foreground"
                            >
                                <span class="inline-block size-1.5 animate-pulse rounded-full bg-current" />
                                <span class="inline-block size-1.5 animate-pulse rounded-full bg-current" style="animation-delay: 0.2s" />
                                <span class="inline-block size-1.5 animate-pulse rounded-full bg-current" style="animation-delay: 0.4s" />
                            </div>
                            <div
                                v-else
                                class="ask-response-content prose prose-sm max-w-none dark:prose-invert prose-p:my-1.5 prose-ul:my-1.5 prose-ol:my-1.5 prose-li:my-0.5 prose-headings:my-2 prose-headings:font-semibold first:prose-p:mt-0 last:prose-p:mb-0"
                                v-html="renderMarkdown(msg.content)"
                            />
                            <a
                                v-if="msg.view_data_url"
                                :href="msg.view_data_url"
                                class="mt-3 inline-flex items-center justify-center gap-2 rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow-sm transition-colors hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                            >
                                <ExternalLink class="h-4 w-4 shrink-0" />
                                View data
                            </a>
                        </template>
                    </span>
                </div>
            </template>
        </div>
        <div class="mt-2 shrink-0 space-y-2 sm:mt-3 sm:space-y-4">
            <p
                v-if="aiModelLabel"
                class="hidden px-2 text-xs text-muted-foreground sm:block"
                title="Same model used when this data was extracted"
            >
                Using: {{ aiModelLabel }}
            </p>
            <template v-if="messages.length === 0">
                <div
                    v-if="suggestedPrompts.length > 0"
                    class="flex flex-wrap justify-center gap-1.5 px-1 py-1 sm:gap-2.5 sm:px-2 sm:py-2"
                >
                    <button
                        v-for="(p, i) in suggestedPrompts.slice(0, 2)"
                        :key="i"
                        type="button"
                        class="inline-flex cursor-pointer items-center rounded border border-input bg-muted/50 px-2 py-1 text-[11px] leading-tight text-foreground transition-colors hover:bg-muted hover:border-primary/50 sm:rounded-lg sm:border-2 sm:px-3 sm:py-1.5 sm:text-sm"
                        @click="emit('ask', p)"
                    >
                        {{ p }}
                    </button>
                </div>
            </template>
            <div class="flex flex-col gap-2 pt-0 sm:gap-2 sm:pt-0">
                <div
                    v-if="isListening"
                    class="flex items-center gap-3 rounded-lg border-2 border-primary/40 bg-primary/10 px-3 py-2"
                >
                    <span class="relative flex h-3 w-3">
                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-primary opacity-75" />
                        <span class="relative inline-flex h-3 w-3 rounded-full bg-primary" />
                    </span>
                    <span class="text-sm font-medium text-primary">Recording…</span>
                    <button
                        type="button"
                        class="ml-auto inline-flex items-center gap-1.5 rounded-md border border-primary/50 bg-background px-2.5 py-1.5 text-xs font-medium text-primary hover:bg-primary/10"
                        title="Stop and add to message"
                        @click="stopRecording"
                    >
                        <Square class="h-3.5 w-3.5" />
                        Stop
                    </button>
                </div>
                <div
                    v-if="attachments.length > 0"
                    class="flex flex-wrap items-center gap-2 pb-1.5 sm:pb-2"
                >
                    <div
                        v-for="(file, i) in attachments"
                        :key="i"
                        class="flex items-center gap-1.5"
                    >
                        <div class="relative flex h-14 w-14 shrink-0 overflow-hidden rounded-lg border border-border bg-muted/50">
                            <img
                                v-if="attachmentPreviewUrls[i]"
                                :src="attachmentPreviewUrls[i]"
                                :alt="file.name"
                                class="h-full w-full object-cover"
                            />
                            <div
                                v-else
                                class="flex h-full w-full flex-col items-center justify-center gap-0.5 p-1 text-muted-foreground"
                            >
                                <FileText class="h-6 w-6 shrink-0" />
                                <span class="truncate text-[10px] leading-tight">{{ file.name.slice(-6) }}</span>
                            </div>
                            <button
                                type="button"
                                class="absolute -right-1 -top-1 flex h-5 w-5 items-center justify-center rounded-full border border-border bg-background text-muted-foreground shadow hover:bg-muted hover:text-foreground"
                                title="Remove attachment"
                                aria-label="Remove attachment"
                                @click="removeAttachment(i)"
                            >
                                <X class="h-3 w-3" />
                            </button>
                        </div>
                    </div>
                </div>
                <div class="flex flex-nowrap items-center gap-2">
                    <div class="flex min-w-0 flex-1 items-center gap-1.5 rounded-lg border-2 border-input bg-background focus-within:ring-2 focus-within:ring-ring sm:gap-2">
                        <input
                            ref="fileInputRef"
                            type="file"
                            class="hidden"
                            multiple
                            accept="image/*,.pdf,.txt,text/*,application/pdf"
                            aria-label="Attach files"
                            @change="onFileChange"
                        />
                        <button
                            type="button"
                            class="shrink-0 rounded p-1.5 text-muted-foreground hover:bg-muted hover:text-foreground disabled:opacity-50 sm:p-2"
                            :disabled="askLoading"
                            title="Attach file to send to AI"
                            aria-label="Attach file"
                            @click="fileInputRef?.click()"
                        >
                            <Paperclip class="h-3.5 w-3.5 sm:h-4 sm:w-4" />
                        </button>
                        <textarea
                            v-model="question"
                            class="min-h-[40px] min-w-0 flex-1 resize-none rounded-lg border-0 bg-transparent px-2 py-1.5 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none sm:min-h-[44px] sm:py-2"
                            placeholder="Ask about this data…"
                            rows="1"
                            @keydown.enter.exact.prevent="submitQuestion"
                        />
                        <button
                            v-if="speechSupported"
                            type="button"
                            class="shrink-0 rounded p-1.5 text-muted-foreground hover:bg-muted hover:text-foreground disabled:opacity-50 sm:p-2"
                            :disabled="askLoading"
                            :title="isListening ? 'Listening…' : 'Speak your question'"
                            aria-label="Speak"
                            :class="{ 'bg-primary/20 text-primary': isListening }"
                            @click="startSpeechInput"
                        >
                            <Mic class="h-3.5 w-3.5 sm:h-4 sm:w-4" />
                        </button>
                    </div>
                    <button
                        v-if="askLoading"
                        type="button"
                        class="h-[40px] shrink-0 inline-flex items-center gap-1.5 rounded-lg border-2 border-destructive/80 bg-background px-3 py-1.5 text-xs font-medium text-destructive hover:bg-destructive/10 sm:h-[44px] sm:px-4 sm:py-2 sm:text-sm"
                        @click="emit('stop')"
                    >
                        <Square class="h-3.5 w-3.5" />
                        Stop
                    </button>
                    <button
                        v-else
                        type="button"
                        class="h-[40px] shrink-0 rounded-lg bg-primary px-3 py-1.5 text-xs font-medium text-primary-foreground hover:bg-primary/90 disabled:opacity-50 sm:h-[44px] sm:px-4 sm:py-2 sm:text-sm"
                        :disabled="!question.trim() && !isListening && attachments.length === 0"
                        @click="submitQuestion"
                    >
                        {{ isListening ? 'Stop' : 'Send' }}
                    </button>
                </div>
            </div>
            <p v-if="askError || savedChatError || speechError" class="text-sm text-destructive">
                {{ askError ?? savedChatError ?? speechError }}
            </p>
            <div v-if="savedChats.length > 0" class="border-t border-sidebar-border/70 pt-2 sm:pt-3">
                <button
                    type="button"
                    class="flex w-full items-center gap-1.5 text-left text-sm font-medium text-foreground"
                    @click="savedChatsOpen = !savedChatsOpen"
                >
                    <ChevronDown v-if="savedChatsOpen" class="h-4 w-4" />
                    <ChevronRight v-else class="h-4 w-4" />
                    Saved chats ({{ savedChats.length }})
                </button>
                <ul v-show="savedChatsOpen" class="mt-2 max-h-32 space-y-1 overflow-y-auto">
                    <li
                        v-for="chat in savedChats"
                        :key="chat.id"
                        class="flex items-center justify-between gap-2 rounded border border-sidebar-border/50 bg-muted/30 px-2 py-1.5 text-sm"
                    >
                        <button
                            type="button"
                            class="min-w-0 flex-1 truncate text-left hover:underline"
                            :title="chatTitle(chat)"
                            @click="emit('loadChat', chat)"
                        >
                            {{ chatTitle(chat) }}
                        </button>
                        <button
                            type="button"
                            class="shrink-0 rounded p-0.5 text-muted-foreground hover:bg-destructive/20 hover:text-destructive"
                            title="Delete"
                            @click="emit('deleteChat', chat)"
                        >
                            <Trash2 class="h-3.5 w-3.5" />
                        </button>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Prominent, readable chat response bubble (assistant) */
.ask-response-bubble {
    background-color: hsl(var(--card));
    color: hsl(var(--card-foreground));
}
:root.dark .ask-response-bubble,
.dark .ask-response-bubble {
    background-color: hsl(var(--card));
    border-color: hsl(var(--border));
}

/* Better formatting for markdown response content */
.ask-response-content :deep(table) {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.875rem;
    margin: 0.5rem 0 0.75rem;
    border-radius: 0.5rem;
    overflow: hidden;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
}
.ask-response-content :deep(thead) {
    background: hsl(var(--muted));
}
.ask-response-content :deep(th) {
    padding: 0.5rem 0.75rem;
    text-align: left;
    font-weight: 600;
    border-bottom: 2px solid hsl(var(--border));
}
.ask-response-content :deep(td) {
    padding: 0.5rem 0.75rem;
    border-bottom: 1px solid hsl(var(--border));
}
.ask-response-content :deep(tr:last-child td) {
    border-bottom: none;
}
.ask-response-content :deep(tbody tr:hover) {
    background: hsl(var(--muted) / 0.5);
}
.ask-response-content :deep(strong) {
    font-weight: 600;
}
.ask-response-content :deep(ul),
.ask-response-content :deep(ol) {
    padding-left: 1.25rem;
}
.ask-response-content :deep(li) {
    margin-bottom: 0.25rem;
}
.ask-response-content :deep(h1),
.ask-response-content :deep(h2),
.ask-response-content :deep(h3) {
    line-height: 1.3;
    margin-top: 0.75rem;
}
.ask-response-content :deep(h1:first-child),
.ask-response-content :deep(h2:first-child),
.ask-response-content :deep(h3:first-child) {
    margin-top: 0;
}
</style>
