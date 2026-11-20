<script setup lang="ts">
import BudgetSummary from '@/components/wmo/BudgetSummary.vue';
import MetricCard from '@/components/wmo/MetricCard.vue';
import type { DashboardSummary } from '@/types/wmo';
defineProps<{ summary: DashboardSummary }>();
</script>

<template>
    <div class="grid gap-4 sm:grid-cols-3">
        <MetricCard label="Residents" :value="summary.residents_total ?? 0" description="Fictional Wmo-related resident records" />
        <MetricCard label="Active authorizations" :value="summary.active_authorizations ?? 0" description="Valid today in Europe/Amsterdam" />
        <MetricCard label="Your upcoming bookings" :value="summary.upcoming_rides" description="Booked rides scheduled from now onward" />
    </div>
    <BudgetSummary v-if="summary.budget" :budget="summary.budget" title="Active annual allowances" />
</template>
