import { existsSync } from 'node:fs';
import { loadEnvFile } from 'node:process';
import { defineConfig } from '@playwright/test';
if (existsSync('.env')) loadEnvFile('.env');

export default defineConfig({
    testDir: './tests/browser',
    fullyParallel: false,
    workers: 1,
    use: { baseURL: process.env.E2E_BASE_URL ?? 'http://localhost', browserName: 'chromium', headless: true, extraHTTPHeaders: { Origin: process.env.E2E_BASE_URL ?? 'http://localhost' } },
    reporter: 'list',
    outputDir: 'test-results',
});
