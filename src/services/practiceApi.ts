import { getApiBase } from "@/lib/apiBase";

const API_BASE = getApiBase();

type RequestOptions = {
  method?: "GET" | "POST" | "PUT";
  token: string;
  body?: unknown;
};

async function request<T>(path: string, options: RequestOptions): Promise<T> {
  const response = await fetch(`${API_BASE}${path}`, {
    method: options.method || "GET",
    headers: {
      "Content-Type": "application/json",
      Authorization: `Bearer ${options.token}`,
    },
    body: options.body ? JSON.stringify(options.body) : undefined,
  });
  const data = await response.json().catch(() => ({}));
  if (!response.ok) {
    throw new Error((data as { message?: string }).message || "Request failed");
  }
  return data as T;
}

export type PracticePaperSummary = {
  id: string;
  title: string;
  paperNumber: number;
  questionCount: number;
  timerSeconds: number;
  status: "not_started" | "in_progress" | "submitted";
  bestScore: number | null;
  bestAccuracy: number | null;
  attemptsCount: number;
  completedAttempts: number;
};

export type PracticeLevel = {
  id: string;
  name: string;
  slug: string;
  timerSeconds: number;
  unlocked: boolean;
  lockedMessage: string | null;
  papers: PracticePaperSummary[];
};

export type PracticeSummary = {
  completedPapers: number;
  attempts: number;
  averageAccuracy: number;
  bestScore: number;
};

export type PracticeQuestion = {
  id: string;
  questionNumber: number;
  questionText: string;
  options: string[];
};

export type PracticePaper = {
  id: string;
  title: string;
  levelId: string;
  levelName: string;
  paperNumber: number;
  questionCount: number;
  timerSeconds: number;
};

export type PracticeResult = {
  id: string;
  paperId: string;
  paperTitle: string;
  paperNumber: number;
  levelName: string;
  totalQuestions: number;
  correctCount: number;
  wrongCount: number;
  score: number;
  accuracy: number;
  timeTakenSeconds: number;
  completed_at: string;
  review: {
    questionId: string;
    questionNumber: number;
    questionText: string;
    selectedAnswer: string;
    correctAnswer: string;
    isCorrect: boolean;
    explanation: string;
  }[];
};

export const getPracticeLevels = (token: string) =>
  request<{ levels: PracticeLevel[]; summary: PracticeSummary }>("/api/student/practice/levels", { token });

export const getPracticePaper = (token: string, paperId: string) =>
  request<{
    paper: PracticePaper;
    progress: {
      status: string;
      answers: Record<string, string>;
      lastQuestionNumber: number;
      timeSpentSeconds: number;
    };
    questions: PracticeQuestion[];
  }>(`/api/student/practice/papers/${paperId}`, { token });

export const savePracticeProgress = (
  token: string,
  payload: { paperId: string; answers: Record<string, string>; lastQuestionNumber: number; timeSpentSeconds: number },
) => request<{ message: string }>("/api/student/practice/progress", { method: "POST", token, body: payload });

export const submitPracticePaper = (
  token: string,
  payload: { paperId: string; answers: Record<string, string>; timeTakenSeconds: number },
) => request<{ message: string; result: PracticeResult }>("/api/student/practice/submit", { method: "POST", token, body: payload });

export type AdminPracticeOverview = {
  levels: {
    id: string;
    name: string;
    slug: string;
    timerSeconds: number;
    paperCount: number;
    questionCount: number;
  }[];
  results: {
    id: string;
    studentName: string;
    studentEmail: string;
    levelName: string;
    paperTitle: string;
    score: number;
    totalQuestions: number;
    accuracy: number;
    timeTakenSeconds: number;
    submittedAt: string;
  }[];
};

export const getAdminPracticeOverview = (token: string) =>
  request<AdminPracticeOverview>("/api/admin/practice/overview", { token });

export const importDefaultPracticeDocs = (token: string) =>
  request<{ message: string; imports: Record<string, unknown> }>("/api/admin/practice/import-defaults", {
    method: "POST",
    token,
  });

export const updatePracticeLevel = (token: string, levelId: string, payload: { name: string; timerSeconds: number }) =>
  request<{ level: unknown }>(`/api/admin/practice/levels/${levelId}`, { method: "PUT", token, body: payload });

export const uploadPracticeDocx = async (token: string, levelId: string, file: File) => {
  const form = new FormData();
  form.append("levelId", levelId);
  form.append("file", file);
  const response = await fetch(`${API_BASE}/api/admin/practice/upload-docx`, {
    method: "POST",
    headers: { Authorization: `Bearer ${token}` },
    body: form,
  });
  const data = await response.json().catch(() => ({}));
  if (!response.ok) {
    throw new Error((data as { message?: string }).message || "Upload failed");
  }
  return data as { message: string };
};
