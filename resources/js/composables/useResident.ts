import { useApiRequest } from '@/composables/useApiRequest';
import { getResident } from '@/services/api/residents';
import type { Resident } from '@/types/wmo';
import { onMounted, onUnmounted, ref } from 'vue';

export function useResident(id: number) {
    const resident = ref<Resident>();
    const request = useApiRequest();
    const controller = new AbortController();

    async function load() {
        const result = await request.execute(() => getResident(id, controller.signal));
        if (result) resident.value = result;
    }

    onMounted(load);
    onUnmounted(() => controller.abort());

    return { resident, ...request, load };
}
