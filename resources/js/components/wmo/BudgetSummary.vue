<script setup lang="ts">
import { kilometers } from '@/lib/wmo';
import type { BudgetTotals } from '@/types/wmo';
import { computed } from 'vue';
const props = defineProps<{ budget: BudgetTotals; title: string }>();
const percentage = computed(() =>
    props.budget.allocated_meters ? Math.round((props.budget.consumed_meters / props.budget.allocated_meters) * 100) : 0,
);
</script>

<template>
    <section class="rounded-xl border p-6">
        <h2 class="font-semibold">{{ title }}</h2>
        <div class="mt-5 flex flex-wrap items-end justify-between gap-3">
            <p class="text-3xl font-semibold">
                {{ kilometers(budget.remaining_meters) }} <span class="text-sm font-normal text-muted-foreground">km remaining</span>
            </p>
            <p class="text-sm text-muted-foreground">{{ percentage }}% consumed</p>
        </div>
        <progress
            class="mt-4 h-2 w-full accent-emerald-600"
            :value="budget.consumed_meters"
            :max="budget.allocated_meters || 1"
            :aria-label="`${title}: ${percentage}% consumed`"
        />
        <p class="mt-3 text-sm text-muted-foreground">
            {{ kilometers(budget.consumed_meters) }} km used of {{ kilometers(budget.allocated_meters) }} km allocated.
        </p>
    </section>
</template>
