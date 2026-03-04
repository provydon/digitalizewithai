<script setup lang="ts">
import NovaLogo from '@/components/NovaLogo.vue';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div
        class="flex min-h-svh flex-col items-center justify-center gap-6 bg-background p-6 md:p-10"
    >
        <div class="w-full max-w-sm">
            <div class="flex flex-col gap-8">
                <div class="flex flex-col items-center gap-5">
                    <NovaLogo variant="full" :show-text="true" :link-to-home="true" />
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
