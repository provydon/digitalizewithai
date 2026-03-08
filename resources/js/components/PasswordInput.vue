<script setup lang="ts">
import { useVModel } from '@vueuse/core';
import { useToggle } from '@vueuse/core';
import { Eye, EyeOff } from 'lucide-vue-next';
import type { HTMLAttributes } from 'vue';
import { ref } from 'vue';
import { cn } from '@/lib/utils';

const props = withDefaults(
    defineProps<{
        modelValue?: string | number;
        defaultValue?: string | number;
        class?: HTMLAttributes['class'];
        id?: string;
        name?: string;
        placeholder?: string;
        autocomplete?: string;
        required?: boolean;
        disabled?: boolean;
        tabindex?: number | string;
        autofocus?: boolean;
        readonly?: boolean;
    }>(),
    {
        placeholder: 'Password',
        autocomplete: 'current-password',
    },
);

const emit = defineEmits<{
    (e: 'update:modelValue', payload: string | number): void;
}>();

const modelValue = useVModel(props, 'modelValue', emit, {
    passive: true,
    defaultValue: props.defaultValue,
    eventName: 'update:modelValue',
});

const [isVisible, toggleVisible] = useToggle(false);
const inputEl = ref<HTMLInputElement | null>(null);

defineExpose({
    get $el() {
        return inputEl.value;
    },
    focus: () => inputEl.value?.focus(),
});
</script>

<template>
    <div :class="cn('relative', props.class)">
        <input
            ref="inputEl"
            :id="props.id"
            v-model="modelValue"
            :name="props.name"
            :required="props.required"
            :disabled="props.disabled"
            :tabindex="props.tabindex"
            :autocomplete="props.autocomplete"
            :autofocus="props.autofocus"
            :readonly="props.readonly"
            :type="isVisible ? 'text' : 'password'"
            :placeholder="props.placeholder"
            data-slot="input"
            :class="cn(
                'file:text-foreground placeholder:text-muted-foreground selection:bg-primary selection:text-primary-foreground dark:bg-input/30 border-input h-9 w-full min-w-0 rounded-md border bg-transparent py-1 text-base shadow-xs transition-[color,box-shadow] outline-none file:inline-flex file:h-7 file:border-0 file:bg-transparent file:text-sm file:font-medium disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 md:text-sm',
                'focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]',
                'aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive',
                'px-3 pr-10',
            )"
        />
        <button
            type="button"
            class="absolute right-2 top-1/2 -translate-y-1/2 rounded p-1 text-muted-foreground hover:text-foreground focus:outline-none focus-visible:ring-2 focus-visible:ring-ring"
            :aria-label="isVisible ? 'Hide password' : 'Show password'"
            tabindex="-1"
            @click="toggleVisible()"
        >
            <Eye v-if="!isVisible" class="h-4 w-4" />
            <EyeOff v-else class="h-4 w-4" />
        </button>
    </div>
</template>
