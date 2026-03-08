<script setup lang="ts">
import { useMediaQuery } from '@vueuse/core';
import { computed } from 'vue';
import { paginationSlots } from '../types';

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
    return paginationSlots(props.current, total);
});
</script>

<template>
    <div class="flex flex-wrap items-center justify-center gap-1 py-2">
        <button
            type="button"
            class="rounded-lg border border-sidebar-border/70 px-2.5 py-1.5 text-sm font-medium text-foreground hover:bg-muted/60 disabled:opacity-50 dark:border-sidebar-border"
            :disabled="current <= 1"
            @click="emit('go-to', current - 1)"
        >
            Previous
        </button>
        <template v-for="(slot, idx) in slots" :key="(slotKeyPrefix ?? '') + idx">
            <button
                v-if="slot !== 'ellipsis'"
                type="button"
                class="min-w-[2.25rem] rounded-lg border px-2.5 py-1.5 text-sm font-medium transition-colors"
                :class="current === slot ? 'border-primary bg-primary text-primary-foreground' : 'border-sidebar-border/70 text-foreground hover:bg-muted/60 dark:border-sidebar-border'"
                @click="emit('go-to', slot)"
            >
                {{ slot }}
            </button>
            <span v-else class="px-1 text-muted-foreground">…</span>
        </template>
        <button
            type="button"
            class="rounded-lg border border-sidebar-border/70 px-2.5 py-1.5 text-sm font-medium text-foreground hover:bg-muted/60 disabled:opacity-50 dark:border-sidebar-border"
            :disabled="current >= total"
            @click="emit('go-to', current + 1)"
        >
            Next
        </button>
    </div>
</template>
