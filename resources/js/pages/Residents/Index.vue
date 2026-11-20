<script setup lang="ts">
import ResidentSearch from '@/components/residents/ResidentSearch.vue';
import PageHeading from '@/components/wmo/PageHeading.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Residents" />
    <AppLayout :breadcrumbs="[{ title: 'Residents', href: '/residents' }]">
        <div class="mx-auto flex w-full min-w-0 max-w-7xl flex-col gap-6 p-4 md:p-8">
            <PageHeading title="Residents" description="Find a resident and open their authorization and annual budget details." />
            <ResidentSearch />
        </div>
    </AppLayout>
</template>
