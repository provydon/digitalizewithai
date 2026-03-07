<script setup lang="ts">
import { Bookmark, ChevronDown, ChevronRight, ExternalLink, Loader2, MessageSquarePlus, Trash2 } from 'lucide-vue-next';
import { nextTick, ref, watch } from 'vue';
import { renderMarkdown } from '@/lib/markdown';
import type { ChatMessage, SavedChat } from '../types';

const props = defineProps<{
    recordName: string;
    aiModelLabel: string;
    messages: ChatMessage[];
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
    ask: [prompt?: string];
    saveChat: [name?: string];
    loadChat: [chat: SavedChat];
    deleteChat: [chat: SavedChat];
    newChat: [];
}>();

const savedChatsOpen = ref(false);
const question = ref('');
const chatScrollRef = ref<HTMLElement | null>(null);

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

// Scroll as streaming content grows
watch(
    () => {
        const msgs = props.messages;
        const last = msgs[msgs.length - 1];
        return last?.role === 'assistant' ? last.content.length : 0;
    },
    scrollToBottom,
);

function submitQuestion() {
    const q = question.value.trim();
    if (!q) return;
    question.value = '';
    emit('ask', q);
}

function onSaveChat() {
    if (props.saveChatLoading || !props.canSaveChat) return;
    emit('saveChat');
}

function chatTitle(chat: SavedChat): string {
    if (chat.name?.trim()) return chat.name;
    const first = chat.messages?.[0];
    const text = first?.content?.trim();
    return text ? (text.slice(0, 40) + (text.length > 40 ? '…' : '')) : 'Untitled chat';
}
</script>

<template>
    <div class="flex h-[60vh] min-h-[320px] max-h-[560px] flex-col p-3 sm:p-4">
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
                        class="inline-block max-w-[85%] rounded-lg px-3 py-2 text-left text-sm"
                        :class="
                            msg.role === 'user'
                                ? 'bg-primary text-primary-foreground'
                                : 'bg-muted/60 text-foreground dark:bg-muted/50'
                        "
                    >
                        <template v-if="msg.role === 'user'">
                            <span class="whitespace-pre-wrap">{{ msg.content }}</span>
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
                                class="prose prose-sm max-w-none dark:prose-invert prose-p:my-1 prose-ul:my-1 prose-ol:my-1 prose-li:my-0 prose-headings:my-2 first:prose-p:mt-0 last:prose-p:mb-0"
                                v-html="renderMarkdown(msg.content)"
                            />
                            <a
                                v-if="(msg as ChatMessage).view_data_url"
                                :href="(msg as ChatMessage).view_data_url"
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
        <div class="mt-4 shrink-0 space-y-4 sm:mt-3 sm:space-y-3">
            <p
                v-if="aiModelLabel"
                class="px-2 text-xs text-muted-foreground"
                title="Same model used when this data was extracted"
            >
                Using: {{ aiModelLabel }}
            </p>
            <template v-if="messages.length === 0">
                <p class="hidden px-2 pt-1 pb-2 text-center text-sm text-muted-foreground sm:block sm:pb-1">
                    Ask anything about
                    <span class="inline-block font-bold text-lg text-foreground">{{ recordName || 'this data' }}</span>
                    . Type below or click a suggestion below.
                </p>
                <div
                    v-if="suggestedPrompts.length > 0"
                    class="flex flex-wrap justify-center gap-1.5 px-1 py-1.5 sm:gap-2.5 sm:px-2 sm:py-2"
                >
                    <button
                        v-for="(p, i) in suggestedPrompts"
                        :key="i"
                        type="button"
                        class="inline-flex cursor-pointer items-center rounded border border-input bg-muted/50 px-2 py-1 text-[11px] leading-tight text-foreground transition-colors hover:bg-muted hover:border-primary/50 sm:rounded-lg sm:border-2 sm:px-3 sm:py-1.5 sm:text-sm"
                        @click="emit('ask', p)"
                    >
                        {{ p }}
                    </button>
                </div>
                <div
                    v-if="insights.length > 0"
                    class="hidden sm:mx-2 sm:flex sm:flex-wrap sm:justify-center sm:gap-1.5 sm:border-t sm:border-border/60 sm:pt-3 sm:pb-1"
                >
                    <span
                        v-for="(insight, i) in insights"
                        :key="i"
                        class="rounded-md bg-muted/40 px-2 py-1 text-xs text-muted-foreground"
                    >
                        {{ insight }}
                    </span>
                </div>
            </template>
            <div class="flex flex-col gap-3 pt-1 sm:gap-2 sm:pt-0">
                <div class="flex flex-wrap items-center gap-2">
                    <button
                        type="button"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-sidebar-border/70 bg-muted/50 px-2.5 py-1.5 text-xs font-medium text-foreground hover:bg-muted dark:border-sidebar-border"
                        :disabled="askLoading"
                        title="Start a new chat"
                        @click="emit('newChat')"
                    >
                        <MessageSquarePlus class="h-3.5 w-3.5" />
                        New chat
                    </button>
                    <button
                        v-if="canSaveChat"
                        type="button"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-sidebar-border/70 bg-muted/50 px-2.5 py-1.5 text-xs font-medium text-foreground hover:bg-muted dark:border-sidebar-border"
                        :disabled="saveChatLoading"
                        @click="onSaveChat"
                    >
                        <Loader2 v-if="saveChatLoading" class="h-3.5 w-3.5 animate-spin" />
                        <Bookmark v-else class="h-3.5 w-3.5" />
                        Save chat
                    </button>
                </div>
                <textarea
                    v-model="question"
                    class="min-h-[44px] w-full flex-1 rounded-lg border-2 border-input bg-background px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring"
                    placeholder="Ask about this data..."
                    rows="1"
                    @keydown.enter.exact.prevent="submitQuestion"
                />
                <button
                    type="button"
                    class="min-h-[44px] w-full shrink-0 rounded-lg bg-primary px-4 py-2.5 text-sm font-medium text-primary-foreground hover:bg-primary/90 disabled:opacity-50 sm:h-fit sm:w-auto sm:self-end sm:py-2"
                    :disabled="askLoading || !question.trim()"
                    @click="submitQuestion"
                >
                    {{ askLoading ? '…' : 'Send' }}
                </button>
            </div>
            <p v-if="askError || savedChatError" class="text-sm text-destructive">
                {{ askError ?? savedChatError }}
            </p>
            <div v-if="savedChats.length > 0" class="border-t border-sidebar-border/70 pt-3">
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
