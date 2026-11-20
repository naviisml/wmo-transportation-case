<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import PaginationControls from '@/components/wmo/PaginationControls.vue';
import RequestFeedback from '@/components/wmo/RequestFeedback.vue';
import { useApiRequest } from '@/composables/useApiRequest';
import { listResidents } from '@/services/api/residents';
import type { Page, Resident } from '@/types/wmo';
import { onMounted, ref } from 'vue';
import ResidentTable from './ResidentTable.vue';
const search = ref('');
const appliedSearch = ref('');
const result = ref<Page<Resident>>();
const { pending, error, status, execute } = useApiRequest();

async function load(page = 1) {
    const response = await execute(() => listResidents(appliedSearch.value, page));
    if (response) result.value = response;
}
function submitSearch() {
    appliedSearch.value = search.value;
    void load();
}
onMounted(load);
</script>

<template>
    <section class="flex min-w-0 flex-col gap-5" aria-label="Resident search">
        <form class="flex max-w-xl items-end gap-3" @submit.prevent="submitSearch">
            <div class="flex-1 space-y-2">
                <Label for="resident-search">Find resident by name or reference</Label
                ><Input id="resident-search" v-model="search" maxlength="100" placeholder="Search fictional residents…" />
            </div>
            <Button :disabled="pending">Search</Button>
        </form>
        <RequestFeedback :pending="pending" :error="error" :status="status" @retry="load(result?.meta.current_page)" />
        <template v-if="result && !error">
            <ResidentTable v-if="result.data.length" :residents="result.data" />
            <div v-else class="rounded-xl border p-10 text-center text-sm text-muted-foreground">
                No residents found. Try a different name or reference.
            </div>
            <PaginationControls
                :page="result.meta.current_page"
                :last-page="result.meta.last_page"
                :total="result.meta.total"
                :pending="pending"
                label="residents"
                @change="load"
            />
        </template>
    </section>
</template>
