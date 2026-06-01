import { expect, test } from "@playwright/test";

import { authenticate } from "../packages/auth";

test.beforeEach(async ({ page }) => {
  await authenticate(page);
});

test("customer quick-add modal saves without leaving the list", async ({ page }) => {
  const name = `Quick Customer ${Date.now()}`;

  await page.goto("/customers");
  await page.getByRole("button", { name: "Quick add" }).click();
  await page.getByRole("heading", { name: "Quick add customer" }).isVisible();
  await page.getByRole("textbox", { name: "Name", exact: true }).fill(name);
  await page.getByLabel("Email").fill(`quick-${Date.now()}@example.test`);
  await page.getByRole("button", { name: "Save" }).click();

  await expect(page).toHaveURL(/\/customers$/);
  await expect(page.getByText(name)).toBeVisible();
});
