import { getApiBase } from "@/lib/apiBase";
import type { Batch, ClassSession } from "@/context/InstructorDashboardContext";
import type { Student } from "@/context/InstructorDashboardContext";

const API_BASE = getApiBase();

async function request<T>(token: string, path: string, options: RequestInit = {}): Promise<T> {
  const response = await fetch(`${API_BASE}${path}`, {
    ...options,
    headers: { "Content-Type": "application/json", Authorization: `Bearer ${token}`, ...(options.headers || {}) },
  });
  const data = await response.json().catch(() => ({}));
  if (!response.ok) throw new Error((data as { message?: string }).message || "Batch request failed");
  return data as T;
}

export type StudentBatch = Omit<Batch, "studentIds"> & { classes: ClassSession[] };

export const fetchTutorBatches = (token: string) => request<{ batches: Batch[]; classes: ClassSession[] }>(token, "/api/tutor/batches");
export const fetchTutorStudentsForBatches = async (token: string): Promise<Student[]> => {
  const data = await request<{ students: Array<Record<string, unknown>> }>(token, "/api/tutor/students");
  return data.students.map((row) => {
    const user = (row.user || {}) as Record<string, unknown>;
    return {
      id: String(row.id || ""),
      name: String(user.name || "Student"),
      email: String(user.email || ""),
      level: String(row.level_name || "Not assigned"),
      batchId: null,
      feesStatus: "unpaid",
      joinedAt: String(row.created_at || ""),
      progress: { marks: 0, levelCompleted: 0, status: "Average" },
    };
  });
};
export const createTutorStudent = async (token: string, payload: Omit<Student, "id" | "progress"> & { password: string }): Promise<Student> => {
  // The student endpoint does not persist profile images. Avoid sending a potentially
  // multi-megabyte data URL that can make production proxies reject the JSON request.
  const apiPayload = { ...payload, avatarUrl: undefined };
  const data = await request<{ student: Record<string, unknown> }>(token, "/api/tutor/add-student", {
    method: "POST",
    body: JSON.stringify(apiPayload),
  });
  const row = data.student;
  return {
    id: String(row.id || ""),
    name: String(row.user_name || payload.name),
    email: String(row.user_email || payload.email),
    parentEmail: payload.parentEmail,
    parentMobile: payload.parentMobile,
    whatsappNumber: payload.whatsappNumber,
    dateOfBirth: payload.dateOfBirth,
    gender: payload.gender,
    course: payload.course,
    avatarUrl: payload.avatarUrl,
    level: payload.level,
    batchId: payload.batchId,
    feesStatus: payload.feesStatus,
    joinedAt: String(row.created_at || payload.joinedAt),
    levelStartDate: payload.levelStartDate,
    levelEndDate: payload.levelEndDate,
    progress: { marks: 0, levelCompleted: 0, status: "Average" },
  };
};
export const createTutorBatch = (token: string, batch: Omit<Batch, "id" | "studentIds">) => request<{ batch: Batch }>(token, "/api/tutor/batches", { method: "POST", body: JSON.stringify(batch) });
export const removeTutorBatch = (token: string, id: string) => request<{ message: string }>(token, `/api/tutor/batches/${id}`, { method: "DELETE" });
export const assignTutorBatchStudent = (token: string, batchId: string, studentId: string) => request<{ message: string }>(token, `/api/tutor/batches/${batchId}/students`, { method: "POST", body: JSON.stringify({ studentId }) });
export const createTutorClass = (token: string, session: Omit<ClassSession, "id" | "attendance">) => request<{ class: ClassSession }>(token, "/api/tutor/classes", { method: "POST", body: JSON.stringify(session) });
export const toggleTutorAttendance = (token: string, classId: string, studentId: string) => request<{ present: boolean }>(token, `/api/tutor/classes/${classId}/attendance/${studentId}`, { method: "PATCH" });
export const fetchStudentBatches = (token: string) => request<{ batches: StudentBatch[] }>(token, "/api/student/batches");
