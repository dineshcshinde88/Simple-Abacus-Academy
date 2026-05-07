import { getApiBase } from "@/lib/apiBase";

const API_BASE = getApiBase();

export class ApiError extends Error {
  status: number;

  constructor(message: string, status: number) {
    super(message);
    this.name = "ApiError";
    this.status = status;
  }
}

async function request<T>(path: string, body: unknown): Promise<T> {
  const response = await fetch(`${API_BASE}${path}`, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(body),
  });
  const data = await response.json().catch(() => ({}));
  if (!response.ok) {
    throw new ApiError((data as { message?: string }).message || "Request failed", response.status);
  }
  return data as T;
}

export const startInstructorRegistration = (payload: { fullName: string; mobile: string; email: string }) =>
  request<{ message: string; email: string }>("/api/instructor/register/start", payload);

export const verifyInstructorOtp = (payload: { email: string; otp: string }) =>
  request<{ message: string; email: string }>("/api/instructor/register/verify-otp", payload);

export const resendInstructorOtp = (payload: { email: string }) =>
  request<{ message: string }>("/api/instructor/register/resend-otp", payload);

export const setInstructorPassword = (payload: { email: string; password: string; confirmPassword: string }) =>
  request<{ message: string }>("/api/instructor/register/set-password", payload);
