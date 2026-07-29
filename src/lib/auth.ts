import { getApiBase } from "@/lib/apiBase";

export type AuthUser = {
  id: string;
  name: string;
  email: string;
  role: "student" | "tutor";
};

export class AuthApiError extends Error {
  status: number;
  code?: string;

  constructor(message: string, status: number, code?: string) {
    super(message);
    this.name = "AuthApiError";
    this.status = status;
    this.code = code;
  }
}

type AuthResponse = {
  token: string;
  user: AuthUser;
};

export type RegisterDetails = {
  course?: string;
  phoneCountry?: string;
  phone?: string;
  gender?: string;
  motherTongue?: string;
  dob?: string;
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
      const errorData = data as { message?: string; code?: string };
      throw new AuthApiError(errorData.message || "Request failed", response.status, errorData.code);
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

export async function register(
  name: string,
  email: string,
  password: string,
  role: "student" | "tutor",
  details: RegisterDetails = {},
): Promise<AuthResponse> {
  return apiRequest<AuthResponse>("/api/auth/register", {
    method: "POST",
    body: JSON.stringify({ name, email, password, role, ...details }),
  });
}

export async function forgotPassword(email: string): Promise<{ message: string }> {
  return apiRequest<{ message: string }>("/api/auth/forgot-password", {
    method: "POST",
    body: JSON.stringify({ email }),
  });
}

export async function resetPassword(email: string, token: string, password: string, confirmPassword: string): Promise<{ message: string }> {
  return apiRequest<{ message: string }>("/api/auth/reset-password", {
    method: "POST",
    body: JSON.stringify({ email, token, password, confirmPassword }),
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
