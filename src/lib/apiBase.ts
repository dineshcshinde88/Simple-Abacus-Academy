const LOCAL_HOSTS = new Set(["localhost", "127.0.0.1", "::1"]);
const DEFAULT_LOCAL_API = "http://localhost:5002";
const DEFAULT_PROD_API = "https://api.simpleabacus.com";

function normalizeBaseUrl(url: string): string {
  return url.trim().replace(/\/+$/, "");
}

export function getApiBase(): string {
  const configured = import.meta.env.VITE_API_URL?.trim();
  if (configured) {
    return normalizeBaseUrl(configured);
  }

  if (typeof window === "undefined") {
    return DEFAULT_LOCAL_API;
  }

  const host = window.location.hostname.toLowerCase();
  return LOCAL_HOSTS.has(host) ? DEFAULT_LOCAL_API : DEFAULT_PROD_API;
}

