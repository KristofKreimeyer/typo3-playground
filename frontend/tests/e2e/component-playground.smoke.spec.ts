import { expect, test } from '@playwright/test';
import { loginToTypo3, requiredCredentials } from './support/typo3-backend';

function backendCredentials(): { username: string; password: string } {
  return requiredCredentials(
    'admin',
    process.env.TYPO3_E2E_ADMIN_USER ?? process.env.TYPO3_E2E_USER ?? process.env.TYPO3_BACKEND_USER,
    process.env.TYPO3_E2E_ADMIN_PASSWORD ?? process.env.TYPO3_E2E_PASSWORD ?? process.env.TYPO3_BACKEND_PASSWORD,
  );
}

test('runs the documented Component Playground backend flow', async ({ page }) => {
  await loginToTypo3(page, backendCredentials());

  await expect(page.locator('a[href*="/module/web/component-playground"]')).toBeVisible();
  await page.locator('a[href*="/module/web/component-playground"]').click();
  const app = page.frameLocator('iframe[name="list_frame"]');

  await expect(app.getByTestId('component-playground-root')).toBeVisible();
  await expect(app.getByTestId('component-card-hero')).toBeVisible();
  await expect(app.getByTestId('component-card-teaserGrid')).toBeVisible();
  await expect(app.getByTestId('component-card-quote')).toBeVisible();
  await expect(app.getByTestId('component-card-cta')).toBeVisible();
  await expect(app.getByTestId('json-editor')).toBeVisible();
  await expect(app.getByTestId('preview-frame')).toBeVisible();

  await app.getByTestId('component-card-hero').click();
  const editor = app.getByTestId('json-editor');
  const defaultProps = await editor.inputValue();
  await app.getByTestId('variant-selector').selectOption({ label: 'Long text' });
  await expect(editor).not.toHaveValue(defaultProps);
  await expect(app.getByTestId('render-status')).toHaveText('success');
  await expect(app.getByTestId('preview-frame')).toBeVisible();

  await app.getByTestId('viewport-desktop').click();
  await expect(app.getByTestId('viewport-desktop')).toHaveAttribute('aria-pressed', 'true');
  await expect(app.getByTestId('viewport-status')).toHaveText('1200px');
  await app.getByTestId('viewport-mobile').click();
  await expect(app.getByTestId('viewport-mobile')).toHaveAttribute('aria-pressed', 'true');
  await expect(app.getByTestId('viewport-status')).toHaveText('390px');
  await expect(app.getByTestId('preview-frame')).toBeVisible();

  await editor.fill('{ "headline": "Broken JSON"');
  await expect(app.getByTestId('json-status')).toHaveText('invalid');
  await expect(app.getByTestId('json-editor-message')).toContainText(/expected|JSON/i);
  await expect(app.getByTestId('render-status')).toHaveText('invalid JSON');
  await expect(app.getByTestId('preview-shell')).toBeVisible();

  await editor.fill(JSON.stringify({
    eyebrow: 'Schema test',
    text: 'This should fail backend validation.',
  }, null, 2));
  await expect(app.getByTestId('json-status')).toHaveText('valid');
  await expect(app.getByTestId('render-status')).toHaveText('error');
  await expect(app.getByText('invalid_props', { exact: true })).toBeVisible();
  await expect(app.getByTestId('validation-error-list')).toBeVisible();
  await expect(app.getByTestId('validation-error-headline')).toContainText('headline is required');

  await editor.fill(JSON.stringify({
    eyebrow: 'Recovered preview',
    headline: 'Preview recovered after validation',
    text: 'The preview renders again after restoring valid props.',
    buttonLabel: 'Explore',
    buttonUrl: '/demo',
  }, null, 2));
  await expect(app.getByTestId('json-status')).toHaveText('valid');
  await expect(app.getByTestId('render-status')).toHaveText('success');
  await expect(app.getByTestId('preview-frame')).toBeVisible();
  await expect(app.frameLocator('[data-testid="preview-frame"]').getByText('Preview recovered after validation')).toBeVisible();
});
