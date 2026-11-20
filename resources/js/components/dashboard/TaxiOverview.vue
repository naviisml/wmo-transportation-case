<script setup lang="ts">
import MetricCard from '@/components/wmo/MetricCard.vue';
import type { DashboardSummary } from '@/types/wmo';
import WorkspaceLink from './WorkspaceLink.vue';
defineProps<{ summary: DashboardSummary }>();
</script>

<template>
    <div class="grid gap-4 sm:grid-cols-2">
        <MetricCard label="Assigned rides" :value="summary.rides_total" description="Only rides belonging to your organization" />
        <MetricCard label="Upcoming rides" :value="summary.upcoming_rides" description="Booked journeys scheduled from now onward" />
    </div>
    <WorkspaceLink
        href="/rides"
        title="View rides"
        description="Open your organization's schedule and filter by date or status. Times are displayed in Europe/Amsterdam."
    />
</template>
