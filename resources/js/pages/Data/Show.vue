<script setup lang="ts">
import {
    BarElement,
    CategoryScale,
    Chart as ChartJS,
    LinearScale,
} from 'chart.js';
import { Head, Link } from '@inertiajs/vue3';
import {
    BarChart3,
    FileSpreadsheet,
    Lightbulb,
    MessageSquare,
    Table as TableIcon,
} from 'lucide-vue-next';
import {
    TabsContent,
    TabsList,
    TabsRoot,
    TabsTrigger,
} from 'reka-ui';
import { computed, nextTick, onMounted, ref } from 'vue';
import { Bar } from 'vue-chartjs';
import * as XLSX from 'xlsx';
import AppLayout from '@/layouts/AppLayout.vue';
import api from '@/lib/api';
import { useAppearance } from '@/composables/useAppearance';
import { renderMarkdown } from '@/lib/markdown';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

type ChatMessage = { role: 'user' | 'assistant'; content: string };

ChartJS.register(CategoryScale, LinearScale, BarElement);

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
const activeTab = ref('data');

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

// —— Explore with AI ——
const question = ref('');
const messages = ref<ChatMessage[]>([]);
const chatScrollRef = ref<HTMLElement | null>(null);
const askLoading = ref(false);
const askError = ref<string | null>(null);

const { resolvedAppearance } = useAppearance();

function getCsrfToken(): string {
    const meta = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (meta) return meta;
    const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
    return match ? decodeURIComponent(match[1]) : '';
}

async function askAi(prompt?: string) {
    const q = prompt ?? question.value.trim();
    if (!q || !record.value) return;
    askLoading.value = true;
    askError.value = null;
    messages.value.push({ role: 'user', content: q });
    question.value = '';
    const assistantIndex = messages.value.length;
    messages.value.push({ role: 'assistant', content: '' });

    try {
        const streamUrl = `/dashboard/api/data/${record.value.id}/ask/stream`;
        const res = await fetch(streamUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'text/event-stream',
                'X-XSRF-TOKEN': getCsrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            body: JSON.stringify({ question: q }),
        });
        if (!res.ok) {
            const errData = await res.json().catch(() => ({}));
            throw new Error(errData.message || `Request failed: ${res.status}`);
        }
        const reader = res.body?.getReader();
        const decoder = new TextDecoder();
        if (!reader) {
            throw new Error('No response body');
        }
        let buffer = '';
        while (true) {
            const { done, value } = await reader.read();
            if (done) break;
            buffer += decoder.decode(value, { stream: true });
            const lines = buffer.split('\n');
            buffer = lines.pop() ?? '';
            for (const line of lines) {
                if (line.startsWith('data: ')) {
                    const data = line.slice(6).trim();
                    if (data === '[DONE]' || data === '') continue;
                    try {
                        const parsed = JSON.parse(data) as { content?: string; delta?: string; text?: string };
                        const chunk =
                            parsed.content ?? parsed.delta ?? parsed.text ?? (typeof parsed === 'string' ? parsed : '');
                        if (chunk && typeof chunk === 'string') {
                            const last = messages.value[assistantIndex];
                            if (last && last.role === 'assistant') last.content += chunk;
                        }
                    } catch {
                        if (data && data !== '[DONE]') {
                            const last = messages.value[assistantIndex];
                            if (last && last.role === 'assistant') last.content += data;
                        }
                    }
                }
            }
            await nextTick();
            chatScrollRef.value?.scrollTo({ top: chatScrollRef.value.scrollHeight, behavior: 'smooth' });
        }
        if (buffer.startsWith('data: ')) {
            const data = buffer.slice(6).trim();
            if (data && data !== '[DONE]') {
                try {
                    const parsed = JSON.parse(data) as { content?: string; delta?: string; text?: string };
                    const chunk =
                        parsed.content ?? parsed.delta ?? parsed.text ?? (typeof parsed === 'string' ? parsed : '');
                    if (chunk && typeof chunk === 'string') {
                        const last = messages.value[assistantIndex];
                        if (last && last.role === 'assistant') last.content += chunk;
                    }
                } catch {
                    const last = messages.value[assistantIndex];
                    if (last && last.role === 'assistant') last.content += data;
                }
            }
        }
    } catch (e: unknown) {
        const err = e as Error;
        askError.value = err.message ?? 'Request failed';
        messages.value.pop();
        const last = messages.value[messages.value.length - 1];
        if (last?.role === 'assistant' && last.content === '') messages.value.pop();
    } finally {
        askLoading.value = false;
        await nextTick();
        chatScrollRef.value?.scrollTo({ top: chatScrollRef.value.scrollHeight, behavior: 'smooth' });
    }
}

function getInsights() {
    question.value = 'Summarize this data and give me key insights, patterns, or notable findings.';
    askAi(question.value);
}

function suggestChart() {
    question.value = 'What chart would best visualize this data? Suggest chart type and which columns to use.';
    askAi(question.value);
}

// —— Export to Excel ——
function exportToExcel() {
    const t = tableData.value;
    if (!t?.headers?.length || !record.value) return;
    const rows = (t.rows ?? []).map((row: unknown[]) =>
        (t.headers as string[]).reduce(
            (acc, h, i) => ({ ...acc, [h]: row[i] ?? '' }),
            {} as Record<string, unknown>,
        ),
    );
    const ws = XLSX.utils.json_to_sheet(rows);
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, record.value.name.slice(0, 31) || 'Data');
    XLSX.writeFile(wb, `${record.value.name || 'export'}.xlsx`);
}

// —— Chart (bar from table) ——
const chartLabelCol = ref(0);
const chartValueCol = ref(1);

const isDarkChart = computed(() => resolvedAppearance.value === 'dark');

const chartOptions = computed(() => {
    const isDark = isDarkChart.value;
    const gridColor = isDark ? 'rgba(255,255,255,0.08)' : 'rgba(0,0,0,0.06)';
    const tickColor = isDark ? 'hsl(0 0% 63.9%)' : 'hsl(0 0% 45.1%)';
    return {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
        },
        scales: {
            x: {
                grid: { color: gridColor },
                ticks: { color: tickColor, font: { size: 11 } },
            },
            y: {
                beginAtZero: true,
                grid: { color: gridColor },
                ticks: { color: tickColor, font: { size: 11 } },
            },
        },
    };
});

const chartData = computed(() => {
    const t = tableData.value;
    if (!t?.headers?.length || !t.rows?.length) return { labels: [], datasets: [] };
    const labels = (t.rows as unknown[][]).map((row) => String(row[chartLabelCol.value] ?? ''));
    const values = (t.rows as unknown[][]).map((row) => {
        const v = row[chartValueCol.value];
        return typeof v === 'number' ? v : Number(v) || 0;
    });
    return {
        labels,
        datasets: [
            {
                label: (t.headers as string[])[chartValueCol.value] || 'Value',
                data: values,
                backgroundColor: isDarkChart.value
                    ? 'hsla(220, 70%, 50%, 0.85)'
                    : 'hsl(var(--chart-1))',
            },
        ],
    };
});

const canChart = computed(
    () =>
        tableData.value &&
        (tableData.value.headers?.length ?? 0) >= 2 &&
        (tableData.value.rows?.length ?? 0) > 0,
);
const canExport = computed(() => !!tableData.value && !!record.value);
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

            <div
                v-if="!loading && !error && record"
                class="rounded-xl border border-sidebar-border/70 bg-card shadow-sm dark:border-sidebar-border"
            >
                <div class="border-b border-sidebar-border/70 px-4 pt-4 dark:border-sidebar-border">
                    <h1 class="mb-2 text-xl font-semibold text-foreground">
                        {{ record.name }}
                    </h1>
                    <p
                        v-if="record.created_at"
                        class="mb-4 text-sm text-muted-foreground"
                    >
                        Created {{ new Date(record.created_at).toLocaleString() }}
                    </p>

                    <TabsRoot v-model="activeTab" class="w-full">
                        <TabsList
                            class="inline-flex h-10 w-full items-center justify-start gap-1 rounded-lg bg-muted/50 p-1 text-muted-foreground"
                            aria-label="Data view tabs"
                        >
                            <TabsTrigger
                                value="data"
                                class="inline-flex items-center justify-center gap-2 rounded-md px-3 py-1.5 text-sm font-medium ring-offset-background transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 data-[state=active]:bg-background data-[state=active]:text-foreground data-[state=active]:shadow-sm"
                            >
                                <TableIcon class="h-4 w-4" />
                                Data
                            </TabsTrigger>
                            <TabsTrigger
                                value="explore"
                                class="inline-flex items-center justify-center gap-2 rounded-md px-3 py-1.5 text-sm font-medium ring-offset-background transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 data-[state=active]:bg-background data-[state=active]:text-foreground data-[state=active]:shadow-sm"
                            >
                                <MessageSquare class="h-4 w-4" />
                                Explore with AI
                            </TabsTrigger>
                            <TabsTrigger
                                value="export"
                                class="inline-flex items-center justify-center gap-2 rounded-md px-3 py-1.5 text-sm font-medium ring-offset-background transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 data-[state=active]:bg-background data-[state=active]:text-foreground data-[state=active]:shadow-sm"
                            >
                                <FileSpreadsheet class="h-4 w-4" />
                                Export to Excel
                            </TabsTrigger>
                        </TabsList>

                        <TabsContent value="data" class="mt-0 rounded-b-xl">
                            <div class="p-6 pt-4">
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
                                    <table
                                        class="w-full min-w-[300px] border-collapse text-left text-sm"
                                    >
                                        <thead>
                                            <tr
                                                class="border-b border-sidebar-border bg-muted/50 dark:border-sidebar-border"
                                            >
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

                                <p v-else class="text-muted-foreground">
                                    No content to display.
                                </p>
                            </div>
                        </TabsContent>

                        <TabsContent value="explore" class="mt-0 rounded-b-xl">
                            <div class="flex flex-col gap-4 p-6 pt-4">
                                <!-- Ask AI input (fixed at top) -->
                                <div class="space-y-2">
                                    <div class="flex flex-wrap gap-2">
                                        <button
                                            type="button"
                                            class="inline-flex items-center gap-1.5 rounded-md border border-sidebar-border/70 bg-muted/30 px-3 py-1.5 text-sm text-foreground hover:bg-muted/60 dark:border-sidebar-border"
                                            :disabled="askLoading"
                                            @click="getInsights"
                                        >
                                            <Lightbulb class="h-3.5 w-3.5" />
                                            Get insights
                                        </button>
                                        <button
                                            type="button"
                                            class="inline-flex items-center gap-1.5 rounded-md border border-sidebar-border/70 bg-muted/30 px-3 py-1.5 text-sm text-foreground hover:bg-muted/60 dark:border-sidebar-border"
                                            :disabled="askLoading || !canChart"
                                            @click="suggestChart"
                                        >
                                            <BarChart3 class="h-3.5 w-3.5" />
                                            Suggest a chart
                                        </button>
                                    </div>
                                    <div class="flex gap-2">
                                        <textarea
                                            v-model="question"
                                            class="min-h-[72px] w-full flex-1 rounded-lg border border-sidebar-border/70 bg-background px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring dark:border-sidebar-border"
                                            placeholder="e.g. What are the top 3 values? Summarize trends..."
                                            rows="2"
                                            @keydown.ctrl.enter="askAi()"
                                        />
                                        <button
                                            type="button"
                                            class="h-fit rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 disabled:opacity-50"
                                            :disabled="askLoading || !question.trim()"
                                            @click="askAi()"
                                        >
                                            {{ askLoading ? '…' : 'Ask' }}
                                        </button>
                                    </div>
                                    <p v-if="askError" class="text-sm text-destructive">
                                        {{ askError }}
                                    </p>
                                </div>

                                <!-- Chat messages: fixed height, scrollable, does not push chart -->
                                <div
                                    ref="chatScrollRef"
                                    class="flex min-h-[200px] max-h-[260px] flex-col gap-3 overflow-y-auto rounded-lg border border-sidebar-border/70 bg-muted/20 py-2 dark:border-sidebar-border"
                                >
                                    <template v-if="messages.length === 0">
                                        <p class="px-4 py-6 text-center text-sm text-muted-foreground">
                                            Ask a question above. Replies appear here and won’t push the chart down.
                                        </p>
                                    </template>
                                    <template v-else>
                                        <div
                                            v-for="(msg, i) in messages"
                                            :key="i"
                                            class="px-4"
                                            :class="msg.role === 'user' ? 'text-right' : ''"
                                        >
                                            <span
                                                class="inline-block max-w-[85%] rounded-lg px-3 py-2 text-left text-sm"
                                                :class="
                                                    msg.role === 'user'
                                                        ? 'bg-primary text-primary-foreground'
                                                        : 'bg-muted/60 text-foreground dark:bg-muted/50'
                                                "
                                            >
                                                <template v-if="msg.role === 'user'">
                                                    <span class="whitespace-pre-wrap">{{ msg.content }}</span>
                                                </template>
                                                <template v-else>
                                                    <div
                                                        v-if="!msg.content && askLoading"
                                                        class="flex items-center gap-1 text-muted-foreground"
                                                    >
                                                        <span class="inline-block size-1.5 animate-pulse rounded-full bg-current" />
                                                        <span class="inline-block size-1.5 animate-pulse rounded-full bg-current" style="animation-delay: 0.2s" />
                                                        <span class="inline-block size-1.5 animate-pulse rounded-full bg-current" style="animation-delay: 0.4s" />
                                                    </div>
                                                    <div
                                                        v-else
                                                        class="prose prose-sm max-w-none dark:prose-invert prose-p:my-1 prose-ul:my-1 prose-ol:my-1 prose-li:my-0 prose-headings:my-2 first:prose-p:mt-0 last:prose-p:mb-0"
                                                        v-html="renderMarkdown(msg.content)"
                                                    />
                                                </template>
                                            </span>
                                        </div>
                                    </template>
                                </div>

                                <!-- Chart (table only) – stays below chat -->
                                <div v-if="canChart" class="space-y-3">
                                    <div class="flex items-center gap-2">
                                        <BarChart3 class="h-4 w-4 text-foreground" />
                                        <span class="text-sm font-medium text-foreground">
                                            Bar chart
                                        </span>
                                    </div>
                                    <div class="flex flex-wrap gap-4">
                                        <div class="flex items-center gap-2">
                                            <label class="text-xs text-muted-foreground">Labels</label>
                                            <select
                                                v-model.number="chartLabelCol"
                                                class="rounded border border-sidebar-border/70 bg-background px-2 py-1 text-sm dark:border-sidebar-border"
                                            >
                                                <option
                                                    v-for="(h, i) in (tableData?.headers ?? [])"
                                                    :key="i"
                                                    :value="i"
                                                >
                                                    {{ h }}
                                                </option>
                                            </select>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <label class="text-xs text-muted-foreground">Values</label>
                                            <select
                                                v-model.number="chartValueCol"
                                                class="rounded border border-sidebar-border/70 bg-background px-2 py-1 text-sm dark:border-sidebar-border"
                                            >
                                                <option
                                                    v-for="(h, i) in (tableData?.headers ?? [])"
                                                    :key="i"
                                                    :value="i"
                                                >
                                                    {{ h }}
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="h-[280px] w-full">
                                        <Bar :data="chartData" :options="chartOptions" />
                                    </div>
                                </div>
                            </div>
                        </TabsContent>

                        <TabsContent value="export" class="mt-0 rounded-b-xl">
                            <div class="flex flex-col gap-4 p-6 pt-4">
                                <p class="text-sm text-muted-foreground">
                                    Download this data as an Excel (.xlsx) file. Available for table data only.
                                </p>
                                <button
                                    type="button"
                                    class="inline-flex w-fit items-center gap-2 rounded-lg border border-sidebar-border/70 bg-muted/30 px-3 py-2 text-sm font-medium text-foreground hover:bg-muted/60 dark:border-sidebar-border disabled:opacity-50"
                                    :disabled="!canExport"
                                    @click="exportToExcel"
                                >
                                    <FileSpreadsheet class="h-4 w-4" />
                                    Export to Excel
                                </button>
                                <p
                                    v-if="!canExport"
                                    class="text-sm text-muted-foreground"
                                >
                                    No table data to export. Upload or digitalize a table to use this.
                                </p>
                            </div>
                        </TabsContent>
                    </TabsRoot>
                </div>
            </div>

            <div
                v-else
                class="rounded-xl border border-sidebar-border/70 bg-card p-6 shadow-sm dark:border-sidebar-border"
            >
                <div v-if="loading" class="py-8 text-center text-muted-foreground">
                    Loading…
                </div>
                <div v-else-if="error" class="py-8 text-center text-destructive">
                    {{ error }}
                </div>
            </div>
        </div>
    </AppLayout>
</template>
