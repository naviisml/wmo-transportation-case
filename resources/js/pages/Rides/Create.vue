<script setup lang="ts">
import BookingConfirmation from '@/components/rides/BookingConfirmation.vue';
import RideBookingForm from '@/components/rides/RideBookingForm.vue';
import { Button } from '@/components/ui/button';
import BudgetSummary from '@/components/wmo/BudgetSummary.vue';
import PageHeading from '@/components/wmo/PageHeading.vue';
import RequestFeedback from '@/components/wmo/RequestFeedback.vue';
import { useResident } from '@/composables/useResident';
import AppLayout from '@/layouts/AppLayout.vue';
import { authorizationStatus, currentBudget } from '@/lib/wmo';
import type { Ride } from '@/types/wmo';
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
const props = defineProps<{ residentId: number }>();
const { resident, pending, error, status, load } = useResident(props.residentId);
const confirmation = ref<Ride>();
const budget = computed(() => (resident.value ? currentBudget(resident.value) : undefined));
const canBook = computed(() => !!resident.value && authorizationStatus(resident.value) === 'Active');
const breadcrumbs = computed(() => [
    { title: 'Residents', href: '/residents' },
    { title: resident.value?.name ?? 'Resident', href: `/residents/${props.residentId}` },
    { title: 'Book a ride', href: `/residents/${props.residentId}/rides/create` },
]);
</script>

<template>
    <Head :title="confirmation ? 'Ride confirmed' : 'Book a ride'" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex w-full min-w-0 max-w-5xl flex-col gap-6 p-4 md:p-8">
            <PageHeading
                :title="confirmation ? 'Ride confirmed' : 'Book a ride'"
                :description="resident ? `For ${resident.name} · ${resident.reference}` : 'Loading resident details…'"
            >
                <template #actions
                    ><Button as-child variant="outline"><Link :href="`/residents/${residentId}`">Back to resident</Link></Button></template
                >
            </PageHeading>
            <RequestFeedback :pending="pending" :error="error" :status="status" @retry="load" />
            <BookingConfirmation v-if="confirmation" :ride="confirmation" />
            <div v-else-if="resident && !error" class="grid items-start gap-6 lg:grid-cols-2">
                <div class="space-y-5">
                    <BudgetSummary v-if="budget" :budget="budget" title="Current annual allowance" />
                    <div v-else class="rounded-xl border p-6 text-sm text-muted-foreground">No current budget period is available.</div>
                    <p class="text-sm text-muted-foreground">
                        Authorization: {{ authorizationStatus(resident) }}. No allowance is deducted until the booking succeeds.
                    </p>
                </div>
                <RideBookingForm :resident="resident" :can-book="canBook" @booked="confirmation = $event" />
            </div>
        </div>
    </AppLayout>
</template>
