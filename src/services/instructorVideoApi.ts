import { getApiBase } from "@/lib/apiBase";

const API_BASE = getApiBase();

export type InstructorVideoProgress = {
  currentPositionSeconds: number;
  maximumWatchedPositionSeconds: number;
  uniqueWatchedSeconds: number;
  completionPercentage: number;
  isCompleted: boolean;
  completedAt?: string | null;
};

export type InstructorTrainingVideo = {
  id: string;
  title: string;
  description?: string | null;
  program: "abacus" | "vedic_maths" | string;
  level: string;
  sequenceNumber: number;
  thumbnail?: string | null;
  durationSeconds: number;
  isUnlocked: boolean;
  lockedReason: string;
  progress: InstructorVideoProgress;
};

export type InstructorVideoDashboard = {
  subscription: {
    hasAccess: boolean;
    state: "none" | "active" | "expired" | "suspended";
    subscription: null | {
      id: string;
      planName: string;
      startDate: string;
      expiryDate: string;
      status: string;
      remainingDays: number;
    };
  };
  library: {
    videos: InstructorTrainingVideo[];
    summary: {
      totalVideos: number;
      completedVideos: number;
      remainingVideos: number;
      overallProgress: number;
    };
  };
  watermarkIdentity: {
    name: string;
    mobile: string;
    instructorId: string;
  };
};

export type PlaybackResponse = {
  video: InstructorTrainingVideo;
  playbackUrl: string;
  expiresAt: number;
  watermarkIdentity: InstructorVideoDashboard["watermarkIdentity"];
};

async function apiRequest<T>(path: string, token: string, options?: RequestInit): Promise<T> {
  const response = await fetch(`${API_BASE}${path}`, {
    ...options,
    headers: {
      "Content-Type": "application/json",
      Authorization: `Bearer ${token}`,
      ...(options?.headers || {}),
    },
  });
  const data = await response.json().catch(() => ({}));
  if (!response.ok) {
    throw new Error((data as { message?: string }).message || "Request failed");
  }
  return data as T;
}

export const getInstructorVideoDashboard = (token: string) =>
  apiRequest<InstructorVideoDashboard>("/api/tutor/training-videos", token);

export const requestInstructorVideoPlayback = (token: string, videoId: string) =>
  apiRequest<PlaybackResponse>(`/api/tutor/training-videos/${videoId}/playback`, token);

export const saveInstructorVideoProgress = (
  token: string,
  videoId: string,
  payload: {
    sessionId: string;
    currentPositionSeconds: number;
    maximumWatchedPositionSeconds: number;
    durationSeconds: number;
    segments: Array<{ start: number; end: number }>;
  },
) =>
  apiRequest<{ progress: InstructorVideoProgress; library: InstructorVideoDashboard["library"] }>(
    `/api/tutor/training-videos/${videoId}/progress`,
    token,
    {
      method: "POST",
      body: JSON.stringify(payload),
    },
  );
