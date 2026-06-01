import { expect, test } from "@playwright/test";

import { authenticate } from "./auth";

test.beforeEach(async ({ page }) => {
  await authenticate(page);
});

test("user can create a package", async ({ page }) => {
  const reference = `E2E-PKG-${Date.now()}`;

  await page.goto("/packages/new");
  await page.getByPlaceholder("2026-PKG-001").fill(reference);
  await page.getByLabel("Origin country").fill("US");
  await page.getByLabel("Weight kg").fill("88.5");
  await page.getByLabel("Bags").fill("9");
  const createResponse = page.waitForResponse(
    (response) => response.url() === "http://localhost/api/v1/packages" && response.request().method() === "POST",
  );
  await page.locator("form").evaluate((form) => (form as HTMLFormElement).requestSubmit());
  expect((await createResponse).status()).toBe(201);

  await expect(page).toHaveURL(/\/packages\/[0-9a-hjkmnp-tv-z]{26}$/, { timeout: 15_000 });
  await expect(page.getByRole("heading", { name: reference })).toBeVisible();
});
