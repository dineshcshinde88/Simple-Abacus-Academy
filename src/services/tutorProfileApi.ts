import { getApiBase } from "@/lib/apiBase";

export type TutorProfile = {
  name: string;
  email: string;
  avatarUrl: string | null;
};

const API_BASE = getApiBase();

async function parseResponse(response: Response): Promise<TutorProfile> {
  const data = await response.json().catch(() => ({}));
  if (!response.ok) {
    throw new Error((data as { message?: string }).message || "Instructor profile could not be saved.");
  }
  return (data as { profile: TutorProfile }).profile;
}

export async function fetchTutorProfile(token: string): Promise<TutorProfile> {
  return parseResponse(await fetch(`${API_BASE}/api/tutor/profile`, {
    headers: { Authorization: `Bearer ${token}` },
  }));
}

export async function saveTutorProfile(token: string, profile: TutorProfile, profilePicture?: File | null): Promise<TutorProfile> {
  const body = new FormData();
  body.append("name", profile.name);
  body.append("email", profile.email);
  if (profilePicture) body.append("profilePicture", profilePicture);

  return parseResponse(await fetch(`${API_BASE}/api/tutor/profile`, {
    method: "PATCH",
    headers: { Authorization: `Bearer ${token}` },
    body,
  }));
}
