<script setup lang="ts">
import ResidentSearch from '@/components/residents/ResidentSearch.vue';
import PageHeading from '@/components/wmo/PageHeading.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Book a ride" />
    <AppLayout :breadcrumbs="[{ title: 'Book a ride', href: '/book-ride' }]">
        <div class="mx-auto flex w-full min-w-0 max-w-7xl flex-col gap-6 p-4 md:p-8">
            <PageHeading
                title="Book a ride"
                description="First select a resident. Their ride will be booked on a separate page with their applicable allowance."
            />
            <ResidentSearch selecting-for-booking />
        </div>
    </AppLayout>
</template>
