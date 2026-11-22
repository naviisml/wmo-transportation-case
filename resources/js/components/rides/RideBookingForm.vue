<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import RequestFeedback from '@/components/wmo/RequestFeedback.vue';
import { useRideBooking } from '@/composables/useRideBooking';
import { amsterdamDate, budgetForDate, currentBudget, kilometers } from '@/lib/wmo';
import type { Resident, Ride } from '@/types/wmo';
import { computed } from 'vue';
const props = defineProps<{ resident: Resident; canBook: boolean }>();
const emit = defineEmits<{ booked: [ride: Ride] }>();
const { form, pending, error, status, fieldErrors, submit } = useRideBooking(props.resident);
const applicableBudget = computed(() => {
    if (!form.scheduled_at) return currentBudget(props.resident);
    const date = new Date(form.scheduled_at);
    return Number.isNaN(date.getTime()) ? undefined : budgetForDate(props.resident, amsterdamDate(date));
});
const allowanceAvailable = computed(() => (applicableBudget.value?.remaining_meters ?? 0) > 0);
const requestedMeters = computed<number | undefined>(() => {
    const kilometers = Number(form.distance_km);
    if (!Number.isFinite(kilometers) || kilometers <= 0) return undefined;
    const meters = Math.round(kilometers * 1000);
    return Number.isFinite(meters) && meters > 0 ? meters : undefined;
});
const insufficientAllowance = computed(() => {
    if (requestedMeters.value === undefined) return false;
    return requestedMeters.value > (applicableBudget.value?.remaining_meters ?? 0);
});

async function confirm() {
    if (requestedMeters.value === undefined) return;
    const ride = await submit(requestedMeters.value);
    if (ride) emit('booked', ride);
}
</script>

<template>
    <form class="grid gap-5 rounded-xl border p-6" @submit.prevent="confirm">
        <div>
            <h2 class="text-lg font-semibold">Journey details</h2>
            <p class="mt-1 text-sm text-muted-foreground">The responsible taxi company is determined from the residential parcel.</p>
        </div>
        <RequestFeedback :pending="pending" :error="error" :status="status" @retry="confirm" />
        <div class="space-y-2">
            <Label for="origin">Origin</Label
            ><Input id="origin" v-model="form.origin" required maxlength="255" :aria-invalid="!!fieldErrors.origin" /><InputError
                :message="fieldErrors.origin?.[0]"
            />
        </div>
        <div class="space-y-2">
            <Label for="destination">Destination</Label
            ><Input
                id="destination"
                v-model="form.destination"
                required
                maxlength="255"
                placeholder="Street, house number, city"
                :aria-invalid="!!fieldErrors.destination"
            /><InputError :message="fieldErrors.destination?.[0]" />
        </div>
        <div class="grid gap-5 sm:grid-cols-2">
            <div class="space-y-2">
                <Label for="scheduled">Date & time (your device timezone)</Label
                ><Input
                    id="scheduled"
                    v-model="form.scheduled_at"
                    type="datetime-local"
                    required
                    :aria-invalid="!!fieldErrors.scheduled_at"
                /><InputError :message="fieldErrors.scheduled_at?.[0]" />
            </div>
            <div class="space-y-2">
                <Label for="distance">Distance (km)</Label
                ><Input
                    id="distance"
                    v-model="form.distance_km"
                    type="number"
                    min="0.001"
                    max="1000"
                    step="0.001"
                    required
                    :aria-invalid="!!fieldErrors.distance_meters"
                /><InputError :message="fieldErrors.distance_meters?.[0]" />
            </div>
        </div>
        <p class="text-sm text-muted-foreground">The scheduled date selects its annual budget. A period must already exist for that date.</p>
        <p v-if="!canBook || !allowanceAvailable" class="text-sm text-amber-700 dark:text-amber-400">
            No active authorization with an available allowance exists for this booking.
        </p>
        <p v-if="insufficientAllowance" class="text-sm text-amber-700 dark:text-amber-400">
            The requested distance exceeds the available allowance of {{ kilometers(applicableBudget?.remaining_meters ?? 0) }} km.
        </p>
        <p v-if="form.scheduled_at && applicableBudget" class="text-sm text-muted-foreground">
            Selected period: {{ applicableBudget.starts_on }} → {{ applicableBudget.ends_on }}. Available:
            {{ kilometers(applicableBudget.remaining_meters) }} km.
        </p>
        <Button :disabled="pending || !canBook || !allowanceAvailable || insufficientAllowance || requestedMeters === undefined"
            >Confirm booking</Button
        >
    </form>
</template>
