import { expect, test } from "@playwright/test";

test.beforeEach(async ({ context, page }) => {
  await context.clearCookies();
  await page.goto("/login");
  await page.evaluate(() => window.localStorage.clear());
});

test("unauthenticated redirect to login", async ({ page }) => {
  await page.goto("/dashboard");

  await expect(page).toHaveURL(/\/login$/);
});

test("user can log in and see dashboard", async ({ page }) => {
  await page.goto("/login");
  await page.getByPlaceholder("admin@sortlot.local").fill("admin@sortlot.local");
  await page.getByPlaceholder("Password").fill("password");
  await page.getByRole("button", { name: /sign in/i }).click();

  await expect(page).toHaveURL(/\/dashboard$/);
  await expect(page.getByRole("heading", { name: "Dashboard" })).toBeVisible();
});

test("user can log out", async ({ page }) => {
  await page.goto("/login");
  await page.getByPlaceholder("admin@sortlot.local").fill("admin@sortlot.local");
  await page.getByPlaceholder("Password").fill("password");
  await page.getByRole("button", { name: /sign in/i }).click();
  await expect(page).toHaveURL(/\/dashboard$/);
  await page.getByLabel("Log out").click();

  await expect(page).toHaveURL(/\/login$/);
});

test("wrong password shows error", async ({ page }) => {
  await page.goto("/login");
  await page.getByPlaceholder("admin@sortlot.local").fill("admin@sortlot.local");
  await page.getByPlaceholder("Password").fill("not-the-password");
  await page.getByRole("button", { name: /sign in/i }).click();

  await expect(page.getByText("Email or password is incorrect.")).toBeVisible();
});
