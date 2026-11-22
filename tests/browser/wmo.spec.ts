import { expect, test, type Page } from '@playwright/test';
const password = process.env.DEMO_PASSWORD ?? 'demo-assessment-only';
async function login(page: Page, account: string) {
    await page.goto('/login');
    await page.getByLabel('Email address').fill(`${account}@example.test`);
    await page.locator('#password').fill(password);
    await page.getByRole('button', { name: 'Log in', exact: true }).click();
    await expect(page).toHaveURL(/dashboard/);
    await expect(page.getByRole('heading', { name: 'Overview', exact: true })).toBeVisible();
}
test('callcenter searches, books through the API and gets updated allowance', async ({ page }) => {
    const errors: string[] = [];
    page.on('pageerror', (error) => errors.push(error.message));
    await login(page, 'callcenter');
    await page.getByRole('link', { name: 'Residents', exact: true }).first().click();
    await page.getByLabel('Find resident by name or reference').fill('DEMO-001');
    await page.getByRole('button', { name: 'Search', exact: true }).click();
    await expect(page.getByRole('cell', { name: /DEMO-001/ })).toBeVisible();
    await expect(page.getByRole('link', { name: 'View resident', exact: true })).toHaveCount(1);
    await expect(page.getByLabel('Destination', { exact: true })).toHaveCount(0);
    await page.getByRole('link', { name: 'View resident', exact: true }).click();
    await expect(page).toHaveURL(/\/residents\/\d+$/);
    await expect(page.getByRole('heading', { name: 'Resident & authorization' })).toBeVisible();
    await page.getByRole('link', { name: 'Book a ride', exact: true }).last().click();
    await expect(page).toHaveURL(/\/residents\/\d+\/rides\/create$/);
    await expect(page.getByRole('heading', { name: 'Book a ride' })).toBeVisible();
    await page.getByLabel('Destination', { exact: true }).fill('Demo museum, Utrecht');
    await page.getByLabel('Date & time').fill(new Date(Date.now() + 86400000).toISOString().slice(0, 16));
    await page.getByLabel('Distance (km)').fill('1.25');
    const response = page.waitForResponse(r => r.url().endsWith('/api/v1/rides') && r.request().method() === 'POST');
    await page.getByRole('button', { name: 'Confirm booking' }).click();
    expect((await response).status()).toBe(201);
    await expect(page.getByRole('heading', { name: /Ride #\d+ confirmed/ })).toBeVisible();
    await expect(page.getByRole('button', { name: 'Confirm booking' })).toHaveCount(0);
    await expect(page.getByRole('heading', { name: 'Updated annual allowance' })).toBeVisible();
    await page.screenshot({ path: 'test-results/callcenter-desktop.png', fullPage: true });
    expect(errors).toEqual([]);
});
test('session token lifecycle works and CSRF is enforced', async ({ page }) => {
    await login(page, 'callcenter');
    await page.getByRole('link', { name: 'API access', exact: true }).first().click();
    await page.getByLabel('Token name').fill('Browser verification');
    await page.getByLabel('Current password').fill(password);
    await page.getByRole('button', { name: 'Create token', exact: true }).click();
    await expect(page.getByLabel('Copy now')).toBeVisible();
    const token = await page.getByLabel('Copy now').inputValue();
    const apiResult = await page.request.get('/api/v1/me', { headers: { Authorization: `Bearer ${token}` } });
    expect(apiResult.status()).toBe(200);
    await page.getByRole('button', { name: 'Dismiss token' }).click();
    const row = page.getByRole('listitem').filter({ hasText: 'Browser verification' });
    await row.getByRole('button', { name: 'Revoke' }).click();
    await expect(row).toHaveCount(0);
    // A direct request needs an XSRF token, even with a session cookie.
    const status = (await page.request.post('/api/v1/rides', { data: {} })).status();
    expect(status).toBe(419);
});
for (const account of ['taxi-a', 'taxi-b']) {
    test(`${account} sees only assigned organization rides on mobile`, async ({ page }) => {
        await page.setViewportSize({ width: 390, height: 844 });
        await login(page, account);
        await page.getByRole('link', { name: 'View rides', exact: true }).click();
        await expect(page.getByRole('heading', { name: 'Assigned rides', exact: true })).toBeVisible();
        const response = await page.request.get('/api/v1/rides');
        const json = await response.json();
        const me = await (await page.request.get('/api/v1/me')).json();
        expect(json.data.length).toBeGreaterThan(0);
        for (const ride of json.data) expect(ride.taxi_company.id).toBe(me.data.taxi_company_id);
        expect((await page.request.get('/api/v1/residents')).status()).toBe(403);
        expect(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth)).toBe(true);
        await page.screenshot({ path: `test-results/${account}-mobile.png`, fullPage: true });
    });
}
test('municipality opens its overview and browses Utrecht residents', async ({ page }) => {
    await login(page, 'gemeente');
    await page.getByRole('link', { name: 'Municipal overview', exact: true }).first().click();
    await expect(page).toHaveURL(/\/municipality$/);
    await expect(page.getByRole('heading', { name: 'Municipal overview', exact: true })).toBeVisible();
    await expect(page.getByRole('heading', { name: 'Transport coverage' })).toBeVisible();
    await expect(page.getByRole('heading', { name: 'Current budgets for active authorizations' })).toBeVisible();
    const summary = await (await page.request.get('/api/v1/dashboard')).json();
    expect(summary.data.role).toBe('municipality');
    expect(summary.data.residents_total).toBeGreaterThanOrEqual(24);
    expect(summary.data).not.toHaveProperty('residents');
    expect((await page.request.get('/docs/api.json')).status()).toBe(200);
    await page.screenshot({ path: 'test-results/municipality-overview.png', fullPage: true });
    await page.getByRole('link', { name: 'View residents', exact: true }).click();
    await expect(page).toHaveURL(/\/residents$/);
    await page.getByLabel('Find resident by name or reference').fill('DEMO-008');
    await page.getByRole('button', { name: 'Search', exact: true }).click();
    await expect(page.getByRole('link', { name: 'View resident', exact: true })).toHaveCount(1);
    await expect(page.getByRole('cell', { name: /DEMO-008/ })).toBeVisible();
    await page.getByRole('link', { name: 'View resident', exact: true }).click();
    await expect(page).toHaveURL(/\/residents\/\d+$/);
    await expect(page.getByRole('heading', { name: 'Resident & authorization' })).toBeVisible();
    await expect(page.getByRole('link', { name: 'Book a ride', exact: true })).toHaveCount(0);
});

test('booking entry point selects a resident on a dedicated page', async ({ page }) => {
    await login(page, 'callcenter');
    await page.goto('/book-ride');
    await page.getByLabel('Find resident by name or reference').fill('DEMO-001');
    await page.getByRole('button', { name: 'Search', exact: true }).click();
    await expect(page.getByRole('link', { name: 'Select resident', exact: true })).toHaveCount(1);
    await page.getByRole('link', { name: 'Select resident', exact: true }).click();
    await expect(page).toHaveURL(/\/residents\/\d+\/rides\/create$/);
    await expect(page.getByLabel('Destination', { exact: true })).toBeVisible();
    await page.goBack();
    await expect(page).toHaveURL(/\/book-ride$/);
    await expect(page.getByLabel('Destination', { exact: true })).toHaveCount(0);
});

test('a failed resident lookup has an actionable error on its own page', async ({ page }) => {
    await login(page, 'callcenter');
    await page.goto('/residents/999999999');
    await expect(page.getByRole('alert')).toContainText('could not be found');
    await expect(page.getByRole('link', { name: 'Back to residents' })).toBeVisible();
    await expect(page.getByLabel('Destination', { exact: true })).toHaveCount(0);
});
