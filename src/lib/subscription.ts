import { AuthApiError } from "@/lib/auth";

const API_BASE = import.meta.env.VITE_API_URL || "http://localhost:5002";

async function apiRequest<T>(path: string, token: string, options?: RequestInit): Promise<T> {
  const response = await fetch(`${API_BASE}${path}`, {
    ...options,
    headers: {
      "Content-Type": "application/json",
      Authorization: `Bearer ${token}`,
      ...(options?.headers || {}),
    },
  });

  const data = await response.json().catch(() => ({}));
  if (!response.ok) {
    throw new AuthApiError((data as { message?: string }).message || "Request failed", response.status);
  }

  return data as T;
}

export type Subscription = {
  _id: string;
  planName: "Monthly" | "Quarterly" | "Yearly";
  price: number;
  startDate: string;
  expiryDate: string;
  status: "active" | "expired";
};

export type Worksheet = {
  _id: string;
  title: string;
  level: "Beginner" | "Intermediate" | "Advanced";
  fileUrl: string;
};

export async function subscribeToPlan(
  token: string,
  planName: "Monthly" | "Quarterly" | "Yearly",
  price: number,
): Promise<{ subscription: Subscription }> {
  return apiRequest<{ subscription: Subscription }>("/api/subscriptions/subscribe", token, {
    method: "POST",
    body: JSON.stringify({ planName, price }),
  });
}

export async function getMySubscription(token: string): Promise<{ subscription: Subscription | null }> {
  return apiRequest<{ subscription: Subscription | null }>("/api/subscriptions/me", token);
}

export async function getWorksheets(token: string): Promise<{ worksheets: Worksheet[] }> {
  return apiRequest<{ worksheets: Worksheet[] }>("/api/worksheets", token);
}

export async function getWorksheetDownload(token: string, id: string): Promise<{ url: string }> {
  return apiRequest<{ url: string }>(`/api/worksheets/${id}/download`, token);
}
