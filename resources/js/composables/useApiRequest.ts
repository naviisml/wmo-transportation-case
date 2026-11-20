import { apiError } from '@/lib/api';
import axios from 'axios';
import { ref } from 'vue';

export function useApiRequest() {
    const pending = ref(false);
    const error = ref('');
    const status = ref<number>();
    const fieldErrors = ref<Record<string, string[]>>({});

    async function execute<T>(operation: () => Promise<T>): Promise<T | undefined> {
        pending.value = true;
        error.value = '';
        status.value = undefined;
        fieldErrors.value = {};

        try {
            return await operation();
        } catch (failure) {
            if (axios.isCancel(failure)) return undefined;
            error.value = apiError(failure);
            if (axios.isAxiosError(failure)) {
                status.value = failure.response?.status;
                fieldErrors.value = failure.response?.data.errors ?? {};
            }
            return undefined;
        } finally {
            pending.value = false;
        }
    }

    return { pending, error, status, fieldErrors, execute };
}
