<script setup lang="ts">
import { authorizationStatus, residentialAddress } from '@/lib/wmo';
import type { Resident } from '@/types/wmo';
defineProps<{ resident: Resident }>();
</script>

<template>
    <section class="rounded-xl border p-6">
        <h2 class="text-lg font-semibold">Resident & authorization</h2>
        <dl class="mt-5 grid gap-5 text-sm sm:grid-cols-2">
            <div>
                <dt class="text-muted-foreground">Reference</dt>
                <dd class="mt-1 font-medium">{{ resident.reference }}</dd>
            </div>
            <div>
                <dt class="text-muted-foreground">Residential address</dt>
                <dd class="mt-1 font-medium">{{ residentialAddress(resident) }}</dd>
            </div>
            <div>
                <dt class="text-muted-foreground">Authorization status</dt>
                <dd class="mt-1 font-medium">{{ authorizationStatus(resident) }}</dd>
            </div>
            <div v-if="resident.authorization">
                <dt class="text-muted-foreground">Original authorization date</dt>
                <dd class="mt-1 font-medium">{{ resident.authorization.starts_on }}</dd>
            </div>
            <div v-if="resident.authorization?.terminated_on">
                <dt class="text-muted-foreground">Termination effective from</dt>
                <dd class="mt-1 font-medium">{{ resident.authorization.terminated_on }}</dd>
            </div>
        </dl>
    </section>
</template>
