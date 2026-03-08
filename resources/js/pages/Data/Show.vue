<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    BarChart3,
    FileSpreadsheet,
    MessageSquare,
    Table as TableIcon,
} from 'lucide-vue-next';
import { TabsContent, TabsList, TabsRoot, TabsTrigger } from 'reka-ui';
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import {
    TooltipProvider,
} from '@/components/ui/tooltip';
import AppLayout from '@/layouts/AppLayout.vue';
import api from '@/lib/api';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import autoTable from 'jspdf-autotable';
import { jsPDF } from 'jspdf';
import * as XLSX from 'xlsx';
import DataShowHeader from '@/pages/Data/components/DataShowHeader.vue';
import DataShowDocView from '@/pages/Data/components/DataShowDocView.vue';
import DataShowTableView from '@/pages/Data/components/DataShowTableView.vue';
import DataShowAskAi from '@/pages/Data/components/DataShowAskAi.vue';
import DataShowCharts from '@/pages/Data/components/DataShowCharts.vue';
import DataShowExport from '@/pages/Data/components/DataShowExport.vue';
import DataEditRowDialog from '@/pages/Data/components/DataEditRowDialog.vue';
import DataAddRowsDialog from '@/pages/Data/components/DataAddRowsDialog.vue';
import DataAppendDocDialog from '@/pages/Data/components/DataAppendDocDialog.vue';
import DataDeleteRowDialog from '@/pages/Data/components/DataDeleteRowDialog.vue';
import type {
    ChartSuggestion,
    ChatMessage,
    DataRecord,
    RowsMeta,
    SavedChart,
    SavedChat,
    TableRowRecord,
} from '@/pages/Data/types';

type Props = {
    id: number;
    from?: 'dashboard' | 'data';
};

const props = withDefaults(defineProps<Props>(), {
    from: 'dashboard',
});

const record = ref<DataRecord | null>(null);
const loading = ref(true);
const error = ref<string | null>(null);
const activeTab = ref('data');

const breadcrumbs = computed<BreadcrumbItem[]>(() => {
    const items: BreadcrumbItem[] = [{ title: 'Dashboard', href: dashboard() }];
    if (props.from === 'data') {
        items.push({ title: 'Data', href: '/data' });
    }
    items.push({ title: record.value?.name ?? '…', href: '#' });
    return items;
});

const backHref = computed(() => (props.from === 'data' ? '/data' : dashboard()));
const backLabel = computed(() => (props.from === 'data' ? 'Back to Data' : 'Back to dashboard'));

async function fetchSavedChats() {
    if (!record.value) return;
    try {
        const { data } = await api.get<{ chats: SavedChat[] }>(`/dashboard/api/data/${record.value.id}/saved-chats`);
        savedChats.value = data.chats ?? [];
    } catch {
        savedChats.value = [];
    }
}

async function fetchSavedCharts() {
    if (!record.value) return;
    try {
        const { data } = await api.get<{ charts: SavedChart[] }>(`/dashboard/api/data/${record.value.id}/saved-charts`);
        savedCharts.value = data.charts ?? [];
    } catch {
        savedCharts.value = [];
    }
}

let processingPollTimer: ReturnType<typeof setInterval> | null = null;

const isProcessing = computed(() => {
    const dd = record.value?.digital_data;
    return !!dd && (dd as { status?: string }).status === 'processing';
});
const processingBatches = computed(() => {
    const dd = record.value?.digital_data as { processing_batches_done?: number; processing_batches_total?: number } | undefined;
    if (!dd) return null;
    const total = dd.processing_batches_total ?? 0;
    if (total === 0) return null;
    return { done: dd.processing_batches_done ?? 0, total };
});

onMounted(async () => {
    try {
        const { data } = await api.get<DataRecord>(`/dashboard/api/data/${props.id}`);
        record.value = data;
        await Promise.all([fetchSavedChats(), fetchSavedCharts()]);
    } catch (e: unknown) {
        const err = e as { response?: { data?: { message?: string } }; message?: string };
        error.value = err.response?.data?.message ?? err.message ?? 'Failed to load';
    } finally {
        loading.value = false;
    }
});

async function pollWhileProcessing() {
    await fetchRecord();
    if (record.value && isDocData.value) {
        await fetchDocFullContent();
    }
    await fetchTableRows();
}

watch(isProcessing, (processing) => {
    if (processingPollTimer) {
        clearInterval(processingPollTimer);
        processingPollTimer = null;
    }
    if (processing) {
        processingPollTimer = setInterval(() => {
            void pollWhileProcessing();
        }, 2000);
    }
});

onBeforeUnmount(() => {
    if (processingPollTimer) {
        clearInterval(processingPollTimer);
        processingPollTimer = null;
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

// —— Doc state (single long scroll + section jump) ——
const docPageCurrent = ref(1);
const fullDocPages = ref<string[]>([]);
const fullDocLoading = ref(false);
const docPageError = ref<string | null>(null);

async function fetchDocFullContent() {
    if (!record.value || !isDocData.value) return;
    fullDocLoading.value = true;
    docPageError.value = null;
    try {
        const { data } = await api.get<{ content: string; pages?: string[] }>(
            `/dashboard/api/data/${record.value.id}/doc-content`,
        );
        const content = data.content ?? '';
        fullDocPages.value = Array.isArray(data.pages) && data.pages.length > 0
            ? data.pages
            : (content ? [content] : []);
    } catch (e: unknown) {
        const err = e as { response?: { data?: { message?: string } }; message?: string };
        docPageError.value = err.response?.data?.message ?? err.message ?? 'Failed to load document';
    } finally {
        fullDocLoading.value = false;
    }
}

const displayedDocContent = computed(() => {
    if (fullDocPages.value.length > 0) return fullDocPages.value.join('\n\n');
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
        await api.patch(`/dashboard/api/data/${record.value.id}/doc-content`, {
            content: docEditContent.value,
        });
        await fetchRecord();
        await fetchDocFullContent();
        cancelDocEdit();
    } catch (e: unknown) {
        const err = e as { response?: { data?: { message?: string } }; message?: string };
        docEditError.value = err.response?.data?.message ?? err.message ?? 'Failed to save';
    } finally {
        docEditSaving.value = false;
    }
}

function goToDocPage(page: number) {
    const total = docPageCount.value;
    const p = Math.max(1, Math.min(total, page));
    if (docEditing.value) cancelDocEdit();
    docPageCurrent.value = p;
    nextTick(() => {
        const el = document.getElementById(`doc-section-${p}`);
        el?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
}

watch(
    () => [record.value?.id, isDocData.value] as const,
    ([id, isDoc]: [number | undefined, boolean]) => {
        if (id && isDoc) {
            docPageCurrent.value = 1;
            fullDocPages.value = [];
            fetchDocFullContent();
        } else {
            fullDocPages.value = [];
        }
    },
);

// —— Table state ——
const tableHeaders = ref<string[]>([]);
const tableRows = ref<TableRowRecord[]>([]);
const rowsMeta = ref<RowsMeta | null>(null);
const rowsLoading = ref(false);
const rowsError = ref<string | null>(null);
const tableSearch = ref('');
const tablePage = ref(1);
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

// —— Doc append (append from photo/video) ——
const appendDocOpen = ref(false);
const appendDocFile = ref<File | null>(null);
const appendDocLoading = ref(false);
const appendDocProgress = ref(0);
const appendDocPhase = ref<'uploading' | 'extracting'>('uploading');
const appendDocError = ref<string | null>(null);
const appendDocSuccess = ref(false);
const appendDocFileInput = ref<HTMLInputElement | null>(null);
const appendDocCameraPhoto = ref<HTMLInputElement | null>(null);
const appendDocCameraVideo = ref<HTMLInputElement | null>(null);
const ACCEPT_DOC_UPLOAD = 'image/jpeg,image/png,image/gif,image/webp,video/mp4,video/webm';
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
        if (tableRows.value.length === 0 && tablePage.value > 1) {
            tablePage.value--;
            await fetchTableRows();
        }
        closeDeleteRow();
    } catch (e: unknown) {
        const err = e as { response?: { data?: { message?: string } }; message?: string };
        rowsError.value = err.response?.data?.message ?? err.message ?? 'Failed to delete';
    } finally {
        deleteConfirming.value = false;
    }
}

async function deleteSelectedRows(rows: TableRowRecord[]) {
    if (!record.value || !rows.length) return;
    rowsError.value = null;
    try {
        for (const row of rows) {
            await api.delete(`/dashboard/api/data/${record.value.id}/rows/${row.id}`);
        }
        await fetchRecord();
        await fetchTableRows();
        if (tableRows.value.length === 0 && tablePage.value > 1) {
            tablePage.value--;
            await fetchTableRows();
        }
    } catch (e: unknown) {
        const err = e as { response?: { data?: { message?: string } }; message?: string };
        rowsError.value = err.response?.data?.message ?? err.message ?? 'Failed to delete rows';
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
        await api.post<{ added: number; message?: string }>(
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

function openAppendDocDialog() {
    appendDocError.value = null;
    appendDocSuccess.value = false;
    appendDocFile.value = null;
    appendDocOpen.value = true;
}

function closeAppendDocDialog() {
    appendDocOpen.value = false;
    appendDocFile.value = null;
    appendDocError.value = null;
    appendDocSuccess.value = false;
}

function onAppendDocFileChange(e: Event) {
    const input = e.target as HTMLInputElement;
    const file = input.files?.[0];
    if (file) appendDocFile.value = file;
}

function onAppendDocDrop(e: DragEvent) {
    e.preventDefault();
    const file = e.dataTransfer?.files?.[0];
    if (file) appendDocFile.value = file;
}

function openAppendDocFilePicker() {
    appendDocError.value = null;
    appendDocSuccess.value = false;
    appendDocFileInput.value?.click();
}

function openAppendDocCameraPhoto() {
    appendDocError.value = null;
    appendDocSuccess.value = false;
    appendDocCameraPhoto.value?.click();
}

function openAppendDocCameraVideo() {
    appendDocError.value = null;
    appendDocSuccess.value = false;
    appendDocCameraVideo.value?.click();
}

async function submitAppendDoc() {
    const file = appendDocFile.value;
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
        appendDocError.value = 'Allowed: images (JPEG, PNG, GIF, WebP) or video (MP4, WebM).';
        return;
    }
    if (file.size > 20 * 1024 * 1024) {
        appendDocError.value = 'File must be under 20 MB.';
        return;
    }
    appendDocLoading.value = true;
    appendDocProgress.value = 0;
    appendDocPhase.value = 'uploading';
    appendDocError.value = null;
    appendDocSuccess.value = false;
    const formData = new FormData();
    formData.append('file', file);
    try {
        await api.post<{ added: number; message?: string }>(
            `/dashboard/api/data/${record.value.id}/append-doc`,
            formData,
            {
                timeout: 120000,
                onUploadProgress(ev: { loaded: number; total?: number }) {
                    if (ev.total && ev.total > 0) {
                        appendDocProgress.value = Math.round((ev.loaded / ev.total) * 100);
                        if (appendDocProgress.value >= 100) appendDocPhase.value = 'extracting';
                    }
                },
            },
        );
        appendDocProgress.value = 100;
        appendDocPhase.value = 'extracting';
        appendDocSuccess.value = true;
        appendDocFile.value = null;
        if (appendDocFileInput.value) appendDocFileInput.value.value = '';
        if (appendDocCameraPhoto.value) appendDocCameraPhoto.value.value = '';
        if (appendDocCameraVideo.value) appendDocCameraVideo.value.value = '';
        await fetchRecord();
        await fetchDocFullContent();
        if (isMultiPageDoc.value) {
            docPageCurrent.value = docPageCount.value;
            nextTick(() => {
                document.getElementById(`doc-section-${docPageCount.value}`)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        }
        setTimeout(() => { appendDocSuccess.value = false; }, 3000);
    } catch (e: unknown) {
        const err = e as { response?: { data?: { message?: string } }; message?: string };
        appendDocError.value = err.response?.data?.message ?? err.message ?? 'Failed to append to document';
    } finally {
        appendDocLoading.value = false;
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
function goToTablePage(page: number) {
    const last = rowsMeta.value?.last_page ?? 1;
    tablePage.value = Math.max(1, Math.min(last, page));
}

watch(tablePage, () => fetchTableRows());
watch(tablePerPage, () => {
    tablePage.value = 1;
    fetchTableRows();
});
watch(tableSearch, () => {
    if (searchDebounce) clearTimeout(searchDebounce);
    searchDebounce = setTimeout(() => {
        tablePage.value = 1;
        fetchTableRows();
    }, 300);
});

// —— Ask AI ——
const messages = ref<ChatMessage[]>([]);
const askLoading = ref(false);
const askError = ref<string | null>(null);
const savedChats = ref<SavedChat[]>([]);
const saveChatLoading = ref(false);
const savedChatError = ref<string | null>(null);
/** When set, autosave updates this chat; when null, autosave creates a new one. */
const currentChatId = ref<number | null>(null);

function getCsrfToken(): string {
    const meta = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (meta) return meta;
    const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
    return match ? decodeURIComponent(match[1]) : '';
}

async function askAi(prompt?: string) {
    const q = (prompt ?? '').trim();
    if (!q || !record.value) return;
    askLoading.value = true;
    askError.value = null;
    messages.value.push({ role: 'user', content: q });
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
        if (!reader) throw new Error('No response body');
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
                        const parsed = JSON.parse(data) as { content?: string; delta?: string; text?: string; data_updated?: boolean; view_data_url?: string };
                        const chunk =
                            parsed.content ?? parsed.delta ?? parsed.text ?? (typeof parsed === 'string' ? parsed : '');
                        if (chunk && typeof chunk === 'string') {
                            const last = messages.value[assistantIndex];
                            if (last && last.role === 'assistant') last.content += chunk;
                        }
                        if (parsed.view_data_url) {
                            const last = messages.value[assistantIndex];
                            if (last && last.role === 'assistant') (last as ChatMessage).view_data_url = parsed.view_data_url;
                        }
                        if (parsed.data_updated && record.value) {
                            await fetchRecord();
                            if (isTableData.value) await fetchTableRows();
                            if (isDocData.value) await fetchDocFullContent();
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
        }
        if (buffer.startsWith('data: ')) {
            const data = buffer.slice(6).trim();
            if (data && data !== '[DONE]') {
                try {
                    const parsed = JSON.parse(data) as { content?: string; delta?: string; text?: string; data_updated?: boolean; view_data_url?: string };
                    const chunk =
                        parsed.content ?? parsed.delta ?? parsed.text ?? (typeof parsed === 'string' ? parsed : '');
                    if (chunk && typeof chunk === 'string') {
                        const last = messages.value[assistantIndex];
                        if (last && last.role === 'assistant') last.content += chunk;
                    }
                    if (parsed.view_data_url) {
                        const last = messages.value[assistantIndex];
                        if (last && last.role === 'assistant') (last as ChatMessage).view_data_url = parsed.view_data_url;
                    }
                    if (parsed.data_updated && record.value) {
                        await fetchRecord();
                        if (isTableData.value) await fetchTableRows();
                        if (isDocData.value) await fetchDocFullContent();
                    }
                } catch {
                    const last = messages.value[assistantIndex];
                    if (last && last.role === 'assistant') last.content += data;
                }
            }
        }
        autosaveChat();
    } catch (e: unknown) {
        const err = e as Error;
        askError.value = err.message ?? 'Request failed';
        messages.value.pop();
        const last = messages.value[messages.value.length - 1];
        if (last?.role === 'assistant' && last.content === '') messages.value.pop();
    } finally {
        askLoading.value = false;
    }
}

async function autosaveChat() {
    if (!record.value || messages.value.length < 2) return;
    saveChatLoading.value = true;
    savedChatError.value = null;
    try {
        const payload = { messages: messages.value };
        if (currentChatId.value != null) {
            const { data } = await api.patch<SavedChat>(
                `/dashboard/api/data/${record.value.id}/saved-chats/${currentChatId.value}`,
                payload,
            );
            const idx = savedChats.value.findIndex((c) => c.id === data.id);
            if (idx >= 0) savedChats.value.splice(idx, 1, data);
        } else {
            const { data } = await api.post<SavedChat>(
                `/dashboard/api/data/${record.value.id}/saved-chats`,
                payload,
            );
            currentChatId.value = data.id;
            savedChats.value = [data, ...savedChats.value];
        }
    } catch {
        savedChatError.value = 'Autosave failed';
    } finally {
        saveChatLoading.value = false;
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

// —— Copy ——
const copyDocFeedback = ref(false);
const copyTableFeedback = ref(false);
const copyDocTooltipOpen = ref<boolean | undefined>(undefined);
const copyTableTooltipOpen = ref<boolean | undefined>(undefined);
const COPY_FEEDBACK_MS = 2500;

async function copyDocToClipboard() {
    if (!record.value || !isDocData.value) return;
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

function exportToPdf(docContentOverride?: string) {
    if (!record.value) return;
    const doc = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'a4' });
    const name = record.value.name || 'export';
    const title = name.slice(0, 80);

    if (isTableData.value) {
        const t = tableData.value;
        if (!t?.headers?.length) return;
        const headers = t.headers as string[];
        const rows = (t.rows ?? []).map((row: unknown[]) =>
            (row ?? []).map((cell) => (cell != null ? String(cell) : '')),
        );
        doc.setFontSize(14);
        doc.text(title, 14, 16);
        autoTable(doc, {
            head: [headers],
            body: rows,
            startY: 22,
            styles: { fontSize: 9 },
            headStyles: { fillColor: [100, 100, 100] },
        });
    } else if (isDocData.value) {
        const content = docContentOverride ?? docContent.value ?? '';
        if (content) {
            doc.setFontSize(14);
            doc.text(title, 14, 16);
            const pageWidth = doc.internal.pageSize.getWidth() - 28;
            const lines = doc.splitTextToSize(content, pageWidth);
            doc.setFontSize(10);
            let y = 24;
            for (const line of lines) {
                if (y > doc.internal.pageSize.getHeight() - 20) {
                    doc.addPage();
                    y = 20;
                }
                doc.text(line, 14, y);
                y += 6;
            }
        } else {
            doc.setFontSize(14);
            doc.text(title, 14, 20);
            doc.setFontSize(10);
            doc.text('(Content loaded on demand — use Export to Doc for full text.)', 14, 30);
        }
    } else {
        doc.setFontSize(14);
        doc.text(title, 14, 20);
        doc.setFontSize(10);
        doc.text('No content to export as PDF.', 14, 30);
    }

    doc.save(`${name}.pdf`);
}

async function exportToPdfAsync() {
    if (!record.value) return;
    if (isDocData.value && isMultiPageDoc.value) {
        const { data } = await api.get<{ content: string }>(
            `/dashboard/api/data/${record.value.id}/doc-content`,
        );
        exportToPdf(data.content ?? '');
    } else {
        exportToPdf();
    }
}

// —— Charts ——
const chartRequest = ref('');
const showSpecificChartRequest = ref(false);
const chartSuggestion = ref<ChartSuggestion | null>(null);
const chartSuggestionLoading = ref(false);
const savedCharts = ref<SavedChart[]>([]);
const saveChartLoading = ref(false);
const savedChartError = ref<string | null>(null);

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

function startNewChat() {
    messages.value = [];
    askError.value = null;
    currentChatId.value = null;
}

async function saveCurrentChat(name?: string) {
    if (!record.value || messages.value.length === 0) return;
    saveChatLoading.value = true;
    savedChatError.value = null;
    try {
        const { data } = await api.post<SavedChat>(
            `/dashboard/api/data/${record.value.id}/saved-chats`,
            { name: name?.trim() || null, messages: messages.value },
        );
        savedChats.value = [data, ...savedChats.value];
    } catch (e: unknown) {
        const err = e as { response?: { data?: { message?: string } }; message?: string };
        savedChatError.value = err.response?.data?.message ?? err.message ?? 'Failed to save chat';
    } finally {
        saveChatLoading.value = false;
    }
}

function loadSavedChat(chat: SavedChat) {
    messages.value = Array.isArray(chat.messages) ? [...chat.messages] : [];
    currentChatId.value = chat.id;
}

async function deleteSavedChat(chat: SavedChat) {
    if (!record.value) return;
    try {
        await api.delete(`/dashboard/api/data/${record.value.id}/saved-chats/${chat.id}`);
        savedChats.value = savedChats.value.filter((c) => c.id !== chat.id);
        if (currentChatId.value === chat.id) currentChatId.value = null;
    } catch {
        await fetchSavedChats();
    }
}

function startNewChart() {
    chartSuggestion.value = null;
    chartRequest.value = '';
    showSpecificChartRequest.value = false;
}

async function saveCurrentChart() {
    if (!record.value || !chartSuggestion.value) return;
    saveChartLoading.value = true;
    savedChartError.value = null;
    try {
        const { data } = await api.post<SavedChart>(
            `/dashboard/api/data/${record.value.id}/saved-charts`,
            { name: null, chart_config: chartSuggestion.value },
        );
        savedCharts.value = [data, ...savedCharts.value];
    } catch (e: unknown) {
        const err = e as { response?: { data?: { message?: string } }; message?: string };
        savedChartError.value = err.response?.data?.message ?? err.message ?? 'Failed to save chart';
    } finally {
        saveChartLoading.value = false;
    }
}

function loadSavedChart(chart: SavedChart) {
    const cfg = chart.chart_config;
    if (cfg && typeof cfg.chartType === 'string' && typeof cfg.labelColumn === 'number' && typeof cfg.valueColumn === 'number') {
        chartSuggestion.value = {
            chartType: cfg.chartType === 'line' || cfg.chartType === 'pie' ? cfg.chartType : 'bar',
            labelColumn: cfg.labelColumn,
            valueColumn: cfg.valueColumn,
            title: cfg.title ?? null,
        };
    }
}

async function deleteSavedChart(chart: SavedChart) {
    if (!record.value) return;
    try {
        await api.delete(`/dashboard/api/data/${record.value.id}/saved-charts/${chart.id}`);
        savedCharts.value = savedCharts.value.filter((c) => c.id !== chart.id);
    } catch {
        await fetchSavedCharts();
    }
}

const canChart = computed(
    () =>
        tableData.value &&
        (tableData.value.headers?.length ?? 0) >= 2 &&
        (tableData.value.rows?.length ?? 0) > 0,
);
const canExportExcel = computed(() => !!tableData.value && !!record.value);
const canExportPdf = computed(() => (!!tableData.value || isDocData.value) && !!record.value);

/** Human-readable label for the AI model used when this data was extracted (and for Ask AI / Charts). */
const aiModelLabel = computed(() => {
    const r = record.value;
    if (!r) return '';
    const p = r.ai_provider?.trim();
    const m = r.ai_model?.trim();
    if (p && m) return `${p} · ${m}`;
    if (m) return m;
    if (p) return p;
    return '';
});
</script>

<template>
    <Head :title="record?.name ?? 'Data'" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <TooltipProvider :delay-duration="300">
            <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl px-3 py-4 sm:p-4">
                <div class="flex items-center gap-4">
                    <Link
                        :href="backHref"
                        class="min-h-[44px] shrink-0 py-2 text-sm text-muted-foreground underline-offset-4 hover:underline sm:min-h-0"
                    >
                        ← {{ backLabel }}
                    </Link>
                </div>

                <div
                    v-if="!loading && !error && record"
                    class="min-w-0 rounded-xl border border-sidebar-border/70 bg-card shadow-sm dark:border-sidebar-border"
                >
                    <div class="border-b border-sidebar-border/70 px-3 pt-4 dark:border-sidebar-border sm:px-4">
                        <DataShowHeader
                            :record="record"
                            @update:name="(name) => { if (record) record.name = name }"
                        />

                        <div
                            v-if="isProcessing"
                            class="mx-3 mb-2 flex items-center gap-2 rounded-lg bg-primary/10 px-3 py-2 text-sm text-primary sm:mx-4"
                        >
                            <span class="shrink-0">Extracting…</span>
                            <span v-if="processingBatches" class="tabular-nums">
                                {{ processingBatches.done }}/{{ processingBatches.total }} batches — content will update as more is extracted.
                            </span>
                            <span v-else>Content will update shortly.</span>
                        </div>

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
                                    <DataShowDocView
                                        v-if="isDocData"
                                        :doc-search="docSearch"
                                        :doc-sections="fullDocPages"
                                        :displayed-content="displayedDocContentFiltered"
                                        :doc-page-count="docPageCount"
                                        :doc-page-current="docPageCurrent"
                                        :is-multi-page="fullDocPages.length > 1"
                                        :doc-page-loading="fullDocLoading"
                                        :doc-page-error="docPageError"
                                        :doc-editing="docEditing"
                                        :doc-edit-content="docEditContent"
                                        :doc-edit-saving="docEditSaving"
                                        :doc-edit-error="docEditError"
                                        :copy-feedback="copyDocFeedback"
                                        :copy-tooltip-open="copyDocTooltipOpen"
                                        @update:doc-search="docSearch = $event"
                                        @start-edit="startDocEdit"
                                        @cancel-edit="cancelDocEdit"
                                        @save-edit="saveDocEdit"
                                        @go-to-page="goToDocPage"
                                        @copy="copyDocToClipboard"
                                        @open-append-doc="openAppendDocDialog"
                                        @update:copy-tooltip-open="(v) => { if (!copyDocFeedback) copyDocTooltipOpen = v === false ? undefined : v }"
                                        @update:doc-edit-content="docEditContent = $event"
                                    />
                                    <DataShowTableView
                                        v-else-if="tableData"
                                        :table-search="tableSearch"
                                        :table-headers="tableHeaders"
                                        :table-rows="tableRows"
                                        :rows-meta="rowsMeta"
                                        :rows-loading="rowsLoading"
                                        :rows-error="rowsError"
                                        :table-page="tablePage"
                                        :table-per-page="tablePerPage"
                                        :copy-feedback="copyTableFeedback"
                                        :copy-tooltip-open="copyTableTooltipOpen"
                                        @update:table-search="tableSearch = $event"
                                        @update:table-page="tablePage = $event"
                                        @update:table-per-page="tablePerPage = $event"
                                        @go-to-page="goToTablePage"
                                        @add-rows="openAddRow"
                                        @copy="copyTableToClipboard"
                                        @edit-row="openEditRow"
                                        @delete-row="openDeleteRow"
                                        @delete-selected-rows="deleteSelectedRows"
                                        @update:copy-tooltip-open="(v) => { if (!copyTableFeedback) copyTableTooltipOpen = v === false ? undefined : v }"
                                    />
                                    <p v-else class="text-muted-foreground">
                                        No content to display.
                                    </p>
                                </div>
                            </TabsContent>

                            <TabsContent value="ask" class="mt-0 rounded-b-xl">
                                <DataShowAskAi
                                    :record-name="record?.name ?? ''"
                                    :ai-model-label="aiModelLabel"
                                    :messages="messages"
                                    :suggested-prompts="suggestedPrompts"
                                    :insights="insights"
                                    :ask-loading="askLoading"
                                    :ask-error="askError"
                                    :saved-chats="savedChats"
                                    :save-chat-loading="saveChatLoading"
                                    :saved-chat-error="savedChatError"
                                    :can-save-chat="messages.length > 0"
                                    @ask="askAi"
                                    @new-chat="startNewChat"
                                    @save-chat="saveCurrentChat"
                                    @load-chat="loadSavedChat"
                                    @delete-chat="deleteSavedChat"
                                />
                            </TabsContent>

                            <TabsContent value="charts" class="mt-0 rounded-b-xl">
                                <DataShowCharts
                                    :table-data="tableData"
                                    :ai-model-label="aiModelLabel"
                                    :chart-suggestion="chartSuggestion"
                                    :chart-request="chartRequest"
                                    :show-specific-chart-request="showSpecificChartRequest"
                                    :chart-suggestion-loading="chartSuggestionLoading"
                                    :can-chart="!!canChart"
                                    :saved-charts="savedCharts"
                                    :save-chart-loading="saveChartLoading"
                                    :saved-chart-error="savedChartError"
                                    :can-save-chart="!!chartSuggestion"
                                    @update:chart-request="chartRequest = $event"
                                    @update:show-specific-chart-request="showSpecificChartRequest = $event"
                                    @suggest-chart="suggestChartFromAi"
                                    @new-chart="startNewChart"
                                    @save-chart="saveCurrentChart"
                                    @load-chart="loadSavedChart"
                                    @delete-chart="deleteSavedChart"
                                />
                            </TabsContent>

                            <TabsContent value="export" class="mt-0 rounded-b-xl">
                                <DataShowExport
                                    :is-table-data="isTableData"
                                    :is-doc-data="isDocData"
                                    :can-export-excel="canExportExcel"
                                    :can-export-doc="canExportDoc"
                                    :can-export-pdf="canExportPdf"
                                    @export-excel="exportToExcel"
                                    @export-json="exportToJson"
                                    @export-doc="exportToDoc"
                                    @export-pdf="exportToPdfAsync"
                                />
                            </TabsContent>
                        </TabsRoot>

                        <DataEditRowDialog
                            :open="editRowOpen"
                            :edit-row="editRow"
                            :table-headers="tableHeaders"
                            :edit-cells="editCells"
                            :edit-saving="editSaving"
                            @update:open="editRowOpen = $event"
                            @update:edit-cells="editCells = $event"
                            @save="saveEditRow"
                            @close="closeEditRow"
                        />

                        <DataAddRowsDialog
                            :open="addRowOpen"
                            :table-headers="tableHeaders"
                            :add-row-cells="addRowCells"
                            :add-row-saving="addRowSaving"
                            :add-rows-tab="addRowsTab"
                            :append-file="appendFile"
                            :append-loading="appendLoading"
                            :append-progress="appendProgress"
                            :append-phase="appendPhase"
                            :append-error="appendError"
                            :append-success="appendSuccess"
                            @update:open="addRowOpen = $event"
                            @update:add-row-cells="addRowCells = $event"
                            @update:add-rows-tab="addRowsTab = $event"
                            @save-add-row="saveAddRow"
                            @close="closeAddRow"
                            @append-file-change="onAppendFileChange"
                            @append-drop="onAppendDrop"
                            @open-append-file-picker="openAppendFilePicker"
                            @open-append-camera-photo="openAppendCameraPhoto"
                            @open-append-camera-video="openAppendCameraVideo"
                            @submit-append-upload="submitAppendUpload"
                        >
                            <template #file-inputs>
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
                            </template>
                        </DataAddRowsDialog>

                        <DataDeleteRowDialog
                            :open="deleteRowOpen"
                            :row-to-delete="rowToDelete"
                            :delete-confirming="deleteConfirming"
                            @update:open="deleteRowOpen = $event"
                            @confirm="confirmDeleteRow"
                            @close="closeDeleteRow"
                        />

                        <DataAppendDocDialog
                            v-if="isDocData"
                            :open="appendDocOpen"
                            :append-file="appendDocFile"
                            :append-loading="appendDocLoading"
                            :append-progress="appendDocProgress"
                            :append-phase="appendDocPhase"
                            :append-error="appendDocError"
                            :append-success="appendDocSuccess"
                            @update:open="appendDocOpen = $event"
                            @append-file-change="onAppendDocFileChange"
                            @append-drop="onAppendDocDrop"
                            @open-append-file-picker="openAppendDocFilePicker"
                            @open-append-camera-photo="openAppendDocCameraPhoto"
                            @open-append-camera-video="openAppendDocCameraVideo"
                            @submit-append-doc="submitAppendDoc"
                            @close="closeAppendDocDialog"
                        >
                            <template #file-inputs>
                                <input
                                    ref="appendDocFileInput"
                                    type="file"
                                    :accept="ACCEPT_DOC_UPLOAD"
                                    class="hidden"
                                    @change="onAppendDocFileChange"
                                />
                                <input
                                    ref="appendDocCameraPhoto"
                                    type="file"
                                    accept="image/*"
                                    capture="environment"
                                    class="hidden"
                                    aria-label="Take a picture"
                                    @change="onAppendDocFileChange"
                                />
                                <input
                                    ref="appendDocCameraVideo"
                                    type="file"
                                    accept="video/*"
                                    capture="environment"
                                    class="hidden"
                                    aria-label="Record video"
                                    @change="onAppendDocFileChange"
                                />
                            </template>
                        </DataAppendDocDialog>
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
