import { useApiRequest } from '@/composables/useApiRequest';
import { getDashboardSummary } from '@/services/api/dashboard';
import type { DashboardSummary } from '@/types/wmo';
import { onMounted, ref } from 'vue';

export function useDashboard() {
    const summary = ref<DashboardSummary>();
    const request = useApiRequest();

    async function load() {
        const result = await request.execute(getDashboardSummary);
        if (result) summary.value = result;
    }

    onMounted(load);
    return { summary, ...request, load };
}
