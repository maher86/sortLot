import { expect, test } from "@playwright/test";

import { authenticate } from "../packages/auth";
import { addManualLine, quickAddCustomer } from "./helpers";

test.beforeEach(async ({ page }) => {
  await authenticate(page);
});

test("user can create a draft sales order", async ({ page }) => {
  const customerName = `Invoice Customer ${Date.now()}`;

  await page.goto("/invoices/sales/new");
  await quickAddCustomer(page, customerName);
  await addManualLine(page, "Manual sales line", "10000");
  await page.getByRole("button", { name: "Save draft" }).click();

  await expect(page).toHaveURL(/\/invoices\/sales\/[0-9a-hjkmnp-tv-z]{26}$/);
  await expect(page.getByText(customerName)).toBeVisible();
  await expect(page.getByText("Draft", { exact: true })).toBeVisible();
  await expect(page.getByText("105.00 AED").first()).toBeVisible();
});
