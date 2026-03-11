<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { home } from '@/routes';

defineProps<{
    title?: string;
    description?: string;
}>();

const page = usePage();
const branding = computed(() => (page.props.branding as { name: string }) ?? { name: 'Digitalize with AI' });
</script>

<template>
    <div
        class="flex min-h-svh flex-col items-center justify-center gap-6 bg-background p-6 md:p-10"
    >
        <div class="w-full max-w-sm">
            <div class="flex flex-col gap-8">
                <div class="flex flex-col items-center gap-5">
                    <Link :href="home()" class="flex items-center gap-2 font-medium text-foreground no-underline">
                        <AppLogoIcon class="h-8 w-8 shrink-0" />
                        <span class="text-sm font-medium text-muted-foreground">{{ branding.name }}</span>
                    </Link>
                    <div class="nova-auth-card w-full rounded-2xl border border-border bg-card px-6 py-6 shadow-sm [&_a]:text-primary [&_a:hover]:underline">
                        <div class="space-y-2 text-center">
                            <h1 class="text-xl font-semibold text-foreground">{{ title }}</h1>
                            <p class="text-center text-sm text-muted-foreground">
                                {{ description }}
                            </p>
                        </div>
                        <div class="mt-6">
                            <slot />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
