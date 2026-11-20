import { api } from '@/lib/api';
import type { BookRidePayload, Ride } from '@/types/wmo';

export async function bookRide(payload: BookRidePayload, idempotencyKey: string): Promise<Ride> {
    const response = await api.post<{ data: Ride }>('/rides', payload, { headers: { 'Idempotency-Key': idempotencyKey } });
    return response.data.data;
}
