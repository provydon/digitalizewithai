<script setup lang="ts">
withDefaults(
    defineProps<{
        /** When true, bars animate (playing). When false, bars are static (paused). */
        paused?: boolean;
    }>(),
    { paused: false },
);
</script>

<template>
    <span
        class="read-aloud-playing-icon inline-flex items-end gap-0.5"
        role="img"
        aria-label="Audio playing"
    >
        <span
            v-for="i in 4"
            :key="i"
            class="bar"
            :class="{ 'bar--paused': paused }"
    />
    </span>
</template>

<style scoped>
.read-aloud-playing-icon {
    --bar-width: 3px;
    --bar-min-height: 4px;
    --bar-max-height: 12px;
    height: var(--bar-max-height);
}

.bar {
    width: var(--bar-width);
    min-width: var(--bar-width);
    height: var(--bar-max-height);
    min-height: var(--bar-min-height);
    background: currentColor;
    border-radius: 1px;
    transform-origin: bottom;
    animation: read-aloud-bar 0.6s ease-in-out infinite both;
}

.bar:nth-child(1) { animation-delay: 0ms; }
.bar:nth-child(2) { animation-delay: 120ms; }
.bar:nth-child(3) { animation-delay: 240ms; }
.bar:nth-child(4) { animation-delay: 360ms; }

.bar--paused {
    animation: none;
    height: 6px;
    opacity: 0.7;
}

@keyframes read-aloud-bar {
    0%, 100% { transform: scaleY(0.35); }
    50% { transform: scaleY(1); }
}
</style>
