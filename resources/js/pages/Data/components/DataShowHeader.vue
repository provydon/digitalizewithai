<script setup lang="ts">
import { Pencil } from 'lucide-vue-next';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import api from '@/lib/api';
import type { DataRecord } from '../types';

const props = defineProps<{
    record: DataRecord;
}>();

const emit = defineEmits<{
    'update:name': [name: string];
}>();

const nameEditing = ref(false);
const nameEditValue = ref('');
const nameEditSaving = ref(false);
const nameEditError = ref<string | null>(null);

function startNameEdit() {
    nameEditError.value = null;
    nameEditValue.value = props.record.name;
    nameEditing.value = true;
}

function cancelNameEdit() {
    nameEditing.value = false;
    nameEditValue.value = '';
    nameEditError.value = null;
}

async function saveNameEdit() {
    const name = nameEditValue.value.trim();
    if (!name) return;
    nameEditSaving.value = true;
    nameEditError.value = null;
    try {
        const { data } = await api.patch<{ name: string }>(
            `/dashboard/api/data/${props.record.id}`,
            { name },
        );
        emit('update:name', data.name);
        cancelNameEdit();
    } catch (e: unknown) {
        const err = e as { response?: { data?: { message?: string } }; message?: string };
        nameEditError.value = err.response?.data?.message ?? err.message ?? 'Failed to save name';
    } finally {
        nameEditSaving.value = false;
    }
}
</script>

<template>
    <div class="mb-2 flex min-w-0 items-center gap-1.5">
        <template v-if="!nameEditing">
            <h1
                class="min-w-0 truncate text-lg font-semibold text-foreground sm:text-xl"
                @dblclick="startNameEdit"
            >
                {{ record.name }}
            </h1>
            <button
                type="button"
                class="shrink-0 rounded p-1 text-muted-foreground hover:bg-muted/50 hover:text-foreground"
                title="Edit name"
                aria-label="Edit name"
                @click="startNameEdit"
            >
                <Pencil class="h-3.5 w-3.5" />
            </button>
        </template>
        <template v-else>
            <input
                v-model="nameEditValue"
                type="text"
                class="min-w-0 flex-1 rounded-lg border border-border bg-background px-2 py-1 text-lg font-semibold text-foreground focus:outline-none focus:ring-2 focus:ring-ring sm:text-xl"
                placeholder="Name"
                @keydown.enter.prevent="saveNameEdit()"
                @keydown.escape.prevent="cancelNameEdit()"
            />
            <Button
                size="sm"
                class="shrink-0"
                :disabled="nameEditSaving || !nameEditValue.trim()"
                @click="saveNameEdit"
            >
                {{ nameEditSaving ? '…' : 'Save' }}
            </Button>
            <Button
                size="sm"
                variant="secondary"
                class="shrink-0"
                :disabled="nameEditSaving"
                @click="cancelNameEdit"
            >
                Cancel
            </Button>
        </template>
    </div>
    <p v-if="nameEditError" class="mb-1 text-sm text-destructive">
        {{ nameEditError }}
    </p>
    <p class="mb-4 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-muted-foreground">
        <span class="font-mono">ID: {{ record.id }}</span>
        <span v-if="record.created_at">
            Created {{ new Date(record.created_at).toLocaleString() }}
        </span>
    </p>
</template>
