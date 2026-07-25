import { getApiBase } from "@/lib/apiBase";

const API_BASE = getApiBase();

type ApiOptions = {
  method?: "GET" | "POST";
  token: string;
  body?: unknown;
};

async function apiRequest<T>(path: string, options: ApiOptions): Promise<T> {
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

export type LevelPlan = {
  id: string;
  name: string;
  levelId: string | null;
  levelName: string | null;
  courseName?: string | null;
  courseSlug?: string | null;
  durationDays: number;
  price: number;
  currency: string;
  isActive: boolean;
};

export type StudentSubscription = {
  id: string;
  planId: string | null;
  planName: string;
  levelId: string | null;
  levelName: string | null;
  amount: number;
  currency: string;
  startDate: string | null;
  expiryDate: string | null;
  status: "active" | "expired" | "cancelled";
  paymentStatus: "paid" | "unpaid";
  razorpayOrderId: string | null;
  razorpayPaymentId: string | null;
  createdAt: string | null;
  updatedAt: string | null;
};

export type SubscriptionSummaryResponse = {
  student: {
    id: string;
    name: string;
    email: string;
    levelId: string | null;
    levelName: string | null;
  };
  subscription: {
    current: StudentSubscription | null;
    history: StudentSubscription[];
  };
  canPay: boolean;
};

export type SubscriptionOrderItem = {
  id: string;
  planId: string;
  planName: string;
  programType: "abacus" | "vedic_maths";
  programName: string;
  levelId: string;
  levelName: string | null;
  amount: number;
  durationDays: number;
  status: "pending" | "activated" | "failed";
  subscriptionId: string | null;
};

export type SubscriptionOrder = {
  id: string;
  providerOrderId: string | null;
  subtotal: number;
  discount: number;
  totalAmount: number;
  currency: string;
  paymentStatus: "created" | "paid" | "paid_with_activation_pending" | "failed";
  createdAt: string;
  paidAt: string | null;
  items: SubscriptionOrderItem[];
};
export type RazorpayOrderResponse = {
  attemptId: string;
  keyId: string;
  order: {
    id: string;
    amount: number;
    currency: string;
  };
  plan: {
    id: string;
    name: string;
    levelId: string | null;
    levelName: string | null;
    durationDays: number;
    price: number;
    currency: string;
  };
  plans?: RazorpayOrderResponse["plan"][];
};

export const getSubscriptionPlans = (token: string) =>
  apiRequest<{ plans: LevelPlan[] }>("/api/student/subscriptions/plans", { token });

export const getPublicSubscriptionPlans = async () => {
  const response = await fetch(`${API_BASE}/api/subscriptions/plans/public`);
  const data = await response.json().catch(() => ({}));
  if (!response.ok) {
    throw new Error((data as { message?: string }).message || "Unable to load subscription plans");
  }
  return data as { plans: LevelPlan[] };
};

export const getSubscriptionSummary = (token: string) =>
  apiRequest<SubscriptionSummaryResponse>("/api/student/subscriptions/summary", { token });

export const getSubscriptionOrders = (token: string) =>
  apiRequest<{ orders: SubscriptionOrder[] }>("/api/student/subscription-orders", { token });
export const createRazorpayOrder = (token: string, planId: string | string[]) =>
  apiRequest<RazorpayOrderResponse>("/api/student/subscriptions/create-order", {
    method: "POST",
    token,
    body: Array.isArray(planId) ? { planIds: planId } : { planId },
  });

export const ensureWorksheetPlan = (
  token: string,
  payload: { courseSlug: "abacus-worksheet" | "vedic-maths-worksheet"; level: string; durationDays: number },
) =>
  apiRequest<{ plan: LevelPlan }>("/api/student/subscriptions/ensure-plan", {
    method: "POST",
    token,
    body: payload,
  });

export const verifyRazorpayPayment = (
  token: string,
  payload: {
    attemptId: string;
    razorpayOrderId: string;
    razorpayPaymentId: string;
    razorpaySignature: string;
  },
) =>
  apiRequest<{
    message: string;
    activationStatus?: "activated" | "pending_manual_review";
    allocationStatus?: string | null;
    allocationError?: string | null;
    paymentStatus?: string;
    subscription: SubscriptionSummaryResponse["subscription"];
  }>(
    "/api/student/subscriptions/verify",
    {
      method: "POST",
      token,
      body: payload,
    },
  );
