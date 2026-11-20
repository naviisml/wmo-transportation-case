<script setup lang="ts">
import { dateTime, kilometers } from '@/lib/wmo';
import type { Ride } from '@/types/wmo';
defineProps<{ rides: Ride[] }>();
</script>

<template>
    <div class="overflow-x-auto rounded-xl border">
        <table class="w-full text-left text-sm">
            <caption class="sr-only">
                Assigned rides, times in Europe/Amsterdam
            </caption>
            <thead class="bg-muted/50 text-muted-foreground">
                <tr>
                    <th class="p-4">Scheduled · Amsterdam</th>
                    <th class="p-4">Resident / route</th>
                    <th class="p-4">Distance</th>
                    <th class="p-4">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                <tr v-for="ride in rides" :key="ride.id">
                    <td class="whitespace-nowrap p-4">
                        {{ dateTime(ride.scheduled_at) }}
                        <p class="mt-1 text-xs text-muted-foreground">#{{ ride.id }}</p>
                    </td>
                    <td class="p-4">
                        <p class="font-medium">{{ ride.resident.name }}</p>
                        <p class="mt-1 text-muted-foreground">{{ ride.origin }} → {{ ride.destination }}</p>
                    </td>
                    <td class="whitespace-nowrap p-4">{{ kilometers(ride.distance_meters) }} km</td>
                    <td class="p-4">
                        <span class="rounded-full bg-muted px-2.5 py-1 text-xs capitalize">{{ ride.status }}</span>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
