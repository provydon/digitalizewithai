<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import DataListSection from '@/components/data/DataListSection.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard() },
    { title: 'Data', href: '/data' },
];
</script>

<template>
    <Head title="Data" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="data-page flex min-h-full flex-1 flex-col overflow-x-auto">
            <header class="shrink-0 border-b border-border/80 bg-background/95 px-4 py-5 backdrop-blur sm:px-6">
                <h1 class="text-xl font-semibold tracking-tight text-foreground sm:text-2xl">
                    Data
                </h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    Search and manage everything you've added.
                </p>
            </header>

            <div class="flex flex-1 flex-col gap-6 p-4 sm:p-6">
                <DataListSection
                    mode="full"
                    :per-page="15"
                    view-base-path="/dashboard/data"
                    from-context="data"
                />
            </div>
        </div>
    </AppLayout>
</template>
