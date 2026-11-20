import { api } from '@/lib/api';
import type { Page, Resident } from '@/types/wmo';

export async function listResidents(search: string, page = 1): Promise<Page<Resident>> {
    const response = await api.get<Page<Resident>>('/residents', { params: { search: search || undefined, page } });
    return response.data;
}

export async function getResident(id: number, signal?: AbortSignal): Promise<Resident> {
    const response = await api.get<{ data: Resident }>(`/residents/${id}`, { signal });
    return response.data.data;
}
