<script setup lang="ts">
import { Monitor, Moon, Sun } from 'lucide-vue-next';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { useAppearance } from '@/composables/useAppearance';
import type { BreadcrumbItem } from '@/types';

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItem[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);

const { appearance, updateAppearance } = useAppearance();

const tabs = [
    { value: 'light', Icon: Sun, label: 'Light' },
    { value: 'dark', Icon: Moon, label: 'Dark' },
    { value: 'system', Icon: Monitor, label: 'System' },
] as const;
</script>

<template>
    <header
        class="flex h-14 shrink-0 items-center gap-2 border-b border-sidebar-border/70 px-4 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 sm:h-16 md:px-6"
    >
        <div class="flex flex-1 items-center gap-2">
            <SidebarTrigger class="-ml-1" />
            <template v-if="breadcrumbs && breadcrumbs.length > 0">
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </template>
        </div>
        <div
            class="inline-flex gap-0.5 rounded-lg bg-muted p-1 sm:gap-1"
        >
            <button
                v-for="{ value, Icon, label } in tabs"
                :key="value"
                type="button"
                :title="label"
                @click="updateAppearance(value)"
                :class="[
                    'flex items-center rounded-md transition-colors',
                    'p-2 sm:px-3.5 sm:py-1.5',
                    appearance === value
                        ? 'bg-card text-card-foreground shadow-xs'
                        : 'text-muted-foreground hover:bg-muted hover:text-foreground',
                ]"
            >
                <component :is="Icon" class="h-4 w-4 sm:-ml-1" />
                <span class="ml-1.5 hidden text-sm sm:inline">{{ label }}</span>
            </button>
        </div>
    </header>
</template>
