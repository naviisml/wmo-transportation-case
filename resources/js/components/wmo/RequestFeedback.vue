<script setup lang="ts">
import { LoaderCircle } from 'lucide-vue-next';
withDefaults(defineProps<{ pending: boolean; error: string; status?: number; retryable?: boolean }>(), { retryable: true });
defineEmits<{ retry: [] }>();
</script>

<template>
    <div v-if="error" role="alert" class="rounded-lg border border-red-300 bg-red-50 p-4 text-sm text-red-900">
        {{ error }}
        <a v-if="status === 401 || status === 419" href="/login" class="ml-2 underline">Sign in again</a>
        <button
            v-else-if="retryable && status !== 403 && status !== 404 && status !== 422 && status !== 409"
            type="button"
            class="ml-2 underline"
            @click="$emit('retry')"
        >
            Retry
        </button>
    </div>
    <div v-if="pending" role="status" class="flex items-center gap-2 text-sm text-muted-foreground">
        <LoaderCircle class="h-4 w-4 animate-spin" /> Loading…
    </div>
</template>
