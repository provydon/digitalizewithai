<script setup lang="ts">
import { nextTick, ref, watch } from 'vue';
import { renderMarkdown } from '@/lib/markdown';
import type { ChatMessage } from '../types';

const props = defineProps<{
    recordName: string;
    messages: ChatMessage[];
    suggestedPrompts: string[];
    insights: string[];
    askLoading: boolean;
    askError: string | null;
}>();

const emit = defineEmits<{
    ask: [prompt?: string];
}>();

const question = ref('');
const chatScrollRef = ref<HTMLElement | null>(null);

watch(
    () => props.messages.length,
    () => {
        nextTick(() => {
            chatScrollRef.value?.scrollTo({
                top: chatScrollRef.value.scrollHeight,
                behavior: 'smooth',
            });
        });
    },
);

function submitQuestion() {
    const q = question.value.trim();
    if (!q) return;
    question.value = '';
    emit('ask', q);
}
</script>

<template>
    <div class="flex min-h-[320px] flex-col p-3 sm:min-h-[420px] sm:p-4">
        <div
            ref="chatScrollRef"
            class="flex flex-1 flex-col gap-3 overflow-y-auto rounded-lg py-2"
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
                        </template>
                    </span>
                </div>
            </template>
        </div>
        <div class="mt-4 shrink-0 space-y-4 sm:mt-3 sm:space-y-3">
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
                        class="inline-flex cursor-pointer items-center rounded border border-gray-300 bg-muted/50 px-2 py-1 text-[11px] leading-tight text-foreground transition-colors hover:bg-muted hover:border-primary/50 dark:border-gray-600 dark:hover:border-primary/50 sm:rounded-lg sm:border-2 sm:px-3 sm:py-1.5 sm:text-sm"
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
                <textarea
                    v-model="question"
                    class="min-h-[44px] w-full flex-1 rounded-lg border-2 border-gray-300 bg-background px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring dark:border-gray-600"
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
            <p v-if="askError" class="text-sm text-destructive">
                {{ askError }}
            </p>
        </div>
    </div>
</template>
