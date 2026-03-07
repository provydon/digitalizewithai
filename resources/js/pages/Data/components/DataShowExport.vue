<script setup lang="ts">
import { FileJson, FileOutput, FileSpreadsheet, FileText } from 'lucide-vue-next';

defineProps<{
    isTableData: boolean;
    isDocData: boolean;
    canExportExcel: boolean;
    canExportDoc: boolean;
    canExportPdf: boolean;
}>();

const emit = defineEmits<{
    'export-excel': [];
    'export-json': [];
    'export-doc': [];
    'export-pdf': [];
}>();
</script>

<template>
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
                @click="emit('export-excel')"
            >
                <FileSpreadsheet class="h-4 w-4 shrink-0" />
                Export to Excel
            </button>
            <button
                v-if="isTableData"
                type="button"
                class="inline-flex min-h-[44px] w-full items-center justify-center gap-2 rounded-lg border border-sidebar-border/70 bg-muted/30 px-3 py-2.5 text-sm font-medium text-foreground hover:bg-muted/60 dark:border-sidebar-border disabled:opacity-50 sm:w-auto sm:py-2"
                :disabled="!canExportExcel"
                @click="emit('export-json')"
            >
                <FileJson class="h-4 w-4 shrink-0" />
                Export to JSON
            </button>
            <button
                v-if="isDocData"
                type="button"
                class="inline-flex min-h-[44px] w-full items-center justify-center gap-2 rounded-lg border border-sidebar-border/70 bg-muted/30 px-3 py-2.5 text-sm font-medium text-foreground hover:bg-muted/60 dark:border-sidebar-border disabled:opacity-50 sm:w-auto sm:py-2"
                :disabled="!canExportDoc"
                @click="emit('export-doc')"
            >
                <FileText class="h-4 w-4 shrink-0" />
                Export to Doc
            </button>
            <button
                v-if="isTableData || isDocData"
                type="button"
                class="inline-flex min-h-[44px] w-full items-center justify-center gap-2 rounded-lg border border-sidebar-border/70 bg-muted/30 px-3 py-2.5 text-sm font-medium text-foreground hover:bg-muted/60 dark:border-sidebar-border disabled:opacity-50 sm:w-auto sm:py-2"
                :disabled="!canExportPdf"
                @click="emit('export-pdf')"
            >
                <FileOutput class="h-4 w-4 shrink-0" />
                Export as PDF
            </button>
        </div>
        <p
            v-if="!isTableData && !isDocData"
            class="text-sm text-muted-foreground"
        >
            No content to export.
        </p>
    </div>
</template>
