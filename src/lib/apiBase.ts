const LOCAL_HOSTS = new Set(["localhost", "127.0.0.1", "::1"]);
const DEFAULT_LOCAL_API = "http://localhost:5002";
const DEFAULT_PROD_API = "https://api.simpleabacus.com";

function normalizeBaseUrl(url: string): string {
  return url.trim().replace(/\/+$/, "");
}

function isLocalUrl(url: string): boolean {
  try {
    return LOCAL_HOSTS.has(new URL(url).hostname.toLowerCase());
  } catch {
    return false;
  }
}

export function getApiBase(): string {
  const configured = import.meta.env.VITE_API_URL?.trim();
  const runtimeHost = typeof window === "undefined" ? "" : window.location.hostname.toLowerCase();
  const isLocalRuntime = runtimeHost !== "" && LOCAL_HOSTS.has(runtimeHost);

  if (configured) {
    if (!isLocalRuntime && isLocalUrl(configured)) {
      return DEFAULT_PROD_API;
    }

    return normalizeBaseUrl(configured);
  }

  if (typeof window === "undefined") {
    return DEFAULT_LOCAL_API;
  }

  return isLocalRuntime ? DEFAULT_LOCAL_API : DEFAULT_PROD_API;
}
