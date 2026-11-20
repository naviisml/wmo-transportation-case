<script setup lang="ts">
import CallcenterOverview from '@/components/dashboard/CallcenterOverview.vue';
import MunicipalityOverview from '@/components/dashboard/MunicipalityOverview.vue';
import TaxiOverview from '@/components/dashboard/TaxiOverview.vue';
import PageHeading from '@/components/wmo/PageHeading.vue';
import RequestFeedback from '@/components/wmo/RequestFeedback.vue';
import { useDashboard } from '@/composables/useDashboard';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
const page = usePage();
const { summary, pending, error, status, load } = useDashboard();
</script>

<template>
    <Head title="Overview" />
    <AppLayout :breadcrumbs="[{ title: 'Overview', href: '/dashboard' }]">
        <div class="mx-auto flex w-full min-w-0 max-w-7xl flex-col gap-6 p-4 md:p-8">
            <PageHeading title="Overview" :description="`Welcome back, ${page.props.auth.user.name}. Your Wmo transport workspace.`" />
            <RequestFeedback :pending="pending" :error="error" :status="status" @retry="load" />
            <template v-if="summary && !error">
                <MunicipalityOverview v-if="summary.role === 'municipality'" :summary="summary" />
                <CallcenterOverview v-else-if="summary.role === 'callcenter'" :summary="summary" />
                <TaxiOverview v-else :summary="summary" />
            </template>
        </div>
    </AppLayout>
</template>
