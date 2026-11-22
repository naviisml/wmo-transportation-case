<script setup lang="ts">
import BudgetHistory from '@/components/residents/BudgetHistory.vue';
import ResidentProfile from '@/components/residents/ResidentProfile.vue';
import { Button } from '@/components/ui/button';
import BudgetSummary from '@/components/wmo/BudgetSummary.vue';
import PageHeading from '@/components/wmo/PageHeading.vue';
import RequestFeedback from '@/components/wmo/RequestFeedback.vue';
import { useResident } from '@/composables/useResident';
import AppLayout from '@/layouts/AppLayout.vue';
import { currentBudget } from '@/lib/wmo';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
const props = defineProps<{ residentId: number }>();
const page = usePage();
const canBookRide = computed(() => page.props.auth.user.role === 'callcenter');
const { resident, pending, error, status, load } = useResident(props.residentId);
const budget = computed(() => (resident.value ? currentBudget(resident.value) : undefined));
const breadcrumbs = computed(() => [
    { title: 'Residents', href: '/residents' },
    { title: resident.value?.name ?? 'Resident details', href: `/residents/${props.residentId}` },
]);
</script>

<template>
    <Head :title="resident?.name ?? 'Resident details'" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex w-full min-w-0 max-w-7xl flex-col gap-6 p-4 md:p-8">
            <PageHeading
                :title="resident?.name ?? 'Resident details'"
                description="Authorization, residential address and annual distance allowances."
            >
                <template #actions>
                    <Button as-child variant="outline"><Link href="/residents">Back to residents</Link></Button>
                    <Button v-if="resident && canBookRide" as-child>
                        <Link :href="`/residents/${residentId}/rides/create`">Book a ride</Link>
                    </Button>
                </template>
            </PageHeading>
            <RequestFeedback :pending="pending" :error="error" :status="status" @retry="load" />
            <template v-if="resident && !error">
                <ResidentProfile :resident="resident" />
                <BudgetSummary v-if="budget" :budget="budget" title="Current annual allowance" />
                <p v-else class="rounded-xl border p-6 text-sm text-muted-foreground">No budget period is available for today.</p>
                <BudgetHistory v-if="resident.authorization?.budget_periods.length" :periods="resident.authorization.budget_periods" />
            </template>
        </div>
    </AppLayout>
</template>
