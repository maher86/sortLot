import { expect, test } from "@playwright/test";

import { authenticate } from "./auth";

test.beforeEach(async ({ page }) => {
  await authenticate(page);
});

test("package can be edited, deleted, and started from pre-sorting statuses", async ({ page }) => {
  const reference = `E2E-ACTION-${Date.now()}`;
  const updated = `${reference}-UPD`;

  await page.goto("/dashboard");
  const token = await page.evaluate(() => window.localStorage.getItem("sortlot_token"));
  const create = await page.request.post("http://localhost/api/v1/packages", {
    data: {
      reference,
      origin_country: "US",
    },
    headers: {
      Authorization: `Bearer ${token}`,
      Accept: "application/json",
    },
  });
  expect(create.ok()).toBeTruthy();
  const payload = (await create.json()) as { data: { id: string } };

  await page.goto(`/packages/${payload.data.id}`);
  await page.getByRole("button", { name: "Edit" }).click();
  await page.getByLabel("Reference").fill(updated);
  await page.getByRole("button", { name: "Save" }).click();
  await expect(page.getByRole("heading", { name: updated })).toBeVisible();

  await page.getByRole("button", { name: "Start sorting" }).click();
  await expect(page.getByText("Sorting").first()).toBeVisible();
  await expect(page.getByRole("button", { name: "Edit" })).toBeDisabled();
  await expect(page.getByRole("button", { name: "Delete" })).toBeDisabled();

  const deleteCandidate = await page.request.post("http://localhost/api/v1/packages", {
    data: {
      reference: `${reference}-DEL`,
      origin_country: "US",
    },
    headers: {
      Authorization: `Bearer ${token}`,
      Accept: "application/json",
    },
  });
  expect(deleteCandidate.ok()).toBeTruthy();
  const deletable = (await deleteCandidate.json()) as { data: { id: string } };

  page.once("dialog", (dialog) => dialog.accept());
  await page.goto(`/packages/${deletable.data.id}`);
  await page.getByRole("button", { name: "Delete" }).click();
  await expect(page).toHaveURL(/\/packages$/);
});
