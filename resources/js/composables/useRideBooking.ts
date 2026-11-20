import { useApiRequest } from '@/composables/useApiRequest';
import { residentialAddress } from '@/lib/wmo';
import { bookRide } from '@/services/api/rides';
import type { Resident, Ride } from '@/types/wmo';
import { reactive } from 'vue';

export function useRideBooking(resident: Resident) {
    const form = reactive({ origin: resident.address ? residentialAddress(resident) : '', destination: '', scheduled_at: '', distance_km: '' });
    const request = useApiRequest();
    let attempt: { payload: string; key: string } | undefined;

    async function submit(): Promise<Ride | undefined> {
        return request.execute(async () => {
            const payload = {
                resident_id: resident.id,
                origin: form.origin,
                destination: form.destination,
                scheduled_at: new Date(form.scheduled_at).toISOString(),
                distance_meters: Math.round(Number(form.distance_km) * 1000),
            };
            const serialized = JSON.stringify(payload);
            if (!attempt || attempt.payload !== serialized) {
                attempt = { payload: serialized, key: crypto.randomUUID() };
            }
            return bookRide(payload, attempt.key);
        });
    }

    return { form, ...request, submit };
}
