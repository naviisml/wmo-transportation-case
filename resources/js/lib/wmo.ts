import type { Budget, Resident } from '@/types/wmo';

export const kilometers = (meters: number) => new Intl.NumberFormat('nl-NL', { maximumFractionDigits: 3 }).format(meters / 1000);
export function amsterdamDate(date = new Date()): string {
    return new Intl.DateTimeFormat('en-CA', { timeZone: 'Europe/Amsterdam' }).format(date);
}

export function budgetForDate(resident: Resident, date: string): Budget | undefined {
    return resident.authorization?.budget_periods.find((period) => period.starts_on <= date && period.ends_on > date);
}

export function currentBudget(resident: Resident): Budget | undefined {
    return budgetForDate(resident, amsterdamDate());
}

export function dateTime(date: string): string {
    return new Intl.DateTimeFormat('nl-NL', {
        dateStyle: 'medium',
        timeStyle: 'short',
        timeZone: 'Europe/Amsterdam',
    }).format(new Date(date));
}

export function residentialAddress(resident: Resident): string {
    const address = resident.address;
    return address ? `${address.street} ${address.house_number}, ${address.postal_code} ${address.city}` : 'No residential address';
}

export function authorizationStatus(resident: Resident): string {
    const today = amsterdamDate();
    const authorization = resident.authorization;
    if (!authorization) return 'No authorization';
    if (authorization.starts_on > today) return 'Not yet active';
    if (authorization.terminated_on && authorization.terminated_on <= today) return 'Terminated';
    return 'Active';
}
