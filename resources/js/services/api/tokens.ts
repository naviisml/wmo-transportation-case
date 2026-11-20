import { api } from '@/lib/api';
import type { Token, TokenCredentials } from '@/types/wmo';

export async function listTokens(): Promise<Token[]> {
    const response = await api.get<{ data: Token[] }>('/tokens');
    return response.data.data;
}

export async function createToken(credentials: TokenCredentials): Promise<string> {
    const response = await api.post<{ token: string }>('/tokens', credentials);
    return response.data.token;
}

export async function revokeToken(id: number): Promise<void> {
    await api.delete(`/tokens/${id}`);
}
