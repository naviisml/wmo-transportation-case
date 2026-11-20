<script setup lang="ts">
import MetricCard from '@/components/wmo/MetricCard.vue';
import type { DashboardSummary } from '@/types/wmo';
import WorkspaceLink from './WorkspaceLink.vue';
defineProps<{ summary: DashboardSummary }>();
</script>

<template>
    <div class="grid gap-4 sm:grid-cols-3">
        <MetricCard label="Active authorizations" :value="summary.active_authorizations ?? 0" description="Valid today" />
        <MetricCard label="Transport companies" :value="summary.taxi_companies ?? 0" description="Companies in the assessment dataset" />
        <MetricCard label="Upcoming rides" :value="summary.upcoming_rides" description="Across the transport program" />
    </div>
    <WorkspaceLink
        href="/municipality"
        title="Municipal overview"
        description="Review authorization totals, annual allowance usage and parcel coverage. This page contains aggregate figures without resident records."
    />
</template>
