import { expect, test } from "@playwright/test";

import { authenticate } from "../packages/auth";

async function createItem(page: import("@playwright/test").Page) {
  const token = await page.evaluate(() => window.localStorage.getItem("sortlot_token"));
  const packageResponse = await page.request.post("http://localhost/api/v1/packages", {
    data: {
      reference: `E2E-ITEMS-${Date.now()}`,
      origin_country: "US",
      status: "sorting",
    },
    headers: { Authorization: `Bearer ${token}`, Accept: "application/json" },
  });
  expect(packageResponse.ok()).toBeTruthy();
  const packagePayload = (await packageResponse.json()) as { data: { id: string } };
  const options = await page.request.get("http://localhost/api/v1/preferences/item-types", {
    headers: { Authorization: `Bearer ${token}`, Accept: "application/json" },
  });
  const tiers = await page.request.get("http://localhost/api/v1/preferences/pricing-tiers", {
    headers: { Authorization: `Bearer ${token}`, Accept: "application/json" },
  });
  const itemTypes = (await options.json()) as { data: { id: number }[] };
  const pricingTiers = (await tiers.json()) as { data: { id: number }[] };

  const barcode = `ITEM-LIST-${Date.now()}`;
  const itemResponse = await page.request.post(`http://localhost/api/v1/packages/${packagePayload.data.id}/items/bulk`, {
    data: {
      items: [
        {
          item_type_id: itemTypes.data[0].id,
          pricing_tier_id: pricingTiers.data[0].id,
          season: "winter",
          gender: "man",
          barcode,
          unit_price_fils: 1200,
        },
      ],
    },
    headers: { Authorization: `Bearer ${token}`, Accept: "application/json" },
  });
  expect(itemResponse.ok()).toBeTruthy();

  const itemPayload = (await itemResponse.json()) as { data: { id: string }[] };

  return { barcode, itemId: itemPayload.data[0].id };
}

test.beforeEach(async ({ page }) => {
  await authenticate(page);
});

test("item list shows rows and quick status updates", async ({ page }) => {
  const { barcode, itemId } = await createItem(page);

  await page.goto(`/items/${itemId}`);
  await expect(page.getByRole("definition").filter({ hasText: barcode })).toBeVisible();

  await page.goto("/items");
  await page.getByPlaceholder("Search SKU or barcode").fill(barcode);
  await expect(page.getByText(barcode)).toBeVisible();

  const row = page.getByRole("row").filter({ hasText: barcode });
  await row.getByLabel(/change status/i).selectOption("damaged");
  await expect(row.locator("span").filter({ hasText: "Damaged" })).toBeVisible();
});
