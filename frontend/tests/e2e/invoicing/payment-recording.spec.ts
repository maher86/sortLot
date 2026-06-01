import { expect, test } from "@playwright/test";

import { authenticate } from "../packages/auth";
import { addManualLine, quickAddCustomer } from "./helpers";

test.beforeEach(async ({ page }) => {
  await authenticate(page);
});

test("user can record a partial payment", async ({ page }) => {
  const customerName = `Payment Customer ${Date.now()}`;

  await page.goto("/invoices/sales/new");
  await quickAddCustomer(page, customerName);
  await addManualLine(page, "Payment sales line", "20000");
  await page.getByRole("button", { name: "Confirm" }).click();
  await expect(page.getByText("Pending")).toBeVisible();

  await page.getByLabel("Amount fils").fill("5000");
  await page.getByLabel("Reference").fill(`PART-${Date.now()}`);
  await page.getByRole("button", { name: "Record payment" }).click();

  await expect(page.getByText("Payment recorded")).toBeVisible();
  await expect(page.getByText("Partial").first()).toBeVisible();
  await expect(page.getByText("50.00 AED").first()).toBeVisible();
});
