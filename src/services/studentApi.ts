import { getApiBase } from "@/lib/apiBase";

export type StudentDashboardData = {
  name: string;
  level: string | null;
  batchesCount: number;
  worksheetsCount: number;
  videosCount: number;
  subscriptionStatus: "active" | "expired";
  startDate: string | null;
  expiryDate: string | null;
  practice?: {
    purchasedLevels: number;
    completedPapers: number;
    pendingPapers: number;
    averageAccuracy: number;
  };
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
