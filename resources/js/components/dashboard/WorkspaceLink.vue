<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowRight } from 'lucide-vue-next';
defineProps<{ href: string; title: string; description: string }>();
</script>

<template>
    <Link
        :href="href"
        :aria-label="title"
        class="group flex items-start justify-between gap-4 rounded-xl border bg-card p-6 transition-colors hover:border-emerald-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
    >
        <div>
            <h2 class="font-semibold">{{ title }}</h2>
            <p class="mt-2 text-sm text-muted-foreground">{{ description }}</p>
        </div>
        <ArrowRight class="mt-1 h-5 w-5 shrink-0 text-emerald-600 transition-transform group-hover:translate-x-1" />
    </Link>
</template>
