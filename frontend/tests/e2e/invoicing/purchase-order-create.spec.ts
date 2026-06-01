import { expect, test } from "@playwright/test";

import { authenticate } from "../packages/auth";
import { addManualLine, quickAddSupplier } from "./helpers";

test.beforeEach(async ({ page }) => {
  await authenticate(page);
});

test("user can create a draft purchase order", async ({ page }) => {
  const supplierName = `Invoice Supplier ${Date.now()}`;

  await page.goto("/invoices/purchase/new");
  await quickAddSupplier(page, supplierName);
  await addManualLine(page, "Manual purchase line", "8000");
  await page.getByRole("button", { name: "Save draft" }).click();

  await expect(page).toHaveURL(/\/invoices\/purchase\/[0-9a-hjkmnp-tv-z]{26}$/);
  await expect(page.getByText(supplierName)).toBeVisible();
  await expect(page.getByText("Draft", { exact: true })).toBeVisible();
  await expect(page.getByText("80.00 AED").first()).toBeVisible();
});
