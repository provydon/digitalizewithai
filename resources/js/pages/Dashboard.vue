<script setup lang="ts">
import { ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import DigitalizeUploadSection from '@/components/data/DigitalizeUploadSection.vue';
import DataListSection from '@/components/data/DataListSection.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
    },
];

const dataListRef = ref<InstanceType<typeof DataListSection> | null>(null);

function onUploaded() {
    dataListRef.value?.loadList();
}
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="dashboard-page flex min-h-full flex-1 flex-col overflow-x-auto">
            <header class="dashboard-header shrink-0 border-b border-border/80 bg-background/95 px-4 py-5 backdrop-blur sm:px-6">
                <ul class="list-inside list-disc space-y-2 text-sm font-semibold text-foreground sm:text-base">
                    <li>Add a photo or video of Data.</li>
                    <li>We’ll use AI to extract it into Digital items (text, tables, books, logs, Records, etc.).</li>
                    <li>Search, copy, edit, and export your digitalized items.</li>
                </ul>
            </header>

            <div class="flex flex-1 flex-col gap-6 p-4 sm:p-6">
                <DigitalizeUploadSection
                    storage-key="dashboard_upload_seen"
                    @uploaded="onUploaded"
                />

                <DataListSection
                    ref="dataListRef"
                    mode="preview"
                    :per-page="10"
                    see-more-href="/data"
                    view-base-path="/dashboard/data"
                />
            </div>
        </div>
    </AppLayout>
</template>
