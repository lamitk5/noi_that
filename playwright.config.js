import { defineConfig } from '@playwright/test';

export default defineConfig({
  testDir: './tests/e2e',
  timeout: 30000,
  fullyParallel: true,
  forbidOnly: !!process.env.CI,
  retries: 0,
  workers: 8,
  reporter: [
    ['line'],
    ['json', { outputFile: 'test-results/report.json' }]
  ],
  use: {
    baseURL: 'http://127.0.0.1:8000',
    screenshot: 'off',
    video: 'off',
    trace: 'off',
    extraHTTPHeaders: {
      'Accept': 'application/json, text/html, */*',
    },
  },
});
