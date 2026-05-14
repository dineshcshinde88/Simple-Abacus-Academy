const LOCAL_HOSTS = new Set(["localhost", "127.0.0.1", "::1"]);
const DEFAULT_LOCAL_API = "http://localhost:5002";
const DEFAULT_PROD_API_PATH = "/backend/index.php";

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

function getSameOriginApi(path = DEFAULT_PROD_API_PATH): string {
  if (typeof window === "undefined") {
    return normalizeBaseUrl(path);
  }

  return normalizeBaseUrl(`${window.location.origin}${path.startsWith("/") ? path : `/${path}`}`);
}

export function getApiBase(): string {
  const configured = import.meta.env.VITE_API_URL?.trim();
  const runtimeHost = typeof window === "undefined" ? "" : window.location.hostname.toLowerCase();
  const isLocalRuntime = runtimeHost !== "" && LOCAL_HOSTS.has(runtimeHost);

  if (configured) {
    if (!isLocalRuntime && isLocalUrl(configured)) {
      try {
        const configuredPath = new URL(configured).pathname;
        const path = configuredPath.includes("/abacus-spark-learn-main/")
          ? DEFAULT_PROD_API_PATH
          : configuredPath;
        return getSameOriginApi(path);
      } catch {
        return getSameOriginApi();
      }
    }

    return normalizeBaseUrl(configured);
  }

  if (typeof window === "undefined") {
    return DEFAULT_LOCAL_API;
  }

  return isLocalRuntime ? DEFAULT_LOCAL_API : getSameOriginApi();
}
