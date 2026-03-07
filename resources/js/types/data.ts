export type DigitalizedItem = {
    id: number;
    name: string;
    type: string | null;
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
