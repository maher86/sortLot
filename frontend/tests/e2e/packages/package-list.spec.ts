import { expect, test } from "@playwright/test";

import { authenticate } from "./auth";

test.beforeEach(async ({ page }) => {
  await authenticate(page);
});

test("package list loads with filters", async ({ page }) => {
  await page.goto("/packages");

  await expect(page.getByRole("heading", { name: "Packages" })).toBeVisible();
  await expect(page.getByPlaceholder("Search reference")).toBeVisible();
  await page.getByLabel("Filter by status").selectOption("in_transit");
  await expect(page.getByText("No packages found").or(page.getByRole("table"))).toBeVisible();
});
