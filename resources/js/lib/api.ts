import axios from 'axios';
export const api = axios.create({
    baseURL: '/api/v1',
    withCredentials: true,
    withXSRFToken: true,
    headers: { Accept: 'application/json' },
    timeout: 15000,
});
export function apiError(error: unknown): string {
    if (!axios.isAxiosError(error)) return 'Something went wrong. Please try again.';
    const status = error.response?.status;
    if (status === 401 || status === 419) return 'Your session expired. Sign in again to continue.';
    if (status === 403) return 'Your account does not have permission for this action.';
    if (status === 404) return 'The requested record could not be found.';
    if (status === 422)
        return Object.values((error.response?.data.errors ?? {}) as Record<string, string[]>)
            .flat()
            .join(' ');
    if (status === 409) return error.response?.data.message;
    if (status === 429) return 'Too many requests. Please wait a minute and retry.';
    return 'The API is unavailable. Please retry shortly.';
}
