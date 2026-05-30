import axios, { AxiosHeaders } from "axios";

const appUrl = process.env.NEXT_PUBLIC_APP_URL ?? "http://localhost";

function getCookie(name: string) {
  return document.cookie
    .split("; ")
    .find((cookie) => cookie.startsWith(`${name}=`))
    ?.split("=")
    .slice(1)
    .join("=");
}

export const api = axios.create({
  baseURL: process.env.NEXT_PUBLIC_API_URL ?? "http://localhost/api/v1",
  withCredentials: true,
  withXSRFToken: true,
  xsrfCookieName: "XSRF-TOKEN",
  xsrfHeaderName: "X-XSRF-TOKEN",
  headers: {
    Accept: "application/json",
  },
});

api.interceptors.request.use(async (config) => {
  const method = config.method?.toLowerCase();
  const mutates = method && ["post", "put", "patch", "delete"].includes(method);

  if (mutates && typeof window !== "undefined" && !document.cookie.includes("XSRF-TOKEN")) {
    await axios.get(`${appUrl}/sanctum/csrf-cookie`, { withCredentials: true });
  }

  if (typeof window !== "undefined") {
    const xsrfToken = getCookie("XSRF-TOKEN");
    if (xsrfToken) {
      const headers = AxiosHeaders.from(config.headers);
      headers.set("X-XSRF-TOKEN", decodeURIComponent(xsrfToken));
      config.headers = headers;
    }

    const token = window.localStorage.getItem("sortlot_token");
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
  }

  return config;
});
