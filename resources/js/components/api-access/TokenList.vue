<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { dateTime } from '@/lib/wmo';
import type { Token } from '@/types/wmo';
defineProps<{ tokens: Token[]; pending: boolean }>();
defineEmits<{ revoke: [id: number] }>();
</script>

<template>
    <section class="rounded-xl border p-6">
        <h2 class="mb-4 text-lg font-semibold">Your tokens</h2>
        <ul class="divide-y">
            <li v-for="token in tokens" :key="token.id" class="flex flex-wrap items-center justify-between gap-4 py-4">
                <div>
                    <p class="font-medium">{{ token.name }}</p>
                    <p class="mt-1 text-xs text-muted-foreground">
                        Expires {{ token.expires_at ? dateTime(token.expires_at) : 'never' }} · Last used
                        {{ token.last_used_at ? dateTime(token.last_used_at) : 'never' }}
                    </p>
                </div>
                <Button variant="outline" size="sm" :disabled="pending" @click="$emit('revoke', token.id)">Revoke</Button>
            </li>
        </ul>
        <p v-if="!tokens.length" class="text-sm text-muted-foreground">No personal tokens. Your browser uses a secure session.</p>
    </section>
</template>
