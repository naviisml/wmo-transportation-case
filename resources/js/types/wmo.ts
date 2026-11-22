export interface Budget {
    id: number;
    starts_on: string;
    ends_on: string;
    allocated_meters: number;
    consumed_meters: number;
    remaining_meters: number;
}
export interface Resident {
    id: number;
    name: string;
    reference: string;
    address: { street: string; house_number: string; postal_code: string; city: string } | null;
    authorization: { starts_on: string; terminated_on: string | null; budget_periods: Budget[] } | null;
}
export type RideStatus = 'booked' | 'completed' | 'cancelled';
export interface Ride {
    id: number;
    resident: { id: number; name: string };
    origin: string;
    destination: string;
    scheduled_at: string;
    distance_meters: number;
    status: RideStatus;
    taxi_company: { id: number; name: string };
    budget?: Budget;
}
export interface CurrentUser {
    id: number;
    name: string;
    email: string;
    role: 'municipality' | 'callcenter' | 'taxi_company';
    taxi_company_id: number | null;
}
export interface Token {
    id: number;
    name: string;
    expires_at: string | null;
    last_used_at: string | null;
}
export interface Page<T> {
    data: T[];
    meta: { current_page: number; last_page: number; total: number };
}

export interface BudgetTotals {
    allocated_meters: number;
    consumed_meters: number;
    remaining_meters: number;
}

export interface DashboardSummary {
    role: CurrentUser['role'];
    rides_total: number;
    upcoming_rides: number;
    residents_total?: number;
    active_authorizations?: number;
    terminated_authorizations?: number;
    without_authorization?: number;
    taxi_companies?: number;
    parcels?: number;
    uncovered_parcels?: number;
    budget?: BudgetTotals;
}

export interface BookRidePayload {
    resident_id: number;
    origin: string;
    destination: string;
    scheduled_at: string;
    distance_meters: number;
}

export interface TokenCredentials {
    name: string;
    password: string;
}
