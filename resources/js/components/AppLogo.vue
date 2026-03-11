<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { home } from '@/routes';

const page = usePage();
const branding = computed(() => (page.props.branding as { name: string }) ?? { name: 'Digitalize with AI' });
</script>

<template>
    <component :is="Link" :href="home()" class="flex flex-1 items-center gap-2 font-medium text-foreground no-underline">
        <AppLogoIcon class="h-8 w-8 shrink-0" />
        <span class="text-sm font-medium text-muted-foreground">{{ branding.name }}</span>
    </component>
</template>
