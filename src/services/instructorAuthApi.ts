import { getApiBase } from "@/lib/apiBase";

const API_BASE = getApiBase();
const REQUEST_TIMEOUT_MS = 20000;

export class ApiError extends Error {
  status: number;

  constructor(message: string, status: number) {
    super(message);
    this.name = "ApiError";
    this.status = status;
  }
}

export type InstructorRegistrationResponse = {
  message: string;
  email?: string;
};

async function request<T>(path: string, body: unknown): Promise<T> {
  const controller = new AbortController();
  const timeoutId = window.setTimeout(() => controller.abort(), REQUEST_TIMEOUT_MS);

  let response: Response;
  try {
    response = await fetch(`${API_BASE}${path}`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(body),
      signal: controller.signal,
    });
  } catch (error) {
    if (error instanceof DOMException && error.name === "AbortError") {
      throw new ApiError("The instructor request is taking too long. Please try again.", 0);
    }
    throw new ApiError("Unable to reach the instructor registration server. Please try again.", 0);
  } finally {
    window.clearTimeout(timeoutId);
  }

  const data = await response.json().catch(() => ({}));
  if (!response.ok) {
    if (response.status === 404) {
      throw new ApiError(
        "Instructor registration API was not found on the live server. Please deploy the latest backend to api.simpleabacus.com.",
        response.status,
      );
    }
    throw new ApiError((data as { message?: string }).message || "Request failed", response.status);
  }
  return data as T;
}

export const registerInstructor = (payload: {
  fullName: string;
  mobile: string;
  email: string;
  password: string;
  confirmPassword: string;
}) => request<{ message: string; email: string }>("/api/instructor/register/start", payload);

export const forgotInstructorPassword = (payload: { email: string }) =>
  request<{ message: string }>("/api/instructor/forgot-password", payload);

export const resetInstructorPassword = (payload: { email: string; token: string; password: string; confirmPassword: string }) =>
  request<{ message: string }>("/api/instructor/reset-password", payload);
