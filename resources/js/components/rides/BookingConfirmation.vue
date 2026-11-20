<script setup lang="ts">
import { Button } from '@/components/ui/button';
import BudgetSummary from '@/components/wmo/BudgetSummary.vue';
import { dateTime, kilometers } from '@/lib/wmo';
import type { Ride } from '@/types/wmo';
import { Link } from '@inertiajs/vue3';
defineProps<{ ride: Ride }>();
</script>

<template>
    <section class="space-y-6" aria-label="Booking confirmation" role="status">
        <div
            class="rounded-xl border border-emerald-300 bg-emerald-50 p-6 text-emerald-950 dark:border-emerald-800 dark:bg-emerald-950 dark:text-emerald-100"
        >
            <h2 class="text-xl font-semibold">Ride #{{ ride.id }} confirmed</h2>
            <p class="mt-2">
                {{ ride.taxi_company.name }} · {{ dateTime(ride.scheduled_at) }} (Amsterdam) · {{ kilometers(ride.distance_meters) }} km
            </p>
            <p class="mt-3 text-sm">{{ ride.origin }} → {{ ride.destination }}</p>
        </div>
        <BudgetSummary v-if="ride.budget" :budget="ride.budget" title="Updated annual allowance" />
        <div class="flex flex-wrap gap-3">
            <Button as-child><Link :href="`/residents/${ride.resident.id}`">View resident</Link></Button
            ><Button as-child variant="outline"><Link href="/book-ride">Book another ride</Link></Button>
        </div>
    </section>
</template>
