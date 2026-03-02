<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import api from '@/lib/api';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

type DigitalData = {
    type: string;
    content: string;
};

type DataRecord = {
    id: number;
    name: string;
    raw_data: Record<string, unknown> | null;
    digital_data: DigitalData | null;
    created_at: string | null;
    updated_at: string | null;
};

type Props = {
    id: number;
};

const props = defineProps<Props>();

const record = ref<DataRecord | null>(null);
const loading = ref(true);
const error = ref<string | null>(null);

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: 'Dashboard', href: dashboard() },
    { title: record.value?.name ?? '…', href: '#' },
]);

onMounted(async () => {
    try {
        const { data } = await api.get<DataRecord>(`/dashboard/api/data/${props.id}`);
        record.value = data;
    } catch (e: unknown) {
        const err = e as { response?: { data?: { message?: string } }; message?: string };
        error.value = err.response?.data?.message ?? err.message ?? 'Failed to load';
    } finally {
        loading.value = false;
    }
});

const tableData = computed(() => {
    const dd = record.value?.digital_data;
    if (!dd || dd.type !== 'table' || !dd.content) return null;
    try {
        return JSON.parse(dd.content) as { headers?: string[]; rows?: unknown[][] };
    } catch {
        return null;
    }
});

const docContent = computed(() => {
    const dd = record.value?.digital_data;
    if (!dd || dd.type !== 'doc') return null;
    return dd.content ?? '';
});
</script>

<template>
    <Head :title="record?.name ?? 'Data'" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <div class="flex items-center gap-4">
                <Link
                    :href="dashboard()"
                    class="text-sm text-muted-foreground underline-offset-4 hover:underline"
                >
                    ← Back to dashboard
                </Link>
            </div>

            <div class="rounded-xl border border-sidebar-border/70 bg-card p-6 shadow-sm dark:border-sidebar-border">
                <div v-if="loading" class="py-8 text-center text-muted-foreground">
                    Loading…
                </div>
                <div v-else-if="error" class="py-8 text-center text-destructive">
                    {{ error }}
                </div>
                <template v-else-if="record">
                    <h1 class="mb-2 text-xl font-semibold text-foreground">
                        {{ record.name }}
                    </h1>
                    <p
                        v-if="record.created_at"
                        class="mb-4 text-sm text-muted-foreground"
                    >
                        Created {{ new Date(record.created_at).toLocaleString() }}
                    </p>

                    <!-- Doc: plain/markdown text -->
                    <div
                        v-if="docContent !== null"
                        class="prose prose-sm max-w-none dark:prose-invert"
                    >
                        <pre
                            class="whitespace-pre-wrap rounded-lg bg-muted/50 p-4 font-sans text-foreground"
                        >{{ docContent }}</pre>
                    </div>

                    <!-- Table: parsed JSON -->
                    <div v-else-if="tableData" class="overflow-x-auto">
                        <table class="w-full min-w-[300px] border-collapse text-left text-sm">
                            <thead>
                                <tr class="border-b border-sidebar-border bg-muted/50 dark:border-sidebar-border">
                                    <th
                                        v-for="(h, i) in (tableData.headers ?? [])"
                                        :key="i"
                                        class="px-4 py-3 font-medium text-foreground"
                                    >
                                        {{ h }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="(row, ri) in (tableData.rows ?? [])"
                                    :key="ri"
                                    class="border-b border-sidebar-border/70 dark:border-sidebar-border"
                                >
                                    <td
                                        v-for="(cell, ci) in row"
                                        :key="ci"
                                        class="px-4 py-3 text-muted-foreground"
                                    >
                                        {{ cell == null ? '—' : cell }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <p
                        v-else
                        class="text-muted-foreground"
                    >
                        No content to display.
                    </p>
                </template>
            </div>
        </div>
    </AppLayout>
</template>
