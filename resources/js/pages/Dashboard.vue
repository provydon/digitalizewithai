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
                    <li>Upload a photo or video of <strong>physical</strong> notes, logs, or tables—each becomes a <strong>saved</strong> doc or table in your workspace.</li>
                    <li><strong>Search</strong> through your data and get <strong>metrics, totals, and charts</strong>—things that are hard to do with physical notes or handwritten logs.</li>
                    <li>Every item stays: edit it, build charts, and <strong>ask AI to change it</strong>; changes persist.</li>
                    <li>Export any item to Excel, PDF, or JSON with one click.</li>
                </ul>
            </header>

            <div class="flex flex-1 flex-col gap-6 p-4 sm:p-6">
                <div class="flex justify-center">
                    <DigitalizeUploadSection
                        storage-key="dashboard_upload_seen"
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
