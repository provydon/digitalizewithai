<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { FileText, Table as TableIcon, Trash2 } from 'lucide-vue-next';
import type { DigitalizedItem } from '@/types';

const props = defineProps<{
    items: DigitalizedItem[];
    /** Function to get view URL for an item, e.g. (id) => `/dashboard/data/${id}` */
    viewUrl: (id: number) => string;
}>();

const emit = defineEmits<{
    deleteRequest: [item: DigitalizedItem];
}>();

function formatDate(iso: string | null): string {
    if (!iso) return '—';
    const d = new Date(iso);
    if (Number.isNaN(d.getTime())) return '—';
    return d.toLocaleString();
}

function onRowClick(item: DigitalizedItem) {
    router.visit(props.viewUrl(item.id));
}
</script>

<template>
    <div class="-mx-1 overflow-x-auto overscroll-x-contain sm:mx-0">
        <table class="w-full min-w-[320px] text-left text-sm text-foreground sm:min-w-[400px]" role="grid">
            <thead>
                <tr class="border-b border-border">
                    <th class="pb-3 pr-3 font-medium text-muted-foreground sm:pr-4">ID</th>
                    <th class="pb-3 pr-3 font-medium text-muted-foreground sm:pr-4">Name</th>
                    <th class="pb-3 pr-3 font-medium text-muted-foreground sm:pr-4">Type</th>
                    <th class="hidden pb-3 pr-3 font-medium text-muted-foreground md:table-cell sm:pr-4">AI</th>
                    <th class="hidden pb-3 pr-3 font-medium text-muted-foreground sm:table-cell sm:pr-4">Created</th>
                    <th class="w-10 pb-3 pl-0 font-medium text-muted-foreground" aria-label="Actions"> </th>
                </tr>
            </thead>
            <tbody>
                <tr
                    v-for="(item, index) in items"
                    :key="item.id"
                    class="group border-b border-border/80 transition-colors last:border-b-0 hover:bg-muted/40 cursor-pointer"
                    :class="{ 'bg-muted/20': index % 2 === 1 }"
                    @click="onRowClick(item)"
                >
                    <td class="py-3.5 pr-3 font-mono text-xs text-muted-foreground sm:pr-4">
                        {{ item.id }}
                    </td>
                    <td class="py-3.5 pr-3 sm:pr-4">
                        <Link
                            :href="viewUrl(item.id)"
                            class="font-medium text-foreground underline-offset-4 hover:underline"
                            @click.stop
                        >
                            {{ item.name }}
                        </Link>
                    </td>
                    <td class="py-3.5 pr-3 sm:pr-4">
                        <span
                            v-if="item.type === 'doc'"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-muted px-2.5 py-1 text-xs font-medium text-muted-foreground"
                        >
                            <FileText class="h-3.5 w-3.5" />
                            doc
                        </span>
                        <span
                            v-else-if="item.type === 'table'"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-muted px-2.5 py-1 text-xs font-medium text-muted-foreground"
                        >
                            <TableIcon class="h-3.5 w-3.5" />
                            table
                        </span>
                        <span v-else class="text-muted-foreground">—</span>
                    </td>
                    <td class="hidden py-3.5 pr-3 text-muted-foreground md:table-cell sm:pr-4">
                        <span v-if="item.ai_provider || item.ai_model" class="text-xs">
                            {{ [item.ai_provider, item.ai_model].filter(Boolean).join(' · ') }}
                        </span>
                        <span v-else class="text-muted-foreground">—</span>
                    </td>
                    <td class="hidden py-3.5 pr-3 text-muted-foreground sm:table-cell sm:pr-4">
                        {{ formatDate(item.created_at) }}
                    </td>
                    <td class="w-10 py-3.5 pl-0">
                        <button
                            type="button"
                            class="rounded-lg p-2 text-muted-foreground opacity-70 transition-opacity hover:bg-destructive/10 hover:text-destructive hover:opacity-100"
                            title="Delete"
                            aria-label="Delete this item"
                            @click.stop="emit('deleteRequest', item)"
                        >
                            <Trash2 class="h-4 w-4" />
                        </button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
