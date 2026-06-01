import { expect, test } from "@playwright/test";

import { authenticate } from "../packages/auth";

test.beforeEach(async ({ page }) => {
  await authenticate(page);
});

test("item filters can be combined", async ({ page }) => {
  await page.goto("/items");

  await page.getByLabel("Filter by season").selectOption("winter");
  await page.getByLabel("Filter by gender").selectOption("man");
  await page.getByLabel("Filter by status").selectOption("available");

  await expect(page.getByRole("heading", { name: "Items" })).toBeVisible();
  await expect(page.getByPlaceholder("Search SKU or barcode")).toBeVisible();
});
