<script setup lang="ts">
import { useMediaQuery } from '@vueuse/core';
import { computed } from 'vue';

const props = defineProps<{
    current: number;
    total: number;
    slotKeyPrefix?: string;
}>();

const emit = defineEmits<{
    'go-to': [page: number];
}>();

const isDesktop = useMediaQuery('(min-width: 768px)');
const slots = computed(() => {
    const total = props.total;
    if (total <= 0) return [];
    if (isDesktop.value) return Array.from({ length: total }, (_, i) => i + 1);
    // Mobile: 1, 2, ..., last so it fits in one row
    if (total <= 3) return Array.from({ length: total }, (_, i) => i + 1);
    return [1, 2, 'ellipsis' as const, total];
});
</script>

<template>
    <div class="flex flex-nowrap items-center justify-center gap-1 overflow-x-auto py-2 sm:flex-wrap">
        <button
            type="button"
            class="shrink-0 rounded-lg border border-sidebar-border/70 px-2 py-1.5 text-sm font-medium text-foreground hover:bg-muted/60 disabled:opacity-50 dark:border-sidebar-border sm:px-2.5"
            :disabled="current <= 1"
            @click="emit('go-to', current - 1)"
        >
            Previous
        </button>
        <template v-for="(slot, idx) in slots" :key="(slotKeyPrefix ?? '') + idx">
            <button
                v-if="slot !== 'ellipsis'"
                type="button"
                class="shrink-0 min-w-[2rem] rounded-lg border px-2 py-1.5 text-sm font-medium transition-colors sm:min-w-[2.25rem] sm:px-2.5"
                :class="current === slot ? 'border-primary bg-primary text-primary-foreground' : 'border-sidebar-border/70 text-foreground hover:bg-muted/60 dark:border-sidebar-border'"
                @click="emit('go-to', slot)"
            >
                {{ slot }}
            </button>
            <span v-else class="shrink-0 px-0.5 text-muted-foreground sm:px-1">…</span>
        </template>
        <button
            type="button"
            class="shrink-0 rounded-lg border border-sidebar-border/70 px-2 py-1.5 text-sm font-medium text-foreground hover:bg-muted/60 disabled:opacity-50 dark:border-sidebar-border sm:px-2.5"
            :disabled="current >= total"
            @click="emit('go-to', current + 1)"
        >
            Next
        </button>
    </div>
</template>
