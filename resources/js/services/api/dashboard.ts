import { api } from '@/lib/api';
import type { DashboardSummary } from '@/types/wmo';

export async function getDashboardSummary(): Promise<DashboardSummary> {
    const response = await api.get<{ data: DashboardSummary }>('/dashboard');
    return response.data.data;
}
