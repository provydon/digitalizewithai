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
    FileJson,
    FileSpreadsheet,
    FileText,
    MessageSquare,
    Table as TableIcon,
} from 'lucide-vue-next';
import { Camera, Copy, Pencil, Search, Trash2, Upload, Video } from 'lucide-vue-next';
import {
    TabsContent,
    TabsList,
    TabsRoot,
    TabsTrigger,
} from 'reka-ui';
import { computed, nextTick, onMounted, ref, watch } from 'vue';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { Chart } from 'vue-chartjs';
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
    content?: string | null;
    doc_page_count?: number;
    table_row_count?: number;
    suggested_prompts?: string[];
    insights?: string[];
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

const docPageCount = computed(() => {
    const dd = record.value?.digital_data;
    if (!dd || dd.type !== 'doc') return 0;
    return Math.max(1, dd.doc_page_count ?? 1);
});

const isMultiPageDoc = computed(() => docPageCount.value > 1);

const isTableData = computed(() => !!tableData.value);
const isDocData = computed(() => {
    const dd = record.value?.digital_data;
    return !!dd && dd.type === 'doc';
});

const suggestedPrompts = computed(() => {
    const list = record.value?.digital_data?.suggested_prompts;
    return Array.isArray(list) ? list.filter((p): p is string => typeof p === 'string' && p.trim() !== '') : [];
});

const insights = computed(() => {
    const list = record.value?.digital_data?.insights;
    return Array.isArray(list) ? list.filter((i): i is string => typeof i === 'string' && i.trim() !== '') : [];
});

// —— Doc pages: 100% backend. Only current page fetched from API when multi-page. ——
const docPageCurrent = ref(1);
const docPageContent = ref('');
const docPageLoading = ref(false);
const docPageError = ref<string | null>(null);

async function fetchDocPage(page: number) {
    if (!record.value || !isDocData.value) return;
    docPageLoading.value = true;
    docPageError.value = null;
    try {
        const { data } = await api.get<{ page: number; total_pages: number; content: string }>(
            `/dashboard/api/data/${record.value.id}/doc-page`,
            { params: { page } },
        );
        docPageContent.value = data.content ?? '';
        docPageCurrent.value = data.page;
    } catch (e: unknown) {
        const err = e as { response?: { data?: { message?: string } }; message?: string };
        docPageError.value = err.response?.data?.message ?? err.message ?? 'Failed to load page';
    } finally {
        docPageLoading.value = false;
    }
}

const displayedDocContent = computed(() => {
    if (isMultiPageDoc.value) return docPageContent.value;
    return docContent.value ?? '';
});

const docSearch = ref('');
const displayedDocContentFiltered = computed(() => {
    const raw = displayedDocContent.value;
    const q = docSearch.value.trim().toLowerCase();
    if (!q) return raw;
    const lines = raw.split('\n');
    const matched = lines.filter((line) => line.toLowerCase().includes(q));
    return matched.length ? matched.join('\n') : 'No matching lines.';
});

const docEditing = ref(false);
const docEditContent = ref('');
const docEditSaving = ref(false);
const docEditError = ref<string | null>(null);

function startDocEdit() {
    docEditError.value = null;
    docEditContent.value = displayedDocContent.value;
    docEditing.value = true;
}

function cancelDocEdit() {
    docEditing.value = false;
    docEditContent.value = '';
    docEditError.value = null;
}

async function saveDocEdit() {
    if (!record.value || !isDocData.value) return;
    docEditSaving.value = true;
    docEditError.value = null;
    try {
        const body: { content: string; page?: number } = { content: docEditContent.value };
        if (isMultiPageDoc.value) body.page = docPageCurrent.value;
        await api.patch(`/dashboard/api/data/${record.value.id}/doc-content`, body);
        await fetchRecord();
        if (isMultiPageDoc.value) await fetchDocPage(docPageCurrent.value);
        cancelDocEdit();
    } catch (e: unknown) {
        const err = e as { response?: { data?: { message?: string } }; message?: string };
        docEditError.value = err.response?.data?.message ?? err.message ?? 'Failed to save';
    } finally {
        docEditSaving.value = false;
    }
}

watch(
    () => [record.value?.id, isDocData.value, isMultiPageDoc.value] as const,
    ([id, isDoc, multi]: [number | undefined, boolean, boolean]) => {
        if (id && isDoc && multi) {
            docPageCurrent.value = 1;
            fetchDocPage(1);
        } else if (!multi) {
            docPageContent.value = '';
        }
    },
);

function goToDocPage(page: number) {
    const total = docPageCount.value;
    const p = Math.max(1, Math.min(total, page));
    docPageCurrent.value = p;
    fetchDocPage(p);
}

// —— Table rows: 100% backend. No full dataset on frontend — only current page from API. ——
type TableRowRecord = { id: number; row_index: number; cells: unknown[] };
type RowsMeta = { current_page: number; last_page: number; per_page: number; total: number };
const tableHeaders = ref<string[]>([]);
const tableRows = ref<TableRowRecord[]>([]); // current page only
const rowsMeta = ref<RowsMeta | null>(null);
const rowsLoading = ref(false);
const rowsError = ref<string | null>(null);
const tableSearch = ref(''); // sent as ?search= to API
const tablePage = ref(1); // sent as ?page= to API
const tablePerPage = ref(50);
const editRowOpen = ref(false);
const editRow = ref<TableRowRecord | null>(null);
const editCells = ref<string[]>([]);
const editSaving = ref(false);
const deleteRowOpen = ref(false);
const rowToDelete = ref<TableRowRecord | null>(null);
const deleteConfirming = ref(false);
const addRowOpen = ref(false);
const addRowCells = ref<string[]>([]);
const addRowSaving = ref(false);
const addRowsTab = ref<'manual' | 'upload'>('manual');
const appendFile = ref<File | null>(null);
const appendLoading = ref(false);
const appendProgress = ref(0);
const appendPhase = ref<'uploading' | 'extracting'>('uploading');
const appendError = ref<string | null>(null);
const appendSuccess = ref(false);
const appendFileInput = ref<HTMLInputElement | null>(null);
const appendCameraPhoto = ref<HTMLInputElement | null>(null);
const appendCameraVideo = ref<HTMLInputElement | null>(null);
const ACCEPT_TABLE_UPLOAD = 'image/jpeg,image/png,image/gif,image/webp,video/mp4,video/webm';
let searchDebounce: ReturnType<typeof setTimeout> | null = null;

async function fetchRecord() {
    if (!record.value) return;
    const { data } = await api.get<DataRecord>(`/dashboard/api/data/${props.id}`);
    record.value = data;
}

async function fetchTableRows() {
    if (!record.value || !isTableData.value) return;
    rowsLoading.value = true;
    rowsError.value = null;
    try {
        const params = new URLSearchParams({
            page: String(tablePage.value),
            per_page: String(tablePerPage.value),
        });
        if (tableSearch.value.trim()) params.set('search', tableSearch.value.trim());
        const { data } = await api.get<{
            headers: string[];
            rows: TableRowRecord[];
            meta: RowsMeta;
        }>(`/dashboard/api/data/${record.value.id}/rows?${params}`);
        tableHeaders.value = data.headers ?? [];
        tableRows.value = data.rows ?? [];
        rowsMeta.value = data.meta ?? null;
    } catch (e: unknown) {
        const err = e as { response?: { data?: { message?: string } }; message?: string };
        rowsError.value = err.response?.data?.message ?? err.message ?? 'Failed to load rows';
    } finally {
        rowsLoading.value = false;
    }
}

function openEditRow(row: TableRowRecord) {
    editRow.value = row;
    editCells.value = (row.cells ?? []).map((c) => (c == null ? '' : String(c)));
    editRowOpen.value = true;
}

function closeEditRow() {
    editRowOpen.value = false;
    editRow.value = null;
    editCells.value = [];
}

async function saveEditRow() {
    if (!record.value || !editRow.value) return;
    editSaving.value = true;
    try {
        await api.patch(
            `/dashboard/api/data/${record.value.id}/rows/${editRow.value.id}`,
            { cells: editCells.value },
        );
        await fetchRecord();
        await fetchTableRows();
        closeEditRow();
    } catch (e: unknown) {
        const err = e as { response?: { data?: { message?: string } }; message?: string };
        rowsError.value = err.response?.data?.message ?? err.message ?? 'Failed to save';
    } finally {
        editSaving.value = false;
    }
}

function openDeleteRow(row: TableRowRecord) {
    rowToDelete.value = row;
    deleteRowOpen.value = true;
}

function closeDeleteRow() {
    deleteRowOpen.value = false;
    rowToDelete.value = null;
}

async function confirmDeleteRow() {
    if (!record.value || !rowToDelete.value) return;
    deleteConfirming.value = true;
    try {
        await api.delete(`/dashboard/api/data/${record.value.id}/rows/${rowToDelete.value.id}`);
        await fetchRecord();
        await fetchTableRows();
        closeDeleteRow();
    } catch (e: unknown) {
        const err = e as { response?: { data?: { message?: string } }; message?: string };
        rowsError.value = err.response?.data?.message ?? err.message ?? 'Failed to delete';
    } finally {
        deleteConfirming.value = false;
    }
}

function openAddRow() {
    addRowCells.value = tableHeaders.value.map(() => '');
    addRowsTab.value = 'manual';
    appendFile.value = null;
    appendError.value = null;
    appendSuccess.value = false;
    addRowOpen.value = true;
}

function closeAddRow() {
    addRowOpen.value = false;
    addRowCells.value = [];
    appendFile.value = null;
    appendError.value = null;
    appendSuccess.value = false;
}

function onAppendFileChange(e: Event) {
    const input = e.target as HTMLInputElement;
    const file = input.files?.[0];
    if (file) appendFile.value = file;
}

function onAppendDrop(e: DragEvent) {
    e.preventDefault();
    const file = e.dataTransfer?.files?.[0];
    if (file) appendFile.value = file;
}

function openAppendFilePicker() {
    appendError.value = null;
    appendSuccess.value = false;
    appendFileInput.value?.click();
}

function openAppendCameraPhoto() {
    appendError.value = null;
    appendSuccess.value = false;
    appendCameraPhoto.value?.click();
}

function openAppendCameraVideo() {
    appendError.value = null;
    appendSuccess.value = false;
    appendCameraVideo.value?.click();
}

async function submitAppendUpload() {
    const file = appendFile.value;
    if (!file || !record.value) return;
    const allowed = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'video/mp4',
        'video/webm',
    ];
    if (!allowed.includes(file.type)) {
        appendError.value = 'Allowed: images (JPEG, PNG, GIF, WebP) or video (MP4, WebM).';
        return;
    }
    if (file.size > 20 * 1024 * 1024) {
        appendError.value = 'File must be under 20 MB.';
        return;
    }
    appendLoading.value = true;
    appendProgress.value = 0;
    appendPhase.value = 'uploading';
    appendError.value = null;
    appendSuccess.value = false;
    const formData = new FormData();
    formData.append('file', file);
    try {
        const { data } = await api.post<{ added: number; message?: string }>(
            `/dashboard/api/data/${record.value.id}/append-rows`,
            formData,
            {
                timeout: 300000,
                onUploadProgress(ev: { loaded: number; total?: number }) {
                    if (ev.total && ev.total > 0) {
                        appendProgress.value = Math.round((ev.loaded / ev.total) * 100);
                        if (appendProgress.value >= 100) appendPhase.value = 'extracting';
                    }
                },
            },
        );
        appendProgress.value = 100;
        appendPhase.value = 'extracting';
        appendSuccess.value = true;
        appendFile.value = null;
        if (appendFileInput.value) appendFileInput.value.value = '';
        if (appendCameraPhoto.value) appendCameraPhoto.value.value = '';
        if (appendCameraVideo.value) appendCameraVideo.value.value = '';
        await fetchRecord();
        const lastPage = rowsMeta.value?.last_page ?? 1;
        tablePage.value = lastPage;
        await fetchTableRows();
        setTimeout(() => { appendSuccess.value = false; }, 3000);
    } catch (e: unknown) {
        const err = e as { response?: { data?: { message?: string } }; message?: string };
        appendError.value = err.response?.data?.message ?? err.message ?? 'Failed to add rows';
    } finally {
        appendLoading.value = false;
    }
}

async function saveAddRow() {
    if (!record.value || !tableHeaders.value.length) return;
    addRowSaving.value = true;
    try {
        await api.post(`/dashboard/api/data/${record.value.id}/rows`, {
            cells: addRowCells.value,
        });
        await fetchRecord();
        const lastPage = rowsMeta.value?.last_page ?? 1;
        tablePage.value = lastPage;
        await fetchTableRows();
        closeAddRow();
    } catch (e: unknown) {
        const err = e as { response?: { data?: { message?: string } }; message?: string };
        rowsError.value = err.response?.data?.message ?? err.message ?? 'Failed to add row';
    } finally {
        addRowSaving.value = false;
    }
}

watch(
    () => [record.value?.id, isTableData.value] as const,
    ([id, isTable]: [number | undefined, boolean]) => {
        if (id && isTable) {
            tablePage.value = 1;
            fetchTableRows();
        }
    },
);
watch(tablePage, () => fetchTableRows());
watch(tableSearch, () => {
    if (searchDebounce) clearTimeout(searchDebounce);
    searchDebounce = setTimeout(() => {
        tablePage.value = 1;
        fetchTableRows();
    }, 300);
});

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

async function exportToDoc() {
    if (!record.value || !isDocData.value) return;
    let content: string;
    if (isMultiPageDoc.value) {
        const { data } = await api.get<{ content: string }>(
            `/dashboard/api/data/${record.value.id}/doc-content`,
        );
        content = data.content ?? '';
    } else {
        content = docContent.value ?? '';
    }
    const blob = new Blob([content], { type: 'text/plain;charset=utf-8' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `${record.value.name || 'export'}.txt`;
    a.click();
    URL.revokeObjectURL(url);
}

const canExportDoc = computed(() => isDocData.value && !!record.value);

// —— Copy to clipboard ——
const copyDocFeedback = ref(false);
const copyTableFeedback = ref(false);
const copyDocTooltipOpen = ref<boolean | undefined>(undefined);
const copyTableTooltipOpen = ref<boolean | undefined>(undefined);
const COPY_FEEDBACK_MS = 2500;

async function copyDocToClipboard() {
    if (!record.value || !isDocData.value) return;
    // Keep tooltip open and show "Copied" immediately so it doesn't close on click
    copyDocTooltipOpen.value = true;
    copyDocFeedback.value = true;
    setTimeout(() => {
        copyDocFeedback.value = false;
        copyDocTooltipOpen.value = undefined;
    }, COPY_FEEDBACK_MS);
    let text: string;
    if (isMultiPageDoc.value) {
        const { data } = await api.get<{ content: string }>(
            `/dashboard/api/data/${record.value.id}/doc-content`,
        );
        text = data.content ?? '';
    } else {
        text = docContent.value ?? '';
    }
    const clipboard = navigator.clipboard;
    if (clipboard) {
        await clipboard.writeText(text);
    } else {
        const textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.style.position = 'fixed';
        textarea.style.opacity = '0';
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand('copy');
        document.body.removeChild(textarea);
    }
}

function copyTableToClipboard() {
    const t = tableData.value;
    if (!t?.headers?.length) return;
    // Keep tooltip open and show "Copied" immediately so it doesn't close on click
    copyTableTooltipOpen.value = true;
    copyTableFeedback.value = true;
    setTimeout(() => {
        copyTableFeedback.value = false;
        copyTableTooltipOpen.value = undefined;
    }, COPY_FEEDBACK_MS);
    const payload = { headers: t.headers, rows: t.rows ?? [] };
    const text = JSON.stringify(payload, null, 2);
    const clipboard = navigator.clipboard;
    if (clipboard) {
        clipboard.writeText(text);
    } else {
        const textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.style.position = 'fixed';
        textarea.style.opacity = '0';
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand('copy');
        document.body.removeChild(textarea);
    }
}

function exportToJson() {
    const t = tableData.value;
    if (!t?.headers?.length || !record.value) return;
    const payload = { headers: t.headers, rows: t.rows ?? [] };
    const blob = new Blob([JSON.stringify(payload, null, 2)], { type: 'application/json' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `${record.value.name || 'export'}.json`;
    a.click();
    URL.revokeObjectURL(url);
}

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
        <TooltipProvider :delay-duration="300">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl px-3 py-4 sm:p-4">
            <div class="flex items-center gap-4">
                <Link
                    :href="dashboard()"
                    class="min-h-[44px] shrink-0 py-2 text-sm text-muted-foreground underline-offset-4 hover:underline sm:min-h-0"
                >
                    ← Back to dashboard
                </Link>
            </div>

            <div
                v-if="!loading && !error && record"
                class="min-w-0 rounded-xl border border-sidebar-border/70 bg-card shadow-sm dark:border-sidebar-border"
            >
                <div class="border-b border-sidebar-border/70 px-3 pt-4 dark:border-sidebar-border sm:px-4">
                    <h1 class="mb-2 truncate text-lg font-semibold text-foreground sm:text-xl">
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
                            class="flex h-10 w-full items-center justify-start gap-1 overflow-x-auto overflow-y-hidden rounded-lg bg-muted/50 p-1 text-muted-foreground [-webkit-overflow-scrolling:touch]"
                            aria-label="Data view tabs"
                        >
                            <TabsTrigger
                                value="data"
                                class="inline-flex shrink-0 items-center justify-center gap-1.5 rounded-md px-2.5 py-1.5 text-sm font-medium ring-offset-background transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 data-[state=active]:bg-background data-[state=active]:text-foreground data-[state=active]:shadow-sm sm:gap-2 sm:px-3"
                            >
                                <TableIcon class="h-4 w-4" />
                                Data
                            </TabsTrigger>
                            <TabsTrigger
                                value="ask"
                                class="inline-flex shrink-0 items-center justify-center gap-1.5 rounded-md px-2.5 py-1.5 text-sm font-medium ring-offset-background transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 data-[state=active]:bg-background data-[state=active]:text-foreground data-[state=active]:shadow-sm sm:gap-2 sm:px-3"
                            >
                                <MessageSquare class="h-4 w-4" />
                                Ask AI
                            </TabsTrigger>
                            <TabsTrigger
                                v-if="isTableData"
                                value="charts"
                                class="inline-flex shrink-0 items-center justify-center gap-1.5 rounded-md px-2.5 py-1.5 text-sm font-medium ring-offset-background transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 data-[state=active]:bg-background data-[state=active]:text-foreground data-[state=active]:shadow-sm sm:gap-2 sm:px-3"
                            >
                                <BarChart3 class="h-4 w-4" />
                                Charts
                            </TabsTrigger>
                            <TabsTrigger
                                value="export"
                                class="inline-flex shrink-0 items-center justify-center gap-1.5 rounded-md px-2.5 py-1.5 text-sm font-medium ring-offset-background transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 data-[state=active]:bg-background data-[state=active]:text-foreground data-[state=active]:shadow-sm sm:gap-2 sm:px-3"
                            >
                                <FileSpreadsheet class="h-4 w-4" />
                                Export
                            </TabsTrigger>
                        </TabsList>

                        <TabsContent value="data" class="mt-0 rounded-b-xl">
                            <div class="p-3 pt-4 sm:p-6">
                                <!-- Doc: single page from record, multi-page from API (one page at a time) -->
                                <div v-if="isDocData" class="space-y-4">
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <div class="relative min-w-0 flex-1 basis-full sm:basis-0 sm:max-w-sm">
                                            <Search
                                                class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                                            />
                                            <input
                                                v-model="docSearch"
                                                type="search"
                                                placeholder="Search for anything.."
                                                class="w-full rounded-lg border border-sidebar-border/70 bg-background py-2 pl-9 pr-3 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring dark:border-sidebar-border"
                                            />
                                        </div>
                                        <div
                                            v-if="isMultiPageDoc"
                                            class="flex flex-wrap items-center gap-2 text-sm"
                                        >
                                            <span class="text-muted-foreground">
                                                Page {{ docPageCurrent }} of {{ docPageCount }}
                                            </span>
                                            <button
                                                type="button"
                                                class="rounded-lg border border-sidebar-border/70 px-3 py-1.5 text-foreground hover:bg-muted/60 disabled:opacity-50 dark:border-sidebar-border"
                                                :disabled="docPageCurrent <= 1"
                                                @click="goToDocPage(docPageCurrent - 1)"
                                            >
                                                Previous
                                            </button>
                                            <button
                                                type="button"
                                                class="rounded-lg border border-sidebar-border/70 px-3 py-1.5 text-foreground hover:bg-muted/60 disabled:opacity-50 dark:border-sidebar-border"
                                                :disabled="docPageCurrent >= docPageCount"
                                                @click="goToDocPage(docPageCurrent + 1)"
                                            >
                                                Next
                                            </button>
                                        </div>
                                        <div class="ml-auto flex items-center gap-2">
                                            <button
                                                v-if="!docEditing"
                                                type="button"
                                                class="rounded p-1.5 text-muted-foreground hover:bg-muted/50 hover:text-foreground"
                                                title="Edit"
                                                @click="startDocEdit"
                                            >
                                                <Pencil class="h-3.5 w-3.5" />
                                            </button>
                                            <Tooltip
                                                :open="copyDocTooltipOpen"
                                                @update:open="(v) => { if (!copyDocFeedback) copyDocTooltipOpen = v === false ? undefined : v }"
                                            >
                                                <TooltipTrigger as-child>
                                                    <button
                                                        type="button"
                                                        class="rounded p-1.5 text-muted-foreground hover:bg-muted/50 hover:text-foreground"
                                                        @click="copyDocToClipboard"
                                                    >
                                                        <Copy class="h-3.5 w-3.5" />
                                                    </button>
                                                </TooltipTrigger>
                                                <TooltipContent>
                                                    {{ copyDocFeedback ? 'Copied to clipboard' : 'Copy to clipboard' }}
                                                </TooltipContent>
                                            </Tooltip>
                                        </div>
                                    </div>
                                    <p v-if="docPageError" class="text-sm text-destructive">
                                        {{ docPageError }}
                                    </p>
                                    <div
                                        v-if="docPageLoading && isMultiPageDoc"
                                        class="py-8 text-center text-sm text-muted-foreground"
                                    >
                                        Loading page…
                                    </div>
                                    <div
                                        v-else
                                        class="content-paper min-w-0 rounded-xl bg-white p-4 text-gray-900 shadow-sm sm:p-5"
                                    >
                                        <div v-if="docEditing" class="space-y-3">
                                            <textarea
                                                v-model="docEditContent"
                                                class="min-h-[240px] w-full max-w-full resize-y rounded-lg border border-gray-300 bg-white p-3 font-sans text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-0 sm:p-4 sm:text-base"
                                                :placeholder="'Document content…'"
                                                spellcheck="false"
                                            />
                                            <p v-if="docEditError" class="text-sm text-destructive">
                                                {{ docEditError }}
                                            </p>
                                            <div class="flex flex-wrap gap-2">
                                                <Button
                                                    size="sm"
                                                    :disabled="docEditSaving"
                                                    @click="saveDocEdit"
                                                >
                                                    {{ docEditSaving ? 'Saving…' : 'Save' }}
                                                </Button>
                                                <Button
                                                    size="sm"
                                                    variant="secondary"
                                                    :disabled="docEditSaving"
                                                    @click="cancelDocEdit"
                                                >
                                                    Cancel
                                                </Button>
                                            </div>
                                        </div>
                                        <template v-else>
                                            <pre
                                                class="max-w-full overflow-x-auto whitespace-pre-wrap rounded-lg bg-white p-3 font-sans text-sm text-gray-900 sm:p-4 sm:text-base"
                                            >{{ displayedDocContentFiltered || ' ' }}</pre>
                                        </template>
                                    </div>
                                </div>

                                <!-- Table: paginated, searchable, editable (always white background) -->
                                <div v-else-if="tableData" class="table-paper space-y-4 rounded-xl bg-white p-4 text-gray-900 shadow-sm sm:p-5">
                                    <div class="flex flex-wrap items-center gap-2 sm:gap-3">
                                        <div class="relative min-w-0 flex-1 basis-full sm:basis-0 sm:max-w-sm">
                                            <Search
                                                class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"
                                            />
                                            <input
                                                v-model="tableSearch"
                                                type="search"
                                                placeholder="Search for anything.."
                                                class="w-full rounded-lg border border-gray-300 bg-gray-50 py-2 pl-9 pr-3 text-sm text-gray-900 placeholder:text-gray-500 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-0"
                                            />
                                        </div>
                                        <span
                                            v-if="rowsMeta"
                                            class="text-sm text-gray-600"
                                        >
                                            {{ rowsMeta.total.toLocaleString() }} row{{ rowsMeta.total !== 1 ? 's' : '' }}
                                        </span>
                                        <div class="ml-auto flex items-center gap-2">
                                            <Button
                                                size="sm"
                                                variant="outline"
                                                class="gap-1.5 border-gray-300 text-gray-900 hover:bg-gray-100"
                                                @click="openAddRow"
                                            >
                                                <TableIcon class="h-3.5 w-3.5" />
                                                Add rows
                                            </Button>
                                            <Tooltip
                                                :open="copyTableTooltipOpen"
                                                @update:open="(v) => { if (!copyTableFeedback) copyTableTooltipOpen = v }"
                                            >
                                                <TooltipTrigger as-child>
                                                    <button
                                                        type="button"
                                                        class="rounded p-1.5 text-gray-500 hover:bg-gray-200 hover:text-gray-900"
                                                        @click="copyTableToClipboard"
                                                    >
                                                        <Copy class="h-3.5 w-3.5" />
                                                    </button>
                                                </TooltipTrigger>
                                                <TooltipContent>
                                                    {{ copyTableFeedback ? 'Copied to clipboard' : 'Copy to clipboard' }}
                                                </TooltipContent>
                                            </Tooltip>
                                        </div>
                                    </div>
                                    <p v-if="rowsError" class="text-sm text-destructive">
                                        {{ rowsError }}
                                    </p>
                                    <div v-if="rowsLoading" class="py-8 text-center text-sm text-gray-600">
                                        Loading rows…
                                    </div>
                                    <div class="-mx-3 overflow-x-auto overscroll-x-contain sm:mx-0">
                                        <table
                                            class="w-full min-w-[280px] border-collapse text-left text-sm text-gray-900 sm:min-w-[300px]"
                                        >
                                            <thead>
                                                <tr class="border-b border-gray-200 bg-gray-100">
                                                    <th
                                                        v-for="(h, i) in tableHeaders"
                                                        :key="i"
                                                        class="px-2 py-2 font-medium text-gray-900 sm:px-4 sm:py-3"
                                                    >
                                                        {{ h }}
                                                    </th>
                                                    <th
                                                        class="w-20 px-2 py-2 font-medium text-gray-900 sm:w-24 sm:px-4 sm:py-3"
                                                    >
                                                        Actions
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr
                                                    v-for="row in tableRows"
                                                    :key="row.id"
                                                    class="border-b border-gray-200 hover:bg-gray-50"
                                                >
                                                    <td
                                                        v-for="(cell, ci) in (row.cells ?? [])"
                                                        :key="ci"
                                                        class="max-w-[120px] truncate px-2 py-2 text-gray-700 sm:max-w-none sm:px-4 sm:py-3"
                                                        :title="cell != null ? String(cell) : undefined"
                                                    >
                                                        {{ cell == null ? '—' : cell }}
                                                    </td>
                                                    <td class="px-2 py-2 sm:px-4">
                                                        <div class="flex items-center gap-0.5 sm:gap-1">
                                                            <button
                                                                type="button"
                                                                class="min-h-[44px] min-w-[44px] rounded p-2 text-gray-500 hover:bg-gray-200 hover:text-gray-900 sm:min-h-0 sm:min-w-0 sm:p-1.5"
                                                                title="Edit row"
                                                                @click="openEditRow(row)"
                                                            >
                                                                <Pencil class="h-4 w-4" />
                                                            </button>
                                                            <button
                                                                type="button"
                                                                class="min-h-[44px] min-w-[44px] rounded p-2 text-gray-500 hover:bg-red-50 hover:text-red-600 sm:min-h-0 sm:min-w-0 sm:p-1.5"
                                                                title="Delete row"
                                                                @click="openDeleteRow(row)"
                                                            >
                                                                <Trash2 class="h-4 w-4" />
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div
                                        v-if="rowsMeta && rowsMeta.last_page > 1"
                                        class="flex flex-wrap items-center gap-2 text-sm"
                                    >
                                        <button
                                            type="button"
                                            class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-gray-900 hover:bg-gray-100 disabled:opacity-50"
                                            :disabled="tablePage <= 1"
                                            @click="tablePage = Math.max(1, tablePage - 1)"
                                        >
                                            Previous
                                        </button>
                                        <span class="text-gray-600">
                                            Page {{ rowsMeta.current_page }} of {{ rowsMeta.last_page }}
                                        </span>
                                        <button
                                            type="button"
                                            class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-gray-900 hover:bg-gray-100 disabled:opacity-50"
                                            :disabled="tablePage >= rowsMeta.last_page"
                                            @click="tablePage = Math.min(rowsMeta.last_page, tablePage + 1)"
                                        >
                                            Next
                                        </button>
                                    </div>
                                </div>

                                <p v-else class="text-muted-foreground">
                                    No content to display.
                                </p>
                            </div>
                        </TabsContent>

                        <!-- Ask AI: ChatGPT-style (chat up, input down), table only -->
                        <TabsContent value="ask" class="mt-0 rounded-b-xl">
                            <div class="flex min-h-[320px] flex-col p-3 sm:min-h-[420px] sm:p-4">
                                <div
                                    ref="chatScrollRef"
                                    class="flex flex-1 flex-col gap-3 overflow-y-auto rounded-lg py-2"
                                >
                                    <template v-if="messages.length === 0">
                                        <p class="px-2 py-4 text-center text-sm text-muted-foreground">
                                            Ask anything about this data. Type below or try a suggestion.
                                        </p>
                                        <div
                                            v-if="suggestedPrompts.length > 0"
                                            class="flex flex-wrap justify-center gap-2 px-2 pb-2"
                                        >
                                            <button
                                                v-for="(p, i) in suggestedPrompts"
                                                :key="i"
                                                type="button"
                                                class="inline-flex items-center rounded-lg border border-border bg-muted/50 px-3 py-1.5 text-left text-sm text-foreground transition-colors hover:bg-muted hover:border-primary/50"
                                                @click="askAi(p)"
                                            >
                                                {{ p }}
                                            </button>
                                        </div>
                                        <div
                                            v-if="insights.length > 0"
                                            class="mx-2 mt-2 flex flex-wrap justify-center gap-1.5 border-t border-border/60 pt-3"
                                        >
                                            <span
                                                v-for="(insight, i) in insights"
                                                :key="i"
                                                class="rounded-md bg-muted/40 px-2 py-1 text-xs text-muted-foreground"
                                            >
                                                {{ insight }}
                                            </span>
                                        </div>
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
                                    <div class="flex flex-col gap-2 sm:flex-row">
                                        <textarea
                                            v-model="question"
                                            class="min-h-[44px] w-full flex-1 rounded-lg border border-sidebar-border/70 bg-background px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring dark:border-sidebar-border"
                                            placeholder="Ask about this data..."
                                            rows="1"
                                            @keydown.enter.exact.prevent="askAi()"
                                        />
                                        <button
                                            type="button"
                                            class="min-h-[44px] w-full shrink-0 rounded-lg bg-primary px-4 py-2.5 text-sm font-medium text-primary-foreground hover:bg-primary/90 disabled:opacity-50 sm:h-fit sm:w-auto sm:self-end sm:py-2"
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
                            <div class="flex flex-col gap-4 p-3 pt-4 sm:p-6">
                                <div class="flex flex-wrap items-center gap-2">
                                    <button
                                        type="button"
                                        class="inline-flex min-h-[44px] items-center gap-2 rounded-lg border border-sidebar-border/70 px-3 py-2 text-sm font-medium text-foreground hover:bg-muted/60 dark:border-sidebar-border sm:min-h-0"
                                        :class="showSpecificChartRequest ? 'bg-muted/50' : 'bg-transparent'"
                                        @click="showSpecificChartRequest = !showSpecificChartRequest"
                                    >
                                        Ask for Specific Chart
                                    </button>
                                    <button
                                        type="button"
                                        class="inline-flex min-h-[44px] w-full items-center justify-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 disabled:opacity-50 sm:w-fit"
                                        :disabled="!canChart || chartSuggestionLoading"
                                        @click="suggestChartFromAi"
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
                                        v-model="chartRequest"
                                        type="text"
                                        class="min-h-[44px] w-full flex-1 rounded-lg border border-sidebar-border/70 bg-background px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring dark:border-sidebar-border sm:max-w-md"
                                        placeholder="e.g. bar chart of sales by region, pie chart of market share"
                                        @keydown.enter.prevent="suggestChartFromAi()"
                                    />
                                    <button
                                        type="button"
                                        class="min-h-[44px] w-full rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 disabled:opacity-50 sm:h-fit sm:w-auto"
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
                        </TabsContent>

                        <TabsContent value="export" class="mt-0 rounded-b-xl">
                            <div class="flex flex-col gap-4 p-3 pt-4 sm:p-6">
                                <p class="text-sm text-muted-foreground">
                                    Download this data to your device.
                                </p>
                                <div class="flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:gap-3">
                                    <button
                                        v-if="isTableData"
                                        type="button"
                                        class="inline-flex min-h-[44px] w-full items-center justify-center gap-2 rounded-lg border border-sidebar-border/70 bg-muted/30 px-3 py-2.5 text-sm font-medium text-foreground hover:bg-muted/60 dark:border-sidebar-border disabled:opacity-50 sm:w-auto sm:py-2"
                                        :disabled="!canExportExcel"
                                        @click="exportToExcel"
                                    >
                                        <FileSpreadsheet class="h-4 w-4 shrink-0" />
                                        Export to Excel
                                    </button>
                                    <button
                                        v-if="isTableData"
                                        type="button"
                                        class="inline-flex min-h-[44px] w-full items-center justify-center gap-2 rounded-lg border border-sidebar-border/70 bg-muted/30 px-3 py-2.5 text-sm font-medium text-foreground hover:bg-muted/60 dark:border-sidebar-border disabled:opacity-50 sm:w-auto sm:py-2"
                                        :disabled="!canExportExcel"
                                        @click="exportToJson"
                                    >
                                        <FileJson class="h-4 w-4 shrink-0" />
                                        Export to JSON
                                    </button>
                                    <button
                                        v-if="isDocData"
                                        type="button"
                                        class="inline-flex min-h-[44px] w-full items-center justify-center gap-2 rounded-lg border border-sidebar-border/70 bg-muted/30 px-3 py-2.5 text-sm font-medium text-foreground hover:bg-muted/60 dark:border-sidebar-border disabled:opacity-50 sm:w-auto sm:py-2"
                                        :disabled="!canExportDoc"
                                        @click="exportToDoc"
                                    >
                                        <FileText class="h-4 w-4 shrink-0" />
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

                    <!-- Edit row dialog -->
                    <Dialog :open="editRowOpen" @update:open="editRowOpen = $event">
                        <DialogContent class="sm:max-w-lg">
                            <DialogHeader>
                                <DialogTitle>Edit row</DialogTitle>
                            </DialogHeader>
                            <div
                                v-if="editRow && tableHeaders.length"
                                class="grid gap-3 py-2"
                            >
                                <div
                                    v-for="(header, i) in tableHeaders"
                                    :key="i"
                                    class="grid gap-1.5"
                                >
                                    <Label :for="`edit-cell-${i}`">{{ header }}</Label>
                                    <Input
                                        :id="`edit-cell-${i}`"
                                        v-model="editCells[i]"
                                        class="w-full"
                                    />
                                </div>
                            </div>
                            <DialogFooter class="gap-2">
                                <DialogClose as-child>
                                    <Button variant="secondary" @click="closeEditRow">
                                        Cancel
                                    </Button>
                                </DialogClose>
                                <Button
                                    :disabled="editSaving"
                                    @click="saveEditRow"
                                >
                                    {{ editSaving ? 'Saving…' : 'Save' }}
                                </Button>
                            </DialogFooter>
                        </DialogContent>
                    </Dialog>

                    <!-- Add rows dialog: Manual or From photo/video -->
                    <Dialog :open="addRowOpen" @update:open="addRowOpen = $event">
                        <DialogContent class="sm:max-w-lg">
                            <DialogHeader>
                                <DialogTitle>Add rows</DialogTitle>
                            </DialogHeader>
                            <TabsRoot v-model="addRowsTab" class="w-full">
                                <TabsList class="grid w-full grid-cols-2">
                                    <TabsTrigger value="manual">Manual</TabsTrigger>
                                    <TabsTrigger value="upload">From photo or video</TabsTrigger>
                                </TabsList>
                                <TabsContent value="manual" class="mt-3">
                                    <div
                                        v-if="tableHeaders.length"
                                        class="grid gap-3 py-2"
                                    >
                                        <div
                                            v-for="(header, i) in tableHeaders"
                                            :key="i"
                                            class="grid gap-1.5"
                                        >
                                            <Label :for="`add-cell-${i}`">{{ header }}</Label>
                                            <Input
                                                :id="`add-cell-${i}`"
                                                v-model="addRowCells[i]"
                                                class="w-full"
                                            />
                                        </div>
                                    </div>
                                    <DialogFooter class="mt-4 gap-2">
                                        <DialogClose as-child>
                                            <Button variant="secondary" @click="closeAddRow">
                                                Cancel
                                            </Button>
                                        </DialogClose>
                                        <Button
                                            :disabled="addRowSaving"
                                            @click="saveAddRow"
                                        >
                                            {{ addRowSaving ? 'Adding…' : 'Add row' }}
                                        </Button>
                                    </DialogFooter>
                                </TabsContent>
                                <TabsContent value="upload" class="mt-3">
                                    <p class="mb-3 text-sm text-muted-foreground">
                                        Upload a photo or video of a table — we'll extract the rows and append them to this table.
                                    </p>
                                    <input
                                        ref="appendFileInput"
                                        type="file"
                                        :accept="ACCEPT_TABLE_UPLOAD"
                                        class="hidden"
                                        @change="onAppendFileChange"
                                    />
                                    <input
                                        ref="appendCameraPhoto"
                                        type="file"
                                        accept="image/*"
                                        capture="environment"
                                        class="hidden"
                                        aria-label="Take a picture"
                                        @change="onAppendFileChange"
                                    />
                                    <input
                                        ref="appendCameraVideo"
                                        type="file"
                                        accept="video/*"
                                        capture="environment"
                                        class="hidden"
                                        aria-label="Record video"
                                        @change="onAppendFileChange"
                                    />
                                    <div class="mb-3 flex flex-wrap gap-2">
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="sm"
                                            :disabled="appendLoading"
                                            @click="openAppendCameraPhoto"
                                        >
                                            <Camera class="mr-1.5 h-4 w-4" />
                                            Take a picture
                                        </Button>
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="sm"
                                            :disabled="appendLoading"
                                            @click="openAppendCameraVideo"
                                        >
                                            <Video class="mr-1.5 h-4 w-4" />
                                            Record video
                                        </Button>
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="sm"
                                            :disabled="appendLoading"
                                            @click="openAppendFilePicker"
                                        >
                                            <Upload class="mr-1.5 h-4 w-4" />
                                            Choose a file
                                        </Button>
                                    </div>
                                    <div
                                        class="cursor-pointer rounded-lg border-2 border-dashed border-sidebar-border/70 bg-muted/30 p-4 text-center transition-colors dark:border-sidebar-border"
                                        :class="{ 'border-primary/50 bg-primary/5': appendLoading }"
                                        role="button"
                                        tabindex="0"
                                        @click="openAppendFilePicker"
                                        @drop.prevent="onAppendDrop"
                                        @dragover.prevent
                                        @keydown.enter="openAppendFilePicker"
                                        @keydown.space.prevent="openAppendFilePicker"
                                    >
                                        <Upload class="mx-auto mb-2 h-6 w-6 text-muted-foreground" />
                                        <p class="mb-1 text-sm text-muted-foreground">
                                            Or drag a file here
                                        </p>
                                        <p v-if="appendFile" class="mb-2 text-sm font-medium text-foreground">
                                            {{ appendFile.name }}
                                        </p>
                                        <p class="mb-2 text-xs text-muted-foreground">
                                            Images: JPEG, PNG, GIF, WebP. Video: MP4, WebM. Max 20 MB.
                                        </p>
                                        <div v-if="appendLoading" class="mt-2 space-y-2">
                                            <div class="flex justify-between text-sm text-muted-foreground">
                                                <span>{{ appendPhase === 'uploading' ? `Uploading… ${appendProgress}%` : 'Extracting…' }}</span>
                                                <span v-if="appendPhase === 'uploading'" class="tabular-nums">{{ appendProgress }}%</span>
                                            </div>
                                            <div class="h-2 w-full overflow-hidden rounded-full bg-muted">
                                                <div
                                                    class="h-full rounded-full bg-primary transition-[width] duration-300"
                                                    :style="{ width: appendPhase === 'extracting' ? '100%' : `${appendProgress}%` }"
                                                />
                                            </div>
                                        </div>
                                        <p v-else-if="appendError" class="text-sm text-destructive">
                                            {{ appendError }}
                                        </p>
                                        <p v-else-if="appendSuccess" class="text-sm text-green-600 dark:text-green-400">
                                            Rows appended. They appear in the table.
                                        </p>
                                    </div>
                                    <DialogFooter class="mt-2 gap-2">
                                        <DialogClose as-child>
                                            <Button variant="secondary" @click="closeAddRow">
                                                Close
                                            </Button>
                                        </DialogClose>
                                        <Button
                                            :disabled="!appendFile || appendLoading"
                                            @click="submitAppendUpload"
                                        >
                                            {{ appendLoading ? 'Adding…' : 'Add rows from file' }}
                                        </Button>
                                    </DialogFooter>
                                </TabsContent>
                            </TabsRoot>
                        </DialogContent>
                    </Dialog>

                    <!-- Delete row confirmation -->
                    <Dialog :open="deleteRowOpen" @update:open="deleteRowOpen = $event">
                        <DialogContent class="sm:max-w-md">
                            <DialogHeader>
                                <DialogTitle>Delete row?</DialogTitle>
                            </DialogHeader>
                            <p class="text-sm text-muted-foreground">
                                This row will be permanently removed. This cannot be undone.
                            </p>
                            <DialogFooter class="gap-2">
                                <DialogClose as-child>
                                    <Button variant="secondary" @click="closeDeleteRow">
                                        Cancel
                                    </Button>
                                </DialogClose>
                                <Button
                                    variant="destructive"
                                    :disabled="deleteConfirming"
                                    @click="confirmDeleteRow"
                                >
                                    {{ deleteConfirming ? 'Deleting…' : 'Delete' }}
                                </Button>
                            </DialogFooter>
                        </DialogContent>
                    </Dialog>
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
        </TooltipProvider>
    </AppLayout>
</template>
