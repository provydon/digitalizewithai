<script setup lang="ts">
import {
    ArcElement,
    BarController,
    BarElement,
    CategoryScale,
    Chart as ChartJS,
    Filler,
    Legend,
    LinearScale,
    LineController,
    LineElement,
    PieController,
    PointElement,
    Title,
    Tooltip,
} from 'chart.js';
import { BarChart3, Bookmark, ChevronDown, ChevronRight, Loader2, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { Chart } from 'vue-chartjs';
import { useAppearance } from '@/composables/useAppearance';
import type { ChartSuggestion, SavedChart } from '../types';

ChartJS.register(
    CategoryScale,
    LinearScale,
    BarController,
    BarElement,
    LineController,
    LineElement,
    PointElement,
    Filler,
    PieController,
    ArcElement,
    Legend,
    Title,
    Tooltip,
);

const props = defineProps<{
    tableData: { headers?: string[]; rows?: unknown[][] } | null;
    aiModelLabel: string;
    chartSuggestion: ChartSuggestion | null;
    chartSuggestionError: string | null;
    chartRequest: string;
    chartSuggestions: string[];
    showSpecificChartRequest: boolean;
    chartSuggestionLoading: boolean;
    canChart: boolean;
    savedCharts: SavedChart[];
    saveChartLoading: boolean;
    savedChartError: string | null;
    canSaveChart: boolean;
}>();

const emit = defineEmits<{
    'update:chartRequest': [v: string];
    'update:showSpecificChartRequest': [v: boolean];
    'suggest-chart': [];
    'apply-chart-suggestion': [text: string];
    'save-chart': [];
    'load-chart': [chart: SavedChart];
    'delete-chart': [chart: SavedChart];
    'new-chart': [];
}>();

const savedChartsOpen = ref(false);

function onChartRequestInput(e: Event) {
    emit('update:chartRequest', (e.target as HTMLInputElement).value);
}

const { resolvedAppearance } = useAppearance();
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
    const t = props.tableData;
    if (!t?.headers?.length) return null;
    const maxCol = t.headers.length - 1;
    const s = props.chartSuggestion;
    const labelCol = s ? Math.min(s.labelColumn, maxCol) : 0;
    const valueCol = s ? Math.min(s.valueColumn, maxCol) : Math.min(1, maxCol);
    const chartType = s?.chartType && ['bar', 'line', 'pie'].includes(s.chartType) ? s.chartType : 'bar';
    return {
        labelCol,
        valueCol,
        chartType,
        aggregation: s?.aggregation ?? 'none',
        title: s?.title ?? null,
        xAxisName: s?.xAxisName?.trim() || null,
        yAxisName: s?.yAxisName?.trim() || null,
    };
});

const axisTitles = computed(() => {
    const t = props.tableData;
    const config = effectiveChartConfig.value;
    if (!t?.headers?.length || !config) {
        return { x: 'Category', y: 'Value' };
    }
    return {
        x: config.xAxisName || t.headers[config.labelCol] || 'Category',
        y: config.yAxisName || t.headers[config.valueCol] || 'Value',
    };
});

function getThemeColor(cssVar: string): string {
    if (typeof document === 'undefined') return 'hsl(0 0% 50%)';
    const raw = getComputedStyle(document.documentElement).getPropertyValue(cssVar).trim();
    return raw.startsWith('hsl') ? raw : `hsl(${raw})`;
}

function normalizeLabel(value: unknown): string {
    const label = String(value ?? '').trim();
    return label || 'Unknown';
}

function resolveGroupedLabel(row: unknown[], labelCol: number, previousLabel: string | null): string {
    const current = String(row[labelCol] ?? '').trim();
    if (current) return current;
    return previousLabel ?? 'Unknown';
}

function parseNumericValue(value: unknown): number | null {
    if (typeof value === 'number' && Number.isFinite(value)) return value;
    const raw = String(value ?? '').trim();
    if (!raw) return null;
    const match = raw.match(/-?\d[\d,]*(?:\.\d+)?/);
    if (!match) return null;
    const normalized = match[0].replace(/,/g, '');
    const parsed = Number(normalized);
    return Number.isFinite(parsed) ? parsed : null;
}

function formatChartValue(value: number): string {
    return new Intl.NumberFormat().format(value);
}

const chartOptions = computed(() => {
    const isDark = isDarkChart.value;
    const gridColor = isDark ? 'rgba(255,255,255,0.08)' : 'rgba(0,0,0,0.06)';
    const tickColor = getThemeColor('--muted-foreground');
    const config = effectiveChartConfig.value;
    const isPie = config?.chartType === 'pie';
    return {
        responsive: true,
        maintainAspectRatio: false,
        interaction: {
            mode: 'nearest',
            intersect: true,
        },
        plugins: {
            legend: { display: isPie },
            title: config?.title ? { display: true, text: config.title, font: { size: 14 } } : undefined,
            tooltip: {
                enabled: true,
                callbacks: {
                    label(context) {
                        const label = context.dataset.label ? `${context.dataset.label}: ` : '';
                        const value = typeof context.parsed === 'number'
                            ? context.parsed
                            : (typeof context.parsed?.y === 'number' ? context.parsed.y : context.raw);
                        const numericValue = typeof value === 'number' ? value : Number(value) || 0;
                        return `${label}${formatChartValue(numericValue)}`;
                    },
                },
            },
        },
        scales: isPie
            ? {}
            : {
                x: {
                    grid: { color: gridColor },
                    ticks: { color: tickColor, font: { size: 11 } },
                    title: {
                        display: true,
                        text: axisTitles.value.x,
                        color: tickColor,
                        font: { size: 12, weight: '600' },
                    },
                },
                y: {
                    beginAtZero: true,
                    grid: { color: gridColor },
                    ticks: { color: tickColor, font: { size: 11 } },
                    title: {
                        display: true,
                        text: axisTitles.value.y,
                        color: tickColor,
                        font: { size: 12, weight: '600' },
                    },
                },
            },
    };
});

const chartData = computed(() => {
    const t = props.tableData;
    const config = effectiveChartConfig.value;
    if (!t?.headers?.length || !t.rows?.length || !config) return { labels: [], datasets: [] };
    let labels: string[] = [];
    let values: number[] = [];
    if (config.aggregation === 'sum' || config.aggregation === 'count') {
        const grouped = new Map<string, number>();
        let previousLabel: string | null = null;
        for (const row of t.rows as unknown[][]) {
            const label = resolveGroupedLabel(row, config.labelCol, previousLabel);
            previousLabel = label;
            if (config.aggregation === 'count') {
                grouped.set(label, (grouped.get(label) ?? 0) + 1);
                continue;
            }
            const numeric = parseNumericValue(row[config.valueCol]);
            if (numeric === null) continue;
            grouped.set(label, (grouped.get(label) ?? 0) + numeric);
        }
        labels = Array.from(grouped.keys());
        values = Array.from(grouped.values());
    } else {
        labels = (t.rows as unknown[][]).map((row) => normalizeLabel(row[config.labelCol]));
        values = (t.rows as unknown[][]).map((row) => parseNumericValue(row[config.valueCol]) ?? 0);
    }
    const colors = isDarkChart.value ? chartColorsDark : chartColors;
    const pieColors = config.chartType === 'pie'
        ? labels.map((_, i) => colors[i % colors.length])
        : colors[0];
    return {
        labels,
        datasets: [
            {
                label: axisTitles.value.y,
                data: values,
                backgroundColor: pieColors,
                borderColor: config.chartType === 'line' ? colors[0] : undefined,
                fill: config.chartType === 'line',
                tension: 0.3,
            },
        ],
    };
});

function chartTitle(chart: SavedChart): string {
    const name = chart.name?.trim();
    if (name) return name;
    const t = chart.chart_config?.title?.trim();
    if (t) return t;
    const type = chart.chart_config?.chartType ?? 'bar';
    return `${type.charAt(0).toUpperCase() + type.slice(1)} chart`;
}
</script>

<template>
    <div class="flex flex-col gap-4 p-3 pt-4 sm:p-6">
        <p
            v-if="aiModelLabel"
            class="text-xs text-muted-foreground"
            title="Same model used when this data was extracted"
        >
            Using: {{ aiModelLabel }}
        </p>
        <div class="flex flex-wrap items-center gap-2">
            <button
                type="button"
                class="inline-flex min-h-[44px] cursor-pointer items-center gap-2 rounded-lg border-2 border-input px-3 py-2 text-sm font-medium text-foreground transition-colors hover:bg-muted/60 hover:border-primary/50 sm:min-h-0"
                :class="showSpecificChartRequest ? 'bg-muted/50' : 'bg-transparent'"
                @click="emit('update:showSpecificChartRequest', !showSpecificChartRequest)"
            >
                Ask for Specific Chart
            </button>
            <button
                type="button"
                class="inline-flex min-h-[44px] w-full items-center justify-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 disabled:opacity-50 sm:w-fit"
                :disabled="!canChart || chartSuggestionLoading"
                @click="emit('suggest-chart')"
            >
                <Loader2 v-if="chartSuggestionLoading" class="h-4 w-4 animate-spin" />
                <BarChart3 v-else class="h-4 w-4" />
                {{ chartSuggestionLoading ? 'Generating…' : chartSuggestion ? 'Generate Another Chart' : 'Generate Chart' }}
            </button>
            <button
                v-if="canSaveChart"
                type="button"
                class="inline-flex min-h-[44px] items-center gap-1.5 rounded-lg border border-sidebar-border/70 bg-muted/50 px-3 py-2 text-sm font-medium text-foreground hover:bg-muted sm:min-h-0"
                :disabled="saveChartLoading"
                @click="emit('save-chart')"
            >
                <Loader2 v-if="saveChartLoading" class="h-4 w-4 animate-spin" />
                <Bookmark v-else class="h-4 w-4" />
                Save chart
            </button>
        </div>
        <div
            v-show="showSpecificChartRequest"
            class="flex flex-col gap-2 sm:flex-row sm:items-end"
        >
            <input
                :value="chartRequest"
                type="text"
                class="min-h-[44px] w-full flex-1 rounded-lg border border-sidebar-border/70 bg-background px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring dark:border-sidebar-border sm:max-w-md"
                placeholder="e.g. total money spent per customer, pie chart of market share"
                @input="onChartRequestInput"
                @keydown.enter.prevent="emit('suggest-chart')"
            />
            <button
                type="button"
                class="inline-flex min-h-[44px] w-full items-center justify-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 disabled:opacity-50 sm:h-fit sm:w-auto"
                :disabled="!canChart || chartSuggestionLoading"
                @click="emit('suggest-chart')"
            >
                <Loader2 v-if="chartSuggestionLoading" class="h-4 w-4 animate-spin" />
                {{ chartSuggestionLoading ? 'Sending…' : 'Send' }}
            </button>
        </div>
        <p v-if="chartSuggestionError" class="text-sm text-destructive">
            {{ chartSuggestionError }}
        </p>
        <div v-if="!chartSuggestion" class="space-y-3 rounded-lg border border-dashed border-sidebar-border/70 py-4 px-4 text-center dark:border-sidebar-border sm:py-6 sm:px-5">
            <p class="text-xs text-muted-foreground">
                Generate a chart or pick a suggestion below.
            </p>
            <div v-if="chartSuggestions.length > 0" class="flex flex-col items-center gap-1.5">
                <div class="flex flex-wrap justify-center gap-3 mt-30">
                    <button
                        v-for="(suggestion, idx) in chartSuggestions.slice(0, 2)"
                        :key="idx"
                        type="button"
                        class="inline-flex max-w-[180px] cursor-pointer items-center truncate rounded-full border border-sidebar-border/60 bg-muted/40 px-2 py-0.5 text-[11px] text-foreground transition-colors hover:bg-muted hover:border-primary/40 dark:border-sidebar-border"
                        :disabled="!canChart || chartSuggestionLoading"
                        :title="suggestion"
                        @click="emit('apply-chart-suggestion', suggestion)"
                    >
                        {{ suggestion }}
                    </button>
                </div>
            </div>
        </div>
        <div v-else class="space-y-2">
            <p
                v-if="effectiveChartConfig?.title"
                class="text-sm font-medium text-foreground"
            >
                {{ effectiveChartConfig.title }}
            </p>
            <div class="h-[240px] w-full min-w-0 sm:h-[280px]">
                <Chart
                    v-if="effectiveChartConfig?.chartType"
                    :type="effectiveChartConfig.chartType"
                    :data="chartData"
                    :options="chartOptions"
                />
            </div>
        </div>
        <p v-if="savedChartError" class="text-sm text-destructive">
            {{ savedChartError }}
        </p>
        <div v-if="savedCharts.length > 0" class="border-t border-sidebar-border/70 pt-3">
            <button
                type="button"
                class="flex w-full items-center gap-1.5 text-left text-sm font-medium text-foreground"
                @click="savedChartsOpen = !savedChartsOpen"
            >
                <ChevronDown v-if="savedChartsOpen" class="h-4 w-4" />
                <ChevronRight v-else class="h-4 w-4" />
                Saved charts ({{ savedCharts.length }})
            </button>
            <ul v-show="savedChartsOpen" class="mt-2 max-h-32 space-y-1 overflow-y-auto">
                <li
                    v-for="chart in savedCharts"
                    :key="chart.id"
                    class="flex items-center justify-between gap-2 rounded border border-sidebar-border/50 bg-muted/30 px-2 py-1.5 text-sm"
                >
                    <button
                        type="button"
                        class="min-w-0 flex-1 truncate text-left hover:underline"
                        :title="chartTitle(chart)"
                        @click="emit('load-chart', chart)"
                    >
                        {{ chartTitle(chart) }}
                    </button>
                    <button
                        type="button"
                        class="shrink-0 rounded p-0.5 text-muted-foreground hover:bg-destructive/20 hover:text-destructive"
                        title="Delete"
                        @click="emit('delete-chart', chart)"
                    >
                        <Trash2 class="h-3.5 w-3.5" />
                    </button>
                </li>
            </ul>
        </div>
    </div>
</template>
