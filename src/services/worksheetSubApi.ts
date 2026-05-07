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
};

export type WorksheetQuestion = {
  id: string;
  topic_id: string;
  question: string;
  answer: string;
};

export type WorksheetPractice = {
  id: string;
  student_id?: string;
  topic_id: string;
  score: number;
  accuracy: number;
  total_questions: number;
  correct_answers: number;
  time_taken: number;
  status: "Excellent" | "Good" | "Needs Practice";
  created_at: string;
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
};

const API_BASE = getApiBase();
const TOKEN_KEY = "abacus_auth_token";
const LOCAL_PRACTICES_KEY = "worksheet_sub_practices_v2";

const fallbackLevel: WorksheetLevel = {
  id: "abacus-senior-level-6",
  level_name: "Abacus Senior - Level 6",
};

const fallbackTopics: WorksheetTopic[] = [
  { id: "single-double-anzan", level_id: fallbackLevel.id, topic_name: "Multiplication - Single x Double digit (Anzan)", total_questions: 60 },
  { id: "four-digit-add-sub-1", level_id: fallbackLevel.id, topic_name: "Four digits - Addition / Subtraction (Anzan) - 1", total_questions: 40 },
  { id: "four-digit-add-sub-2", level_id: fallbackLevel.id, topic_name: "Four digits - Addition / Subtraction (Anzan) - 2", total_questions: 40 },
  { id: "four-digit-add-sub-3", level_id: fallbackLevel.id, topic_name: "Four digits - Addition / Subtraction (Anzan) - 3", total_questions: 50 },
  { id: "speed-addition-mixed", level_id: fallbackLevel.id, topic_name: "Speed Addition - Mixed Practice", total_questions: 35 },
  { id: "division-basic", level_id: fallbackLevel.id, topic_name: "Division - Double digit by Single digit", total_questions: 30 },
];

export function getFallbackDashboard(): WorksheetDashboardPayload {
  return { level: fallbackLevel, topics: fallbackTopics };
}

export function makeFallbackQuestions(topic: WorksheetTopic): WorksheetQuestion[] {
  return Array.from({ length: topic.total_questions }, (_, index) => {
    const number = index + 1;
    if (topic.topic_name.toLowerCase().includes("multiplication")) {
      const left = (index % 9) + 2;
      const right = 18 + ((index * 7) % 82);
      return {
        id: `${topic.id}-q-${number}`,
        topic_id: topic.id,
        question: `${left} x ${right}`,
        answer: String(left * right),
      };
    }

    if (topic.topic_name.toLowerCase().includes("division")) {
      const divisor = (index % 8) + 2;
      const answer = 11 + ((index * 5) % 60);
      return {
        id: `${topic.id}-q-${number}`,
        topic_id: topic.id,
        question: `${divisor * answer} / ${divisor}`,
        answer: String(answer),
      };
    }

    const first = 1200 + index * 23;
    const second = 185 + ((index * 31) % 720);
    const subtraction = index % 3 === 1;
    return {
      id: `${topic.id}-q-${number}`,
      topic_id: topic.id,
      question: subtraction ? `${first} - ${second}` : `${first} + ${second}`,
      answer: String(subtraction ? first - second : first + second),
    };
  });
}

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

export async function fetchWorksheetDashboard(): Promise<WorksheetDashboardPayload> {
  try {
    return await apiGet<WorksheetDashboardPayload>("/api/student/worksheet-sub");
  } catch {
    return getFallbackDashboard();
  }
}

export async function fetchWorksheetQuestions(topic: WorksheetTopic): Promise<WorksheetQuestion[]> {
  try {
    const data = await apiGet<{ questions: WorksheetQuestion[] }>(`/api/student/worksheet-sub/topics/${topic.id}/questions`);
    return data.questions;
  } catch {
    return makeFallbackQuestions(topic);
  }
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
    correct_answers: payload.correctAnswers,
    time_taken: payload.timeTaken,
    status,
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
    saveLocalPractice(localRecord);
    return localRecord;
  }
}
