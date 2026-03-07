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
    created_at: string | null;
};

export type DataListMeta = {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
};
