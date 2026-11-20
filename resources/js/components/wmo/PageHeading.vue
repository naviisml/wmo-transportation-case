<script setup lang="ts">
defineProps<{ title: string; description: string }>();
</script>

<template>
    <header class="flex flex-wrap items-start justify-between gap-4 border-b pb-6">
        <div>
            <p class="mb-2 text-xs font-semibold uppercase tracking-[.2em] text-emerald-700 dark:text-emerald-400">Utrecht · Wmo transport</p>
            <h1 class="text-3xl font-semibold tracking-tight">{{ title }}</h1>
            <p class="mt-2 max-w-2xl text-sm text-muted-foreground">{{ description }}</p>
        </div>
        <div v-if="$slots.actions" class="flex flex-wrap gap-2"><slot name="actions" /></div>
    </header>
</template>
