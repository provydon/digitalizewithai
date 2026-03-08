/** Shared types for Data show page and its components. */

export type DigitalData = {
    type: string;
    content?: string | null;
    doc_page_count?: number;
    table_row_count?: number;
    suggested_prompts?: string[];
    insights?: string[];
    status?: string;
    processing_batches_done?: number;
    processing_batches_total?: number;
    error?: string;
};

export type DataRecord = {
    id: number;
    name: string;
    raw_data: Record<string, unknown> | null;
    digital_data: DigitalData | null;
    ai_provider: string | null;
    ai_model: string | null;
    created_at: string | null;
    updated_at: string | null;
    has_original_file?: boolean;
};

export type TableRowRecord = { id: number; row_index: number; cells: unknown[] };

export type RowsMeta = {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
};

export type ChatMessage = { role: 'user' | 'assistant'; content: string; view_data_url?: string };

export type ChartSuggestion = {
    chartType: 'bar' | 'line' | 'pie';
    labelColumn: number;
    valueColumn: number;
    title: string | null;
};

export type SavedChat = {
    id: number;
    name: string | null;
    messages: ChatMessage[];
    created_at: string | null;
    updated_at: string | null;
};

export type SavedChart = {
    id: number;
    name: string | null;
    chart_config: ChartSuggestion;
    created_at: string | null;
    updated_at: string | null;
};

/** Returns array of page numbers and 'ellipsis' for pill pagination. */
export function paginationSlots(
    current: number,
    total: number,
): (number | 'ellipsis')[] {
    if (total <= 0) return [];
    if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1);
    const slots: (number | 'ellipsis')[] = [1];
    const windowStart = Math.max(2, current - 1);
    const windowEnd = Math.min(total - 1, current + 1);
    if (windowStart > 2) slots.push('ellipsis');
    for (let p = windowStart; p <= windowEnd; p++) {
        if (p !== 1 && p !== total) slots.push(p);
    }
    if (windowEnd < total - 1) slots.push('ellipsis');
    if (total > 1) slots.push(total);
    return slots;
}
