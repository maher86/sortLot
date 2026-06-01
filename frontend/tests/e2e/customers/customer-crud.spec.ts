import { expect, test } from "@playwright/test";

import { authenticate } from "../packages/auth";

test.beforeEach(async ({ page }) => {
  await authenticate(page);
});

test("user can create and update a customer", async ({ page }) => {
  const name = `E2E Customer ${Date.now()}`;
  const updated = `${name} Updated`;

  await page.goto("/customers/new");
  await page.getByRole("textbox", { name: "Name", exact: true }).fill(name);
  await page.getByLabel("Email").fill(`customer-${Date.now()}@example.test`);
  await page.getByLabel("Credit limit fils").fill("250000");
  await page.getByLabel("Payment terms days").fill("30");
  await page.getByRole("button", { name: "Save" }).click();

  await expect(page).toHaveURL(/\/customers\/[0-9a-hjkmnp-tv-z]{26}$/);
  await expect(page.getByRole("heading", { name })).toBeVisible();
  await expect(page.getByText("2,500.00 AED").first()).toBeVisible();

  await page.getByRole("button", { name: "Edit" }).click();
  await page.getByRole("textbox", { name: "Name", exact: true }).fill(updated);
  await page.getByRole("button", { name: "Save" }).click();
  await expect(page.getByRole("heading", { name: updated })).toBeVisible();
});
