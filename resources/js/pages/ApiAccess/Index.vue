<script setup lang="ts">
import ApiDocumentation from '@/components/api-access/ApiDocumentation.vue';
import CreateTokenForm from '@/components/api-access/CreateTokenForm.vue';
import TokenList from '@/components/api-access/TokenList.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import PageHeading from '@/components/wmo/PageHeading.vue';
import RequestFeedback from '@/components/wmo/RequestFeedback.vue';
import { usePersonalTokens } from '@/composables/usePersonalTokens';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
const { tokens, plaintext, notice, listing, mutation, load, create, revoke } = usePersonalTokens();
const { pending: loading, error: loadError, status: loadStatus } = listing;
const { pending: saving, error: saveError, status: saveStatus, fieldErrors } = mutation;
</script>

<template>
    <Head title="API access" />
    <AppLayout :breadcrumbs="[{ title: 'API access', href: '/api-access' }]">
        <div class="mx-auto flex w-full min-w-0 max-w-7xl flex-col gap-6 p-4 md:p-8">
            <PageHeading title="API access" description="Manage your personal tokens and connect an external API client." />
            <RequestFeedback :pending="loading" :error="loadError" :status="loadStatus" @retry="load" />
            <RequestFeedback :pending="saving" :error="saveError" :status="saveStatus" :retryable="false" />
            <p v-if="notice" role="status" class="rounded-lg border border-emerald-300 bg-emerald-50 p-4 text-sm text-emerald-900">{{ notice }}</p>
            <section v-if="plaintext" class="rounded-xl border p-6">
                <Label for="plaintext">Copy now — shown once</Label
                ><textarea
                    id="plaintext"
                    readonly
                    :value="plaintext"
                    class="mt-3 min-h-20 w-full rounded-md border bg-muted/50 p-3 font-mono text-xs"
                />
                <Button variant="outline" class="mt-3" @click="plaintext = ''">Dismiss token</Button>
            </section>
            <div class="grid gap-6 lg:grid-cols-2">
                <CreateTokenForm :pending="saving" :errors="fieldErrors" @submit="create" /><ApiDocumentation />
            </div>
            <TokenList v-if="!loading && !loadError" :tokens="tokens" :pending="saving" @revoke="revoke" />
        </div>
    </AppLayout>
</template>
