import { expect, type Page } from "@playwright/test";

export async function quickAddCustomer(page: Page, name: string) {
  await page.getByRole("button", { name: "Quick add" }).click();
  await page.getByRole("textbox", { name: "Name", exact: true }).fill(name);
  await page.getByRole("button", { exact: true, name: "Save" }).click();
  await expect(page.getByText("Customer added")).toBeVisible();
}

export async function quickAddSupplier(page: Page, name: string) {
  await page.getByRole("button", { name: "Quick add" }).click();
  await page.getByRole("textbox", { name: "Name", exact: true }).fill(name);
  await page.getByRole("button", { exact: true, name: "Save" }).click();
  await expect(page.getByText("Supplier added")).toBeVisible();
}

export async function addManualLine(page: Page, description: string, unitFils: string) {
  await page.getByRole("button", { name: "Manual line" }).click();
  await page.getByLabel("Line 1 description").fill(description);
  await page.getByLabel("Line 1 unit price fils").fill(unitFils);
}
