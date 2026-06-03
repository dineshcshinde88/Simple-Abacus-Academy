import { getApiBase } from "@/lib/apiBase";

const API_BASE = getApiBase();

async function request<T>(path: string, options: RequestInit = {}): Promise<T> {
  const response = await fetch(`${API_BASE}${path}`, {
    ...options,
    headers: {
      "Content-Type": "application/json",
      ...(options.headers || {}),
    },
  });
  const data = await response.json().catch(() => ({}));
  if (!response.ok) {
    throw new Error((data as { message?: string }).message || "Request failed");
  }
  return data as T;
}

export type CompetitionUser = {
  id: string;
  name: string;
  email: string;
  role: "competition_student";
};

export const competitionRegister = (payload: {
  name: string;
  email: string;
  mobile?: string;
  city?: string;
  school?: string;
  gender?: string;
  dateOfBirth?: string;
  maatsCategory?: string;
  maatsSubcategory?: string;
  calculusGrade?: string;
  streetAddress?: string;
  state?: string;
  pinCode?: string;
  country?: string;
}) =>
  request<{ message: string }>("/api/competition/register", {
    method: "POST",
    body: JSON.stringify(payload),
  });

export const competitionLogin = (email: string, password: string) =>
  request<{ token: string; user: CompetitionUser }>("/api/competition/login", {
    method: "POST",
    body: JSON.stringify({ email, password }),
  });

export const competitionForgotPassword = (email: string) =>
  request<{ message: string }>("/api/competition/forgot-password", {
    method: "POST",
    body: JSON.stringify({ email }),
  });

export const competitionResetPassword = (email: string, token: string, password: string, confirmPassword: string) =>
  request<{ message: string }>("/api/competition/reset-password", {
    method: "POST",
    body: JSON.stringify({ email, token, password, confirmPassword }),
  });

export type CompetitionDashboardData = {
  profile: {
    id: string;
    name: string;
    email: string;
    school: string | null;
    gender: string | null;
    dateOfBirth: string | null;
    phone: string | null;
    maatsCategory: string | null;
    maatsSubcategory: string | null;
    calculusGrade: string | null;
    streetAddress: string | null;
    city: string | null;
    state: string | null;
    pinCode: string | null;
    country: string | null;
  };
  summary: {
    upcomingCompetitions: number;
    purchasedCompetitions: number;
    activeKits: number;
    expiredKits: number;
    examsCompleted: number;
    averageScore: number;
  };
  practiceKits: {
    id: string;
    accessId: string;
    title: string;
    description: string | null;
    startDate: string;
    expiryDate: string;
    accessStatus: "active" | "expired";
    remainingDays: number;
    validityDays: number;
  }[];
  upcomingCompetitions: CompetitionListItem[];
  completedCompetitions: CompetitionListItem[];
};

export type CompetitionListItem = {
  id: string;
  title: string;
  description: string | null;
  duration_minutes?: number;
  durationMinutes?: number;
  total_questions?: number;
  totalQuestions?: number;
  fee?: number;
  status: "scheduled" | "upcoming" | "live" | "completed" | string;
  category_name?: string | null;
  categoryName?: string | null;
  subcategory_name?: string | null;
  subcategoryName?: string | null;
  starts_at?: string | null;
  startsAt?: string | null;
  ends_at?: string | null;
  endsAt?: string | null;
};

export const getCompetitionDashboard = (token: string) =>
  request<CompetitionDashboardData>("/api/competition/student/dashboard", {
    headers: { Authorization: `Bearer ${token}` },
  });

export const getCompetitionList = () =>
  request<Pick<CompetitionDashboardData, "upcomingCompetitions" | "completedCompetitions">>("/api/competition/list");

export type CompetitionLeaderboardResponse = {
  competitions: {
    id: string;
    title: string;
    categoryName: string | null;
    subcategoryName: string | null;
  }[];
  participants: {
    rankPosition: number;
    name: string;
    marks: number;
    totalMarks: number;
    accuracy: number;
    completionTimeSeconds: number;
  }[];
};

export const getCompetitionLeaderboard = (competitionId = "") =>
  request<CompetitionLeaderboardResponse>(
    `/api/competition/leaderboard${competitionId ? `?competitionId=${encodeURIComponent(competitionId)}` : ""}`,
  );

export type CompetitionAdminOverview = {
  summary: {
    totalRegistrations: number;
    pendingApprovals: number;
    activeCompetitions: number;
    revenue: number;
    participantsCount: number;
    activePracticeKitAccess: number;
  };
  registrations: {
    id: string;
    name: string;
    email: string;
    mobile: string | null;
    city: string | null;
    school: string | null;
    status: "pending" | "approved" | "inactive";
    created_at: string;
  }[];
  categories: {
    id: string;
    name: string;
    slug: string;
    description: string | null;
    is_active: number;
  }[];
  subcategories: {
    id: string;
    category_id: string;
    category_name: string | null;
    name: string;
    slug: string;
    description: string | null;
    is_active: number;
  }[];
  competitions: {
    id: string;
    title: string;
    status: string;
    competition_date: string | null;
    start_time: string | null;
    end_time: string | null;
  }[];
};

export const getCompetitionAdminOverview = (token: string) =>
  request<CompetitionAdminOverview>("/api/admin/competition/overview", {
    headers: { Authorization: `Bearer ${token}` },
  });

export const approveCompetitionRegistration = (token: string, id: string) =>
  request<{ message: string; temporaryPassword: string }>(`/api/admin/competition/registrations/${id}/approve`, {
    method: "PUT",
    headers: { Authorization: `Bearer ${token}` },
  });
