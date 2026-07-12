import { getApiBase } from "@/lib/apiBase";

export type WorksheetLevel = {
  id: string;
  level_name: string;
};

export type WorksheetTopic = {
  id: string;
  level_id: string;
  topic_name: string;
  total_questions: number;
  paper_id?: string | null;
  paper_number?: number | null;
  content_type?: "topic" | "paper";
  mode?: "vedic";
  competition?: {
    unlockedTier: number;
    passingPercentage: number;
    tiers: { seconds: number; unlocked: boolean; current: boolean }[];
  };
};

export type WorksheetQuestion = {
  id: string;
  topic_id: string;
  paper_id?: string | null;
  question: string;
  answer: string;
  options?: string[];
};

export type WorksheetAttemptReview = {
  questionId: string;
  questionNumber: number;
  questionText: string;
  studentAnswer?: string;
  selectedAnswer?: string;
  correctAnswer: string;
  isCorrect: boolean;
};

export type WorksheetPractice = {
  id: string;
  student_id?: string;
  topic_id: string;
  paper_id?: string | null;
  worksheet_name?: string;
  score: number;
  accuracy: number;
  percentage?: number;
  total_questions: number;
  attempted?: number;
  correct_answers: number;
  wrong_answers?: number;
  time_taken: number;
  duration_seconds?: number;
  status: "Excellent" | "Good" | "Needs Practice" | "Pass" | "Fail";
  mode?: "practice" | "visualization" | "competition";
  speed_tier?: number | null;
  started_at?: string | null;
  completed_at?: string | null;
  created_at: string;
  review?: WorksheetAttemptReview[];
};
export type WorksheetDashboardPayload = {
  level: WorksheetLevel;
  topics: WorksheetTopic[];
};

type SubmitPracticePayload = {
  topicId: string;
  score: number;
  accuracy: number;
  totalQuestions: number;
  correctAnswers: number;
  timeTaken: number;
  mode?: "practice" | "visualization" | "competition";
  speedTier?: number | null;
  contentType?: "topic" | "paper";
  answers?: Record<string, string>;
  startedAt?: string;
  durationSeconds?: number;
};
const API_BASE = getApiBase();
const TOKEN_KEY = "abacus_auth_token";
const LOCAL_PRACTICES_KEY = "worksheet_sub_practices_v2";

function authHeaders(): HeadersInit {
  const token = typeof window !== "undefined" ? window.localStorage.getItem(TOKEN_KEY) : null;
  return token ? { Authorization: `Bearer ${token}` } : {};
}

async function apiGet<T>(path: string): Promise<T> {
  const response = await fetch(`${API_BASE}${path}`, { headers: authHeaders() });
  if (!response.ok) {
    const data = await response.json().catch(() => ({}));
    throw new Error((data as { message?: string }).message || "Worksheet API request failed");
  }
  return response.json();
}

export async function fetchWorksheetDashboard(levelId?: string | null): Promise<WorksheetDashboardPayload> {
  const query = levelId ? `?levelId=${encodeURIComponent(levelId)}` : "";
  return apiGet<WorksheetDashboardPayload>(`/api/student/worksheet-sub${query}`);
}

export async function fetchWorksheetQuestions(
  topic: WorksheetTopic,
  options?: { mode?: "practice" | "competition"; speedTier?: number | null },
): Promise<WorksheetQuestion[]> {
  const params = new URLSearchParams();
  if (options?.mode) params.set("mode", options.mode);
  if (options?.speedTier) params.set("speedTier", String(options.speedTier));
  const query = params.toString() ? `?${params.toString()}` : "";
  const data = await apiGet<{ questions: WorksheetQuestion[] }>(`/api/student/worksheet-sub/topics/${topic.id}/questions${query}`);
  return data.questions.map((question) => ({ ...question, options: shuffleOptions(resolveQuestionOptions(question)) }));
}

function resolveQuestionOptions(question: WorksheetQuestion): string[] {
  const answer = String(question.answer ?? "").trim();
  const existing = (question.options || []).map((item) => String(item).trim()).filter(Boolean);
  const unique = Array.from(new Set([...existing, answer].filter(Boolean)));
  if (unique.length >= 4) return unique.slice(0, 4);

  const answerNumber = Number(answer);
  if (!Number.isNaN(answerNumber)) {
    const candidates = [answerNumber + 1, answerNumber - 1, answerNumber + 2, answerNumber - 2, answerNumber + 10, answerNumber - 10]
      .map((value) => String(value));
    for (const candidate of candidates) {
      if (!unique.includes(candidate)) unique.push(candidate);
      if (unique.length >= 4) break;
    }
  }

  let suffix = 1;
  while (unique.length < 4) {
    const candidate = answer ? `${answer}${suffix}` : String(suffix);
    if (!unique.includes(candidate)) unique.push(candidate);
    suffix += 1;
  }
  return unique;
}
function shuffleOptions(options: string[]): string[] {
  const shuffled = [...options];
  for (let i = shuffled.length - 1; i > 0; i -= 1) {
    const j = Math.floor(Math.random() * (i + 1));
    [shuffled[i], shuffled[j]] = [shuffled[j], shuffled[i]];
  }
  return shuffled;
}

export function loadLocalPractices(topicId: string): WorksheetPractice[] {
  if (typeof window === "undefined") return [];
  const stored = window.localStorage.getItem(LOCAL_PRACTICES_KEY);
  if (!stored) return [];
  try {
    const all = JSON.parse(stored) as WorksheetPractice[];
    return all.filter((item) => item.topic_id === topicId);
  } catch {
    window.localStorage.removeItem(LOCAL_PRACTICES_KEY);
    return [];
  }
}

function saveLocalPractice(record: WorksheetPractice): void {
  const stored = window.localStorage.getItem(LOCAL_PRACTICES_KEY);
  const all = stored ? (JSON.parse(stored) as WorksheetPractice[]) : [];
  window.localStorage.setItem(LOCAL_PRACTICES_KEY, JSON.stringify([record, ...all]));
}

export async function fetchWorksheetPractices(topicId: string): Promise<WorksheetPractice[]> {
  try {
    const data = await apiGet<{ practices: WorksheetPractice[] }>(`/api/student/worksheet-sub/topics/${topicId}/practices`);
    return data.practices.length ? data.practices : loadLocalPractices(topicId);
  } catch {
    return loadLocalPractices(topicId);
  }
}

export async function submitWorksheetPractice(payload: SubmitPracticePayload): Promise<WorksheetPractice> {
  const status = payload.accuracy >= 90 ? "Excellent" : payload.accuracy >= 70 ? "Good" : "Needs Practice";
  const localRecord: WorksheetPractice = {
    id: `local-${Date.now()}`,
    topic_id: payload.topicId,
    score: payload.score,
    accuracy: payload.accuracy,
    total_questions: payload.totalQuestions,
    attempted: Object.values(payload.answers || {}).filter((value) => String(value).trim() !== "").length,
    correct_answers: payload.correctAnswers,
    wrong_answers: Math.max(0, Object.values(payload.answers || {}).filter((value) => String(value).trim() !== "").length - payload.correctAnswers),
    time_taken: payload.timeTaken,
    duration_seconds: payload.durationSeconds ?? payload.timeTaken,
    status,
    mode: payload.mode || "practice",
    speed_tier: payload.speedTier ?? null,
    started_at: payload.startedAt || null,
    completed_at: new Date().toISOString(),
    created_at: new Date().toISOString(),
  };

  try {
    const response = await fetch(`${API_BASE}/api/student/worksheet-sub/practices`, {
      method: "POST",
      headers: { "Content-Type": "application/json", ...authHeaders() },
      body: JSON.stringify(payload),
    });
    if (!response.ok) throw new Error("Practice save failed");
    const data = (await response.json()) as { practice: WorksheetPractice };
    return data.practice;
  } catch {
    if (payload.contentType === "paper") {
      throw new Error("Result save failed. Please check your connection and submit again.");
    }
    saveLocalPractice(localRecord);
    return localRecord;
  }
}