import { expect, test } from '@playwright/test';
import { loginToTypo3, requiredCredentials } from './support/typo3-backend';

const modulePath = '/typo3/module/web/component-playground';

test('allowed regular user can access Component Playground', async ({ page }) => {
  const credentials = requiredCredentials(
    'allowed regular user',
    process.env.TYPO3_E2E_ALLOWED_USER,
    process.env.TYPO3_E2E_ALLOWED_PASSWORD,
  );

  await loginToTypo3(page, credentials);

  const moduleLink = page.locator('a[href*="/module/web/component-playground"]');
  await expect(moduleLink).toBeVisible();
  await moduleLink.click();

  const app = page.frameLocator('iframe[name="list_frame"]');
  await expect(app.getByTestId('component-playground-root')).toBeVisible();
  await expect(app.getByTestId('component-card-hero')).toBeVisible();
  await expect(app.getByTestId('render-status')).toBeVisible();
});

test('restricted regular user cannot access Component Playground', async ({ page }) => {
  const credentials = requiredCredentials(
    'restricted regular user',
    process.env.TYPO3_E2E_RESTRICTED_USER,
    process.env.TYPO3_E2E_RESTRICTED_PASSWORD,
  );

  await loginToTypo3(page, credentials);

  await expect(page.locator('a[href*="/module/web/component-playground"]')).toHaveCount(0);

  const response = await page.goto(modulePath);
  const app = page.frameLocator('iframe[name="list_frame"]');

  expect(response?.status()).toBeGreaterThanOrEqual(400);
  await expect(app.getByTestId('component-playground-root')).toHaveCount(0);
});
