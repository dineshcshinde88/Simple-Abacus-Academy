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
  const isFormData = body instanceof FormData;

  let response: Response;
  try {
    response = await fetch(`${API_BASE}${path}`, {
      method: "POST",
      headers: isFormData ? undefined : { "Content-Type": "application/json" },
      body: isFormData ? body : JSON.stringify(body),
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
  courseType: string;
  countryCode: string;
  mobile: string;
  email: string;
  gender: string;
  dateOfBirth: string;
  qualification: string;
  careerStarted: string;
  studentsTrained: string;
  address: string;
  profilePicture?: File | null;
  password: string;
  confirmPassword: string;
}) => {
  const formData = new FormData();
  Object.entries(payload).forEach(([key, value]) => {
    if (key === "profilePicture") {
      if (value instanceof File) {
        formData.append(key, value);
      }
      return;
    }
    formData.append(key, String(value ?? ""));
  });

  return request<{ message: string; email: string }>("/api/instructor/register/start", formData);
};

export const forgotInstructorPassword = (payload: { email: string }) =>
  request<{ message: string }>("/api/instructor/forgot-password", payload);

export const resetInstructorPassword = (payload: { email: string; token: string; password: string; confirmPassword: string }) =>
  request<{ message: string }>("/api/instructor/reset-password", payload);
