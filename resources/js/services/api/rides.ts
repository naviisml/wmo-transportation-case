import { api } from '@/lib/api';
import type { BookRidePayload, Page, Ride } from '@/types/wmo';

export interface RideFilters {
    from: string;
    to: string;
    status: string;
}

export async function listRides(filters: RideFilters, page = 1): Promise<Page<Ride>> {
    const response = await api.get<Page<Ride>>('/rides', {
        params: { from: filters.from || undefined, to: filters.to || undefined, status: filters.status || undefined, page },
    });
    return response.data;
}

export async function bookRide(payload: BookRidePayload, idempotencyKey: string): Promise<Ride> {
    const response = await api.post<{ data: Ride }>('/rides', payload, { headers: { 'Idempotency-Key': idempotencyKey } });
    return response.data.data;
}
