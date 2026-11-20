<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { RideFilters } from '@/services/api/rides';
const filters = defineModel<RideFilters>({ required: true });
defineProps<{ pending: boolean }>();
defineEmits<{ apply: [] }>();
</script>

<template>
    <form class="flex flex-wrap items-end gap-4" @submit.prevent="$emit('apply')">
        <div class="space-y-2"><Label for="from">From</Label><Input id="from" v-model="filters.from" type="date" /></div>
        <div class="space-y-2"><Label for="to">Through</Label><Input id="to" v-model="filters.to" type="date" /></div>
        <div class="space-y-2">
            <Label for="status">Status</Label
            ><select id="status" v-model="filters.status" class="block h-10 rounded-md border bg-background px-3 text-sm">
                <option value="">All statuses</option>
                <option value="booked">Booked</option>
                <option value="completed">Completed</option>
                <option value="cancelled">Cancelled</option>
            </select>
        </div>
        <Button :disabled="pending">Apply filters</Button>
    </form>
</template>
