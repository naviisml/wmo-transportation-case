<script setup lang="ts">
import { Button } from '@/components/ui/button';
import BudgetSummary from '@/components/wmo/BudgetSummary.vue';
import MetricCard from '@/components/wmo/MetricCard.vue';
import PageHeading from '@/components/wmo/PageHeading.vue';
import RequestFeedback from '@/components/wmo/RequestFeedback.vue';
import { useDashboard } from '@/composables/useDashboard';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
const { summary, pending, error, status, load } = useDashboard();
</script>

<template>
    <Head title="Municipal overview" />
    <AppLayout
        :breadcrumbs="[
            { title: 'Overview', href: '/dashboard' },
            { title: 'Municipal overview', href: '/municipality' },
        ]"
    >
        <div class="mx-auto flex w-full min-w-0 max-w-7xl flex-col gap-6 p-4 md:p-8">
            <PageHeading
                title="Municipal overview"
                description="Program totals, annual distance allowances and current parcel coverage. All figures use fictional assessment data."
            >
                <template #actions>
                    <Button as-child variant="outline"><Link href="/residents">View residents</Link></Button>
                </template>
            </PageHeading>
            <RequestFeedback :pending="pending" :error="error" :status="status" @retry="load" />
            <template v-if="summary && !error">
                <section aria-label="Authorization totals" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <MetricCard label="Resident records" :value="summary.residents_total ?? 0" />
                    <MetricCard label="Active authorizations" :value="summary.active_authorizations ?? 0" />
                    <MetricCard label="Terminated authorizations" :value="summary.terminated_authorizations ?? 0" />
                    <MetricCard label="Without authorization" :value="summary.without_authorization ?? 0" />
                </section>
                <BudgetSummary v-if="summary.budget" :budget="summary.budget" title="Current budgets for active authorizations" />
                <section class="rounded-xl border p-6" aria-label="Transport coverage">
                    <h2 class="text-lg font-semibold">Transport coverage</h2>
                    <dl class="mt-6 grid gap-6 sm:grid-cols-3">
                        <div>
                            <dt class="text-sm text-muted-foreground">Taxi companies</dt>
                            <dd class="mt-2 text-2xl font-semibold">{{ summary.taxi_companies }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-muted-foreground">Parcels</dt>
                            <dd class="mt-2 text-2xl font-semibold">{{ summary.parcels }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-muted-foreground">Parcels without a current assignment</dt>
                            <dd class="mt-2 text-2xl font-semibold">{{ summary.uncovered_parcels }}</dd>
                        </div>
                    </dl>
                    <p class="mt-6 text-sm text-muted-foreground">
                        Coverage is evaluated for today in Europe/Amsterdam. Missing assignments prevent a ride from being booked.
                    </p>
                </section>
                <div class="grid gap-4 sm:grid-cols-2">
                    <MetricCard label="Total rides" :value="summary.rides_total" /><MetricCard
                        label="Upcoming rides"
                        :value="summary.upcoming_rides"
                    />
                </div>
            </template>
        </div>
    </AppLayout>
</template>
