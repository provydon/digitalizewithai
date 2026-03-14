<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

type Props = {
    className?: HTMLAttributes['class'];
};

defineProps<Props>();
</script>

<template>
    <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 40 42"
        :class="className"
        v-bind="$attrs"
    >
        <!-- Neural node: lines first, then circles on top -->
        <path
            stroke="#FFFFFF"
            stroke-width="2"
            stroke-linecap="round"
            fill="none"
            d="M20 16 L8 12 M20 16 L32 12 M20 16 L20 33"
        />
        <circle cx="20" cy="16" r="5" fill="#FFFFFF" />
        <circle cx="8" cy="12" r="3" fill="#FFFFFF" />
        <circle cx="32" cy="12" r="3" fill="#FFFFFF" />
        <circle cx="20" cy="33" r="3" fill="#FFFFFF" />
    </svg>
</template>
