<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { TokenCredentials } from '@/types/wmo';
import { ref } from 'vue';
defineProps<{ pending: boolean; errors: Record<string, string[]> }>();
const emit = defineEmits<{ submit: [credentials: TokenCredentials] }>();
const name = ref('');
const password = ref('');
function submit() {
    emit('submit', { name: name.value, password: password.value });
    password.value = '';
}
</script>

<template>
    <section class="rounded-xl border p-6">
        <h2 class="text-lg font-semibold">Create personal access token</h2>
        <p class="mt-2 text-sm text-muted-foreground">Tokens expire in 30 days. Permissions follow your current role and organization.</p>
        <form class="mt-5 grid gap-5" @submit.prevent="submit">
            <div class="space-y-2">
                <Label for="token-name">Token name</Label
                ><Input
                    id="token-name"
                    v-model="name"
                    required
                    maxlength="80"
                    placeholder="My Bruno client"
                    :aria-invalid="!!errors.name"
                /><InputError :message="errors.name?.[0]" />
            </div>
            <div class="space-y-2">
                <Label for="token-password">Current password</Label
                ><Input
                    id="token-password"
                    v-model="password"
                    type="password"
                    autocomplete="current-password"
                    required
                    :aria-invalid="!!errors.password"
                /><InputError :message="errors.password?.[0]" />
            </div>
            <Button :disabled="pending">Create token</Button>
        </form>
    </section>
</template>
