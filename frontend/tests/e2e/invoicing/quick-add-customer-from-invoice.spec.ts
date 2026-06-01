import { expect, test } from "@playwright/test";

import { authenticate } from "../packages/auth";
import { quickAddCustomer } from "./helpers";

test.beforeEach(async ({ page }) => {
  await authenticate(page);
});

test("user can quick-add a customer from the invoice form", async ({ page }) => {
  const customerName = `Quick Invoice Customer ${Date.now()}`;

  await page.goto("/invoices/sales/new");
  await quickAddCustomer(page, customerName);

  await expect(page.getByRole("combobox").first()).toHaveValue(/[0-9a-hjkmnp-tv-z]{26}/);
  await expect(page.getByRole("option", { name: customerName })).toBeAttached();
});
