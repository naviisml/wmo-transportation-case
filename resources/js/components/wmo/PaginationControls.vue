<script setup lang="ts">
import { Button } from '@/components/ui/button';
defineProps<{ page: number; lastPage: number; total: number; pending: boolean; label: string }>();
defineEmits<{ change: [page: number] }>();
</script>

<template>
    <nav aria-label="Pagination" class="flex flex-wrap items-center justify-between gap-3 text-sm">
        <span>{{ total }} {{ label }} · Page {{ page }} of {{ lastPage }}</span>
        <div class="flex gap-2">
            <Button variant="outline" :disabled="pending || page <= 1" @click="$emit('change', page - 1)">Previous</Button>
            <Button variant="outline" :disabled="pending || page >= lastPage" @click="$emit('change', page + 1)">Next</Button>
        </div>
    </nav>
</template>
