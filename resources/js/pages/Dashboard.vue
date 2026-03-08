<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import DataListSection from '@/components/data/DataListSection.vue';
import DigitalizeUploadSection from '@/components/data/DigitalizeUploadSection.vue';
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

function onItemCreated() {
    dataListRef.value?.loadList();
}

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
                    <li>Upload photos or video of notes, logs, or tables → saved docs or tables.</li>
                    <li><strong>Search</strong>, get <strong>metrics and charts</strong> from your data.</li>
                    <li>Edit, Chat with with data, and <strong>ask AI</strong> to do things with your data.</li>
                    <li>Export to Excel, PDF, or JSON.</li>
                </ul>
            </header>

            <div class="flex flex-1 flex-col gap-6 p-4 sm:p-6">
                <div class="flex justify-center">
                    <DigitalizeUploadSection
                        storage-key="dashboard_upload_seen"
                        @item-created="onItemCreated"
                        @uploaded="onUploaded"
                    />
                </div>

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
