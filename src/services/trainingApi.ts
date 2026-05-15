import { getApiBase } from "@/lib/apiBase";

const API_BASE = getApiBase();

const request = async <T>(path: string, options: RequestInit = {}, token?: string): Promise<T> => {
  const headers: HeadersInit = {
    "Content-Type": "application/json",
    ...(options.headers || {}),
  };
  if (token) headers.Authorization = `Bearer ${token}`;

  const res = await fetch(`${API_BASE}${path}`, { ...options, headers });
  if (!res.ok) throw new Error("Request failed");
  return res.json();
};

export const trainingLogin = (email: string, password: string) =>
  request<{ token: string; user: any }>("/api/training/auth/login", {
    method: "POST",
    body: JSON.stringify({ email, password }),
  });

export const trainingRegister = (name: string, email: string, password: string, role: "teacher" | "admin") =>
  request<{ token?: string; user: any; message?: string }>("/api/training/auth/register", {
    method: "POST",
    body: JSON.stringify({ name, email, password, role }),
  });

export const trainingMe = (token: string) => request<{ user: any }>("/api/training/auth/me", {}, token);

export const teacherDashboard = (token: string) => request<any>("/api/training/teacher/dashboard", {}, token);

export const teacherAddStudent = (token: string, payload: any) =>
  request<any>("/api/training/teacher/students", { method: "POST", body: JSON.stringify(payload) }, token);

export const teacherUpdateStudent = (token: string, id: string, payload: any) =>
  request<any>(`/api/training/teacher/students/${id}`, { method: "PUT", body: JSON.stringify(payload) }, token);

export type TrainingShopOrderPayload = {
  productId: string;
  productName: string;
  category: string;
  selectedOption: string;
  optionLabel: string;
  quantity: number;
  unitPrice: number;
  finalPrice: number;
};

export type TrainingShopOrder = TrainingShopOrderPayload & {
  id: string;
  invoiceNumber: string;
  paymentStatus: "pending" | "successful" | "failed";
  paymentUrl?: string;
  razorpayOrderId?: string | null;
  razorpayPaymentId?: string | null;
  createdAt: string;
};

export const teacherShopOrders = (token: string) =>
  request<{ orders: TrainingShopOrder[] }>("/api/training/teacher/shop/orders", {}, token);

export const teacherCreateShopOrder = (token: string, payload: TrainingShopOrderPayload) =>
  request<{ order: TrainingShopOrder; paymentUrl: string }>("/api/training/teacher/shop/orders", {
    method: "POST",
    body: JSON.stringify(payload),
  }, token);

export const teacherCompleteShopOrderPayment = (token: string, orderId: string) =>
  request<{
    order: TrainingShopOrder;
    keyId: string;
    razorpayOrder: { id: string; amount: number; currency: string };
  }>(`/api/training/teacher/shop/orders/${orderId}/pay`, {
    method: "POST",
  }, token);

export const teacherVerifyShopOrderPayment = (
  token: string,
  orderId: string,
  payload: {
    razorpayOrderId: string;
    razorpayPaymentId: string;
    razorpaySignature: string;
  },
) =>
  request<{ order: TrainingShopOrder }>(`/api/training/teacher/shop/orders/${orderId}/verify`, {
    method: "POST",
    body: JSON.stringify(payload),
  }, token);

export const adminListTeachers = (token: string) => request<any>("/api/training/admin/teachers", {}, token);
export const adminApproveTeacher = (token: string, id: string) =>
  request<any>(`/api/training/admin/teachers/${id}/approve`, { method: "PUT" }, token);

export const adminListStudents = (token: string) => request<any>("/api/training/admin/students", {}, token);
