import { expect, test } from "@playwright/test";

test("home page renders SortLot", async ({ page }) => {
  await page.goto("/");

  await expect(page.getByRole("heading", { name: /used clothing operations/i })).toBeVisible();
});
