import { beforeEach, describe, expect, it, vi } from "vitest";
import { formatStoredCompletion } from "./completionTimestamp";
import { fetchWorksheetPractices, submitWorksheetPractice } from "@/services/worksheetSubApi";

const storedTimestamp = "2026-07-24 16:45:20";
const practice = {
  id: "attempt-1", topic_id: "topic-1", score: 60, accuracy: 100, total_questions: 60,
  correct_answers: 60, time_taken: 120, status: "Excellent" as const,
  completed_at: storedTimestamp, created_at: storedTimestamp,
};

describe("stored completion timestamps", () => {
  beforeEach(() => {
    window.localStorage.clear();
    vi.restoreAllMocks();
  });

  it("formats the stored server value without generating a current timestamp", () => {
    expect(formatStoredCompletion(storedTimestamp)).toEqual({
      date: "24/07/2026", time: "04:45:20 PM", completedAt: "24/07/2026, 04:45:20 PM",
    });
    expect(formatStoredCompletion(null).completedAt).toBe("-");
  });

  it("returns the exact completed_at supplied by the save API", async () => {
    vi.stubGlobal("fetch", vi.fn().mockResolvedValue({ ok: true, json: async () => ({ practice }) }));
    const saved = await submitWorksheetPractice({
      topicId: "topic-1", score: 60, accuracy: 100, totalQuestions: 60,
      correctAnswers: 60, timeTaken: 120, mode: "practice", answers: {},
    });
    expect(saved.completed_at).toBe(storedTimestamp);
  });

  it("uses the same database timestamp in previous-attempt history", async () => {
    vi.stubGlobal("fetch", vi.fn().mockResolvedValue({ ok: true, json: async () => ({ practices: [practice] }) }));
    const history = await fetchWorksheetPractices("topic-1");
    expect(history[0].completed_at).toBe(storedTimestamp);
    expect(formatStoredCompletion(history[0].completed_at).completedAt).toBe("24/07/2026, 04:45:20 PM");
  });

  it("rejects a failed save and does not create a fake local result", async () => {
    vi.stubGlobal("fetch", vi.fn().mockResolvedValue({ ok: false, json: async () => ({}) }));
    await expect(submitWorksheetPractice({
      topicId: "topic-1", score: 0, accuracy: 0, totalQuestions: 60,
      correctAnswers: 0, timeTaken: 10, mode: "visualization", answers: {},
    })).rejects.toThrow("Result save failed");
    expect(window.localStorage.getItem("worksheet_sub_practices_v2")).toBeNull();
  });
});
