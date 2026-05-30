import { expect, test } from "@playwright/test";

import { authenticate } from "../packages/auth";

test.beforeEach(async ({ page }) => {
  await authenticate(page);
});

test("user can add and edit pricing tiers", async ({ page }) => {
  const code = `E2E${Date.now().toString().slice(-4)}`;

  await page.goto("/preferences");
  await page.getByRole("button", { name: "Pricing Tiers" }).click();
  await page.getByLabel("Tier code").fill(code);
  await page.getByLabel("Tier label").fill("E2E Tier");
  await page.getByLabel("Price per kg fils").fill("1777");
  await page.getByRole("button", { name: "Add" }).click();

  await expect(page.getByText(code)).toBeVisible();
  await page.getByLabel(`Label for ${code}`).fill("E2E Tier Updated");
  await page.getByLabel(`Label for ${code}`).blur();
  await expect(page.getByLabel(`Label for ${code}`)).toHaveValue("E2E Tier Updated");
});
