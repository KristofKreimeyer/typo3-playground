import { expect, type Page } from '@playwright/test';

export type BackendCredentials = {
  username: string;
  password: string;
};

export function requiredCredentials(
  label: string,
  username: string | undefined,
  password: string | undefined,
): BackendCredentials {
  if (!username || !password) {
    throw new Error(`Missing ${label} E2E credentials. Run the local bootstrap and configure .env.e2e.`);
  }

  return { username, password };
}

export async function loginToTypo3(page: Page, credentials: BackendCredentials): Promise<void> {
  await page.goto('/typo3');
  await page.locator('input[name="username"]').fill(credentials.username);
  await page.locator('input[name="p_field"]').fill(credentials.password);
  await page.getByRole('button', { name: 'Login', exact: true }).click();

  await expect(page).not.toHaveURL(/\/typo3\/login(?:[/?#]|$)/, { timeout: 15_000 });
  await expect(page.getByRole('button', { name: credentials.username, exact: true })).toBeVisible();
}
