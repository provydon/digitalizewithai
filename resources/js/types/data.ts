export type DigitalizedItem = {
    id: number;
    name: string;
    type: string | null;
    /** Display status: ready | processing | failed */
    status?: 'ready' | 'processing' | 'failed';
    processing?: boolean;
    processing_batches_done?: number | null;
    processing_batches_total?: number | null;
    ai_provider: string | null;
    ai_model: string | null;
    /** How long extraction took, in seconds (set when ready). */
    extraction_duration_seconds?: number | null;
    /** When extraction started (ISO string). End time = started + duration_seconds. */
    extraction_started_at?: string | null;
    created_at: string | null;
};

export type DataListMeta = {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
};
