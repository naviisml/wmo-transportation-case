import { useApiRequest } from '@/composables/useApiRequest';
import { createToken, listTokens, revokeToken } from '@/services/api/tokens';
import type { Token, TokenCredentials } from '@/types/wmo';
import { onMounted, ref } from 'vue';

export function usePersonalTokens() {
    const tokens = ref<Token[]>([]);
    const plaintext = ref('');
    const notice = ref('');
    const listing = useApiRequest();
    const mutation = useApiRequest();

    async function load() {
        const result = await listing.execute(listTokens);
        if (result) tokens.value = result;
    }

    async function create(credentials: TokenCredentials) {
        notice.value = '';
        plaintext.value = '';
        const token = await mutation.execute(() => createToken(credentials));
        if (!token) return;
        plaintext.value = token;
        notice.value = 'Token created. Copy it now; it will not be shown again.';
        await load();
    }

    async function revoke(id: number) {
        notice.value = '';
        const revoked = await mutation.execute(async () => {
            await revokeToken(id);
            return true;
        });
        if (!revoked) return;
        notice.value = 'Token revoked.';
        await load();
    }

    onMounted(load);
    return { tokens, plaintext, notice, listing, mutation, load, create, revoke };
}
