import { getApiBase } from "@/lib/apiBase";

export type StudentDashboardData = {
  name: string;
  level: string | null;
  batchesCount: number;
  worksheetsCount: number;
  subscriptionStatus: "active" | "expired";
  startDate: string | null;
  expiryDate: string | null;
  subscriptions?: {
    id: string;
    planName: string;
    levelId: string | null;
    levelName: string | null;
    amount: number;
    currency: string;
    startDate: string | null;
    expiryDate: string | null;
    status: "active" | "expired" | "cancelled";
    paymentStatus: "paid" | "unpaid";
  }[];
  practice?: {
    purchasedLevels: number;
    completedPapers: number;
    pendingPapers: number;
    averageAccuracy: number;
  };
};

export type StudentProfileData = {
  id: string;
  name: string;
  email: string;
  course: string;
  phoneCountry: string;
  phone: string;
  gender: string;
  motherTongue: string;
  dob: string | null;
  level: string | null;
  courseName: string | null;
  subscriptionPlan: string | null;
  subscriptionStatus: "active" | "expired";
  subscriptionStart: string | null;
  subscriptionEnd: string | null;
  createdAt: string | null;
  subscriptions?: NonNullable<StudentDashboardData["subscriptions"]>;
};

export type StudentCourseData = {
  id: string;
  courseId: string | null;
  courseName: string;
  courseSlug: string | null;
  levelId: string | null;
  levelName: string | null;
  subscriptionId: string;
  planName: string;
  amount: number;
  currency: string;
  status: "active" | "expired" | "cancelled";
  accessStart: string | null;
  accessEnd: string | null;
};

const API_BASE = getApiBase();

export async function fetchStudentDashboard(token: string): Promise<StudentDashboardData> {
  const response = await fetch(`${API_BASE}/api/student/dashboard`, {
    headers: {
      Authorization: `Bearer ${token}`,
    },
  });

  if (!response.ok) {
    const data = await response.json().catch(() => ({}));
    throw new Error((data as { message?: string }).message || "Failed to load dashboard");
  }

  return response.json();
}

export async function fetchStudentProfile(token: string): Promise<{ profile: StudentProfileData }> {
  const response = await fetch(`${API_BASE}/api/student/profile?_=${Date.now()}`, {
    headers: {
      Authorization: `Bearer ${token}`,
    },
  });

  if (!response.ok) {
    const data = await response.json().catch(() => ({}));
    throw new Error((data as { message?: string }).message || "Failed to load profile");
  }

  return response.json();
}

export async function updateStudentProfile(
  token: string,
  payload: Pick<StudentProfileData, "name" | "course" | "phoneCountry" | "phone" | "gender" | "motherTongue"> & { dob: string },
): Promise<{ message: string }> {
  const response = await fetch(`${API_BASE}/api/student/profile`, {
    method: "PATCH",
    headers: { "Content-Type": "application/json", Authorization: `Bearer ${token}` },
    body: JSON.stringify(payload),
  });
  const data = await response.json().catch(() => ({}));
  if (!response.ok) throw new Error((data as { message?: string }).message || "Failed to update profile");
  return data as { message: string };
}

export async function fetchStudentCourses(token: string): Promise<{ courses: StudentCourseData[] }> {
  const response = await fetch(`${API_BASE}/api/student/courses`, {
    headers: {
      Authorization: `Bearer ${token}`,
    },
  });

  if (!response.ok) {
    const data = await response.json().catch(() => ({}));
    throw new Error((data as { message?: string }).message || "Failed to load courses");
  }

  return response.json();
}

export async function changeStudentPassword(
  token: string,
  payload: { currentPassword: string; newPassword: string; confirmPassword: string },
): Promise<{ message: string }> {
  const response = await fetch(`${API_BASE}/api/auth/change-password`, {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      Authorization: `Bearer ${token}`,
    },
    body: JSON.stringify(payload),
  });

  const data = await response.json().catch(() => ({}));
  if (!response.ok) {
    throw new Error((data as { message?: string }).message || "Failed to change password");
  }

  return data as { message: string };
}
