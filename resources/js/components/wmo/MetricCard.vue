<script setup lang="ts">
defineProps<{ label: string; value: string | number; description?: string }>();
</script>

<template>
    <div class="rounded-xl border bg-card p-6">
        <p class="text-sm text-muted-foreground">{{ label }}</p>
        <p class="mt-3 text-3xl font-semibold tracking-tight">{{ value }}</p>
        <p v-if="description" class="mt-2 text-xs text-muted-foreground">{{ description }}</p>
    </div>
</template>
