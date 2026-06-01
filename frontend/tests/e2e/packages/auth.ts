import { expect, type Page } from "@playwright/test";

let cachedToken: string | null = null;

const apiUrl = process.env.NEXT_PUBLIC_API_URL ?? "http://localhost/api/v1";
const appUrl = process.env.NEXT_PUBLIC_APP_URL ?? "http://localhost";

export async function authenticate(page: Page) {
  await page.context().clearCookies();
  await page.goto("/login");
  await page.evaluate(() => window.localStorage.clear());

  if (!cachedToken) {
    const response = await page.request.post(`${apiUrl}/auth/login`, {
      data: {
        email: "admin@sortlot.local",
        password: "password",
      },
      headers: {
        Accept: "application/json",
        "X-Forwarded-For": `10.50.0.${Math.floor(Math.random() * 200) + 1}`,
      },
    });

    expect(response.ok()).toBeTruthy();
    const payload = (await response.json()) as { data: { token: string } };
    cachedToken = payload.data.token;
  }

  await page.context().addCookies([
    {
      name: "sortlot_auth",
      value: "1",
      domain: "localhost",
      path: "/",
    },
  ]);
  await page.request.get(`${appUrl}/sanctum/csrf-cookie`);
  await page.evaluate((token) => window.localStorage.setItem("sortlot_token", token), cachedToken);
}
