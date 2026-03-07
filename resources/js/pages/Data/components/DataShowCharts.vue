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
import { BarChart3 } from 'lucide-vue-next';
import { computed } from 'vue';
import { Chart } from 'vue-chartjs';
import { useAppearance } from '@/composables/useAppearance';
import type { ChartSuggestion } from '../types';

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

const props = defineProps<{
    tableData: { headers?: string[]; rows?: unknown[][] } | null;
    chartSuggestion: ChartSuggestion | null;
    chartRequest: string;
    showSpecificChartRequest: boolean;
    chartSuggestionLoading: boolean;
    canChart: boolean;
}>();

const emit = defineEmits<{
    'update:chartRequest': [v: string];
    'update:showSpecificChartRequest': [v: boolean];
    'suggest-chart': [];
}>();

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
    const t = props.tableData;
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
</script>

<template>
    <div class="flex flex-col gap-4 p-3 pt-4 sm:p-6">
        <div class="flex flex-wrap items-center gap-2">
            <button
                type="button"
                class="inline-flex min-h-[44px] cursor-pointer items-center gap-2 rounded-lg border-2 border-gray-300 px-3 py-2 text-sm font-medium text-foreground transition-colors hover:bg-muted/60 hover:border-gray-400 dark:border-gray-600 dark:hover:border-gray-500 sm:min-h-0"
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
                <BarChart3 class="h-4 w-4" />
                {{ chartSuggestionLoading ? '…' : chartSuggestion ? 'Generate Another Chart' : 'Generate Chart' }}
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
                placeholder="e.g. bar chart of sales by region, pie chart of market share"
                @input="onChartRequestInput"
                @keydown.enter.prevent="emit('suggest-chart')"
            />
            <button
                type="button"
                class="min-h-[44px] w-full rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 disabled:opacity-50 sm:h-fit sm:w-auto"
                :disabled="!canChart || chartSuggestionLoading"
                @click="emit('suggest-chart')"
            >
                Send
            </button>
        </div>
        <div v-if="!chartSuggestion" class="rounded-lg border border-dashed border-sidebar-border/70 py-12 text-center text-sm text-muted-foreground dark:border-sidebar-border">
            Click "Generate Chart" to have AI pick the best chart type and columns. Use "Ask for Specific Chart" to describe the chart you want.
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
    </div>
</template>
