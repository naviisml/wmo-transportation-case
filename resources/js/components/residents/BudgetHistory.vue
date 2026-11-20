<script setup lang="ts">
import { kilometers } from '@/lib/wmo';
import type { Budget } from '@/types/wmo';
defineProps<{ periods: Budget[] }>();
</script>

<template>
    <section class="min-w-0 rounded-xl border">
        <h2 class="p-6 text-lg font-semibold">Annual budget history</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <caption class="sr-only">
                    Annual periods; end dates are exclusive
                </caption>
                <thead class="bg-muted/50 text-muted-foreground">
                    <tr>
                        <th class="p-4">Period (end exclusive)</th>
                        <th class="p-4">Allocated</th>
                        <th class="p-4">Consumed</th>
                        <th class="p-4">Remaining</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr v-for="period in periods" :key="period.id">
                        <td class="whitespace-nowrap p-4">{{ period.starts_on }} → {{ period.ends_on }}</td>
                        <td class="p-4">{{ kilometers(period.allocated_meters) }} km</td>
                        <td class="p-4">{{ kilometers(period.consumed_meters) }} km</td>
                        <td class="p-4">{{ kilometers(period.remaining_meters) }} km</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>
</template>
