<script setup lang="ts">
import RideFilters from '@/components/rides/RideFilters.vue';
import RideTable from '@/components/rides/RideTable.vue';
import PageHeading from '@/components/wmo/PageHeading.vue';
import PaginationControls from '@/components/wmo/PaginationControls.vue';
import RequestFeedback from '@/components/wmo/RequestFeedback.vue';
import { useApiRequest } from '@/composables/useApiRequest';
import AppLayout from '@/layouts/AppLayout.vue';
import { listRides, type RideFilters as RideFilterValues } from '@/services/api/rides';
import type { Page, Ride } from '@/types/wmo';
import { Head } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
const filters = ref<RideFilterValues>({ from: '', to: '', status: '' });
const appliedFilters = ref<RideFilterValues>({ ...filters.value });
const result = ref<Page<Ride>>();
const { pending, error, status, execute } = useApiRequest();

async function load(page = 1) {
    const response = await execute(() => listRides(appliedFilters.value, page));
    if (response) result.value = response;
}
function applyFilters() {
    appliedFilters.value = { ...filters.value };
    void load();
}
onMounted(load);
</script>

<template>
    <Head title="Assigned rides" />
    <AppLayout :breadcrumbs="[{ title: 'Assigned rides', href: '/rides' }]">
        <div class="mx-auto flex w-full min-w-0 max-w-7xl flex-col gap-6 p-4 md:p-8">
            <PageHeading title="Assigned rides" description="Your organization's journeys. Scheduled times are displayed in Europe/Amsterdam." />
            <RideFilters v-model="filters" :pending="pending" @apply="applyFilters" />
            <RequestFeedback :pending="pending" :error="error" :status="status" @retry="load(result?.meta.current_page)" />
            <template v-if="result && !error">
                <RideTable v-if="result.data.length" :rides="result.data" />
                <div v-else class="rounded-xl border p-10 text-center text-sm text-muted-foreground">No assigned rides match these filters.</div>
                <PaginationControls
                    :page="result.meta.current_page"
                    :last-page="result.meta.last_page"
                    :total="result.meta.total"
                    :pending="pending"
                    label="rides"
                    @change="load"
                />
            </template>
        </div>
    </AppLayout>
</template>
