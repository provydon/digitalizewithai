<script setup lang="ts">
import {
    ArcElement,
    BarElement,
    CategoryScale,
    Chart as ChartJS,
    Filler,
    Legend,
    LinearScale,
    LineController,
    LineElement,
    PointElement,
    Title,
} from 'chart.js';
import { Head, Link } from '@inertiajs/vue3';
import {
    BarChart3,
    FileSpreadsheet,
    FileText,
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
import { Bar, Line, Pie } from 'vue-chartjs';
import * as XLSX from 'xlsx';
import AppLayout from '@/layouts/AppLayout.vue';
import api from '@/lib/api';
import { useAppearance } from '@/composables/useAppearance';
import { renderMarkdown } from '@/lib/markdown';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

ChartJS.register(
    CategoryScale,
    LinearScale,
    BarElement,
    LineController,
    LineElement,
    PointElement,
    Filler,
    ArcElement,
    Legend,
    Title,
);

type ChatMessage = { role: 'user' | 'assistant'; content: string };

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

const isTableData = computed(() => !!tableData.value);
const isDocData = computed(() => docContent.value !== null);

// —— Ask AI (table only) ——
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

// —— Export ——
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

function exportToDoc() {
    const content = docContent.value;
    if (content == null || !record.value) return;
    const blob = new Blob([content], { type: 'text/plain;charset=utf-8' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `${record.value.name || 'export'}.txt`;
    a.click();
    URL.revokeObjectURL(url);
}

const canExportDoc = computed(() => !!docContent.value && !!record.value);

// —— Charts (AI-suggested, table only) ——
const chartRequest = ref('');
const showSpecificChartRequest = ref(false);
type ChartSuggestion = {
    chartType: 'bar' | 'line' | 'pie';
    labelColumn: number;
    valueColumn: number;
    title: string | null;
};
const chartSuggestion = ref<ChartSuggestion | null>(null);
const chartSuggestionLoading = ref(false);

const isDarkChart = computed(() => resolvedAppearance.value === 'dark');

const chartColors = [
    'hsl(var(--chart-1))',
    'hsl(var(--chart-2))',
    'hsl(var(--chart-3))',
    'hsl(var(--chart-4))',
    'hsl(var(--chart-5))',
];
const chartColorsDark = [
    'hsla(220, 70%, 50%, 0.85)',
    'hsla(160, 60%, 45%, 0.85)',
    'hsla(30, 80%, 55%, 0.85)',
    'hsla(280, 65%, 60%, 0.85)',
    'hsla(340, 75%, 55%, 0.85)',
];

const effectiveChartConfig = computed(() => {
    const t = tableData.value;
    if (!t?.headers?.length) return null;
    const maxCol = t.headers.length - 1;
    const s = chartSuggestion.value;
    const labelCol = s ? Math.min(s.labelColumn, maxCol) : 0;
    const valueCol = s ? Math.min(s.valueColumn, maxCol) : Math.min(1, maxCol);
    const chartType = s?.chartType && ['bar', 'line', 'pie'].includes(s.chartType) ? s.chartType : 'bar';
    return { labelCol, valueCol, chartType, title: s?.title ?? null };
});

const chartOptions = computed(() => {
    const isDark = isDarkChart.value;
    const gridColor = isDark ? 'rgba(255,255,255,0.08)' : 'rgba(0,0,0,0.06)';
    const tickColor = isDark ? 'hsl(0 0% 63.9%)' : 'hsl(0 0% 45.1%)';
    const config = effectiveChartConfig.value;
    const isPie = config?.chartType === 'pie';
    return {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: isPie },
            title: config?.title ? { display: true, text: config.title, font: { size: 14 } } : undefined,
        },
        scales: isPie
            ? {}
            : {
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
    const config = effectiveChartConfig.value;
    if (!t?.headers?.length || !t.rows?.length || !config) return { labels: [], datasets: [] };
    const labels = (t.rows as unknown[][]).map((row) => String(row[config.labelCol] ?? ''));
    const values = (t.rows as unknown[][]).map((row) => {
        const v = row[config.valueCol];
        return typeof v === 'number' ? v : Number(v) || 0;
    });
    const colors = isDarkChart.value ? chartColorsDark : chartColors;
    const pieColors = config.chartType === 'pie'
        ? labels.map((_, i) => colors[i % colors.length])
        : colors[0];
    return {
        labels,
        datasets: [
            {
                label: (t.headers as string[])[config.valueCol] || 'Value',
                data: values,
                backgroundColor: pieColors,
                borderColor: config.chartType === 'line' ? colors[0] : undefined,
                fill: config.chartType === 'line',
                tension: 0.3,
            },
        ],
    };
});

async function suggestChartFromAi() {
    if (!record.value || !canChart.value) return;
    chartSuggestionLoading.value = true;
    try {
        const { data } = await api.post<ChartSuggestion>(
            `/dashboard/api/data/${record.value.id}/chart-suggestion`,
            { request: chartRequest.value.trim() || undefined },
        );
        chartSuggestion.value = {
            chartType: data.chartType === 'line' || data.chartType === 'pie' ? data.chartType : 'bar',
            labelColumn: data.labelColumn,
            valueColumn: data.valueColumn,
            title: data.title ?? null,
        };
    } catch {
        chartSuggestion.value = null;
    } finally {
        chartSuggestionLoading.value = false;
    }
}

const canChart = computed(
    () =>
        tableData.value &&
        (tableData.value.headers?.length ?? 0) >= 2 &&
        (tableData.value.rows?.length ?? 0) > 0,
);
const canExportExcel = computed(() => !!tableData.value && !!record.value);
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
                                value="ask"
                                class="inline-flex items-center justify-center gap-2 rounded-md px-3 py-1.5 text-sm font-medium ring-offset-background transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 data-[state=active]:bg-background data-[state=active]:text-foreground data-[state=active]:shadow-sm"
                            >
                                <MessageSquare class="h-4 w-4" />
                                Ask AI
                            </TabsTrigger>
                            <TabsTrigger
                                v-if="isTableData"
                                value="charts"
                                class="inline-flex items-center justify-center gap-2 rounded-md px-3 py-1.5 text-sm font-medium ring-offset-background transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 data-[state=active]:bg-background data-[state=active]:text-foreground data-[state=active]:shadow-sm"
                            >
                                <BarChart3 class="h-4 w-4" />
                                Charts
                            </TabsTrigger>
                            <TabsTrigger
                                value="export"
                                class="inline-flex items-center justify-center gap-2 rounded-md px-3 py-1.5 text-sm font-medium ring-offset-background transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 data-[state=active]:bg-background data-[state=active]:text-foreground data-[state=active]:shadow-sm"
                            >
                                <FileSpreadsheet class="h-4 w-4" />
                                Export
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

                        <!-- Ask AI: ChatGPT-style (chat up, input down), table only -->
                        <TabsContent value="ask" class="mt-0 rounded-b-xl">
                            <div class="flex min-h-[420px] flex-col p-4">
                                <div
                                    ref="chatScrollRef"
                                    class="flex flex-1 flex-col gap-3 overflow-y-auto rounded-lg py-2"
                                >
                                    <template v-if="messages.length === 0">
                                        <p class="px-2 py-8 text-center text-sm text-muted-foreground">
                                            Ask anything about this data. Type below and press Enter or click Send.
                                        </p>
                                    </template>
                                    <template v-else>
                                        <div
                                            v-for="(msg, i) in messages"
                                            :key="i"
                                            class="px-2"
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
                                <div class="mt-3 shrink-0 space-y-1">
                                    <div class="flex gap-2">
                                        <textarea
                                            v-model="question"
                                            class="min-h-[44px] w-full flex-1 rounded-lg border border-sidebar-border/70 bg-background px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring dark:border-sidebar-border"
                                            placeholder="Ask about this data..."
                                            rows="1"
                                            @keydown.enter.exact.prevent="askAi()"
                                        />
                                        <button
                                            type="button"
                                            class="h-fit self-end rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 disabled:opacity-50"
                                            :disabled="askLoading || !question.trim()"
                                            @click="askAi()"
                                        >
                                            {{ askLoading ? '…' : 'Send' }}
                                        </button>
                                    </div>
                                    <p v-if="askError" class="text-sm text-destructive">
                                        {{ askError }}
                                    </p>
                                </div>
                            </div>
                        </TabsContent>

                        <!-- Charts: dynamic chart builder, table only -->
                        <TabsContent value="charts" class="mt-0 rounded-b-xl">
                            <div class="flex flex-col gap-4 p-6 pt-4">
                                <div class="flex flex-wrap items-center gap-2">
                                    <button
                                        type="button"
                                        class="inline-flex items-center gap-2 rounded-lg border border-sidebar-border/70 px-3 py-2 text-sm font-medium text-foreground hover:bg-muted/60 dark:border-sidebar-border"
                                        :class="showSpecificChartRequest ? 'bg-muted/50' : 'bg-transparent'"
                                        @click="showSpecificChartRequest = !showSpecificChartRequest"
                                    >
                                        Ask for Specific Chart
                                    </button>
                                    <button
                                        type="button"
                                        class="inline-flex w-fit items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 disabled:opacity-50"
                                        :disabled="!canChart || chartSuggestionLoading"
                                        @click="suggestChartFromAi"
                                    >
                                        <BarChart3 class="h-4 w-4" />
                                        {{ chartSuggestionLoading ? '…' : chartSuggestion ? 'Generate Another Chart' : 'Generate Chart' }}
                                    </button>
                                </div>
                                <div
                                    v-show="showSpecificChartRequest"
                                    class="flex flex-wrap items-end gap-2"
                                >
                                    <input
                                        v-model="chartRequest"
                                        type="text"
                                        class="w-full min-w-[200px] max-w-md rounded-lg border border-sidebar-border/70 bg-background px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring dark:border-sidebar-border md:max-w-sm"
                                        placeholder="e.g. bar chart of sales by region, pie chart of market share"
                                        @keydown.enter.prevent="suggestChartFromAi()"
                                    />
                                    <button
                                        type="button"
                                        class="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 disabled:opacity-50"
                                        :disabled="!canChart || chartSuggestionLoading"
                                        @click="suggestChartFromAi"
                                    >
                                        Send
                                    </button>
                                </div>
                                <div v-if="!chartSuggestion" class="rounded-lg border border-dashed border-sidebar-border/70 py-12 text-center text-sm text-muted-foreground dark:border-sidebar-border">
                                    Click “Generate Chart” to have AI pick the best chart type and columns. Use “Ask for Specific Chart” to describe the chart you want.
                                </div>
                                <div v-else class="space-y-2">
                                    <p
                                        v-if="effectiveChartConfig?.title"
                                        class="text-sm font-medium text-foreground"
                                    >
                                        {{ effectiveChartConfig.title }}
                                    </p>
                                    <div class="h-[280px] w-full">
                                        <Bar
                                            v-if="effectiveChartConfig?.chartType === 'bar'"
                                            :data="chartData"
                                            :options="chartOptions"
                                        />
                                        <Line
                                            v-else-if="effectiveChartConfig?.chartType === 'line'"
                                            :data="chartData"
                                            :options="chartOptions"
                                        />
                                        <Pie
                                            v-else-if="effectiveChartConfig?.chartType === 'pie'"
                                            :data="chartData"
                                            :options="chartOptions"
                                        />
                                    </div>
                                </div>
                            </div>
                        </TabsContent>

                        <TabsContent value="export" class="mt-0 rounded-b-xl">
                            <div class="flex flex-col gap-4 p-6 pt-4">
                                <p class="text-sm text-muted-foreground">
                                    Download this data to your device.
                                </p>
                                <div class="flex flex-wrap gap-3">
                                    <button
                                        v-if="isTableData"
                                        type="button"
                                        class="inline-flex items-center gap-2 rounded-lg border border-sidebar-border/70 bg-muted/30 px-3 py-2 text-sm font-medium text-foreground hover:bg-muted/60 dark:border-sidebar-border disabled:opacity-50"
                                        :disabled="!canExportExcel"
                                        @click="exportToExcel"
                                    >
                                        <FileSpreadsheet class="h-4 w-4" />
                                        Export to Excel
                                    </button>
                                    <button
                                        v-if="isDocData"
                                        type="button"
                                        class="inline-flex items-center gap-2 rounded-lg border border-sidebar-border/70 bg-muted/30 px-3 py-2 text-sm font-medium text-foreground hover:bg-muted/60 dark:border-sidebar-border disabled:opacity-50"
                                        :disabled="!canExportDoc"
                                        @click="exportToDoc"
                                    >
                                        <FileText class="h-4 w-4" />
                                        Export to Doc
                                    </button>
                                </div>
                                <p
                                    v-if="!isTableData && !isDocData"
                                    class="text-sm text-muted-foreground"
                                >
                                    No content to export.
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
