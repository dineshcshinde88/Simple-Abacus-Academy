import { getApiBase } from "@/lib/apiBase";

export type AuthUser = {
  id: string;
  name: string;
  email: string;
  role: "student" | "tutor";
};

export class AuthApiError extends Error {
  status: number;

  constructor(message: string, status: number) {
    super(message);
    this.name = "AuthApiError";
    this.status = status;
  }
}

type AuthResponse = {
  token: string;
  user: AuthUser;
};

const API_BASE = getApiBase();

async function apiRequest<T>(path: string, options?: RequestInit): Promise<T> {
  try {
    const response = await fetch(`${API_BASE}${path}`, {
      ...options,
      headers: {
        "Content-Type": "application/json",
        ...(options?.headers || {}),
      },
    });

    const data = await response.json().catch(() => ({}));
    if (!response.ok) {
      throw new AuthApiError((data as { message?: string }).message || "Request failed", response.status);
    }

    return data as T;
  } catch (error) {
    if (error instanceof AuthApiError) {
      throw error;
    }
    throw new AuthApiError("Unable to reach server. Please ensure the backend is running.", 0);
  }
}

export async function login(email: string, password: string, role: "student" | "tutor"): Promise<AuthResponse> {
  return apiRequest<AuthResponse>("/api/auth/login", {
    method: "POST",
    body: JSON.stringify({ email, password, role }),
  });
}

export async function register(name: string, email: string, password: string, role: "student" | "tutor"): Promise<AuthResponse> {
  return apiRequest<AuthResponse>("/api/auth/register", {
    method: "POST",
    body: JSON.stringify({ name, email, password, role }),
  });
}

export async function forgotPassword(email: string): Promise<{ message: string }> {
  return apiRequest<{ message: string }>("/api/auth/forgot-password", {
    method: "POST",
    body: JSON.stringify({ email }),
  });
}

export async function getMe(token: string): Promise<{ user: AuthUser }> {
  return apiRequest<{ user: AuthUser }>("/api/auth/me", {
    method: "GET",
    headers: {
      Authorization: `Bearer ${token}`,
    },
  });
}
