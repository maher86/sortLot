import { expect, test } from "@playwright/test";

import { authenticate } from "./auth";

test.beforeEach(async ({ page }) => {
  await authenticate(page);
});

test("user can start sorting and add an item", async ({ page }) => {
  const reference = `E2E-SORT-${Date.now()}`;
  const barcode = `BC-${Date.now()}`;

  await page.goto("/dashboard");
  const token = await page.evaluate(() => window.localStorage.getItem("sortlot_token"));
  const create = await page.request.post("http://localhost/api/v1/packages", {
    data: {
      reference,
      origin_country: "US",
      status: "in_warehouse",
    },
    headers: {
      Authorization: `Bearer ${token}`,
      Accept: "application/json",
    },
  });
  expect(create.ok()).toBeTruthy();
  const payload = (await create.json()) as { data: { id: string } };

  await page.goto(`/packages/${payload.data.id}`);
  await expect(page.getByRole("heading", { name: reference })).toBeVisible();

  await page.getByRole("button", { name: "Start sorting" }).click();
  await expect(page.getByText("Sorting").first()).toBeVisible();

  await page.getByRole("button", { name: "Add items" }).click();
  await page.getByLabel("Item type").selectOption({ index: 1 });
  await page.getByLabel("Pricing tier").selectOption({ index: 1 });
  await page.getByLabel("Barcode").fill(barcode);
  await page.getByRole("button", { name: "Add item", exact: true }).click();

  await expect(page.getByText(barcode)).toBeVisible();
});
