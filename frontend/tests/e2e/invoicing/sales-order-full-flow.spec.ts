import { expect, test } from "@playwright/test";

import { authenticate } from "../packages/auth";
import { addManualLine, quickAddCustomer } from "./helpers";

test.beforeEach(async ({ page }) => {
  await authenticate(page);
});

test("user can create, confirm, and pay a sales order", async ({ page }) => {
  const customerName = `Flow Customer ${Date.now()}`;

  await page.goto("/invoices/sales/new");
  await quickAddCustomer(page, customerName);
  await addManualLine(page, "Flow sales line", "10000");
  await page.getByRole("button", { name: "Confirm" }).click();

  await expect(page).toHaveURL(/\/invoices\/sales\/[0-9a-hjkmnp-tv-z]{26}$/);
  await expect(page.getByText("Pending")).toBeVisible();

  await page.getByLabel("Amount fils").fill("10500");
  await page.getByLabel("Reference").fill(`PAY-${Date.now()}`);
  await page.getByRole("button", { name: "Record payment" }).click();

  await expect(page.getByText("Payment recorded")).toBeVisible();
  await expect(page.getByText("Paid").first()).toBeVisible();
  await expect(page.getByText("0.00 AED").first()).toBeVisible();
});
