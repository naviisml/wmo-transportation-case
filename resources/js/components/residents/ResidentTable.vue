<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { authorizationStatus, currentBudget, kilometers, residentialAddress } from '@/lib/wmo';
import type { Resident } from '@/types/wmo';
import { Link } from '@inertiajs/vue3';
defineProps<{ residents: Resident[]; selectingForBooking: boolean }>();
</script>

<template>
    <div class="overflow-x-auto rounded-xl border">
        <table class="w-full text-left text-sm">
            <caption class="sr-only">
                Resident search results
            </caption>
            <thead class="bg-muted/50 text-muted-foreground">
                <tr>
                    <th class="p-4">Resident</th>
                    <th class="p-4">Residential address</th>
                    <th class="p-4">Authorization</th>
                    <th class="p-4">Remaining budget</th>
                    <th class="p-4"><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody class="divide-y">
                <tr v-for="resident in residents" :key="resident.id">
                    <td class="p-4">
                        <p class="font-medium">{{ resident.name }}</p>
                        <p class="mt-1 text-xs text-muted-foreground">{{ resident.reference }}</p>
                    </td>
                    <td class="p-4">{{ residentialAddress(resident) }}</td>
                    <td class="p-4">{{ authorizationStatus(resident) }}</td>
                    <td class="whitespace-nowrap p-4">
                        {{ currentBudget(resident) ? kilometers(currentBudget(resident)!.remaining_meters) + ' km' : '—' }}
                    </td>
                    <td class="p-4">
                        <Button as-child size="sm" variant="outline">
                            <Link :href="selectingForBooking ? `/residents/${resident.id}/rides/create` : `/residents/${resident.id}`">{{
                                selectingForBooking ? 'Select resident' : 'View resident'
                            }}</Link>
                        </Button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
