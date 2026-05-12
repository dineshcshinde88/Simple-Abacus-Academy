import { useEffect, useMemo, useState } from "react";
import StudentLayout from "@/layouts/StudentLayout";
import { Button } from "@/components/ui/button";
import { useToast } from "@/hooks/use-toast";
import {
  createRazorpayOrder,
  getSubscriptionPlans,
  getSubscriptionSummary,
  LevelPlan,
  StudentSubscription,
  verifyRazorpayPayment,
} from "@/services/subscriptionApi";

const TOKEN_KEY = "abacus_auth_token";

const paymentStatusLabel = (status: StudentSubscription["paymentStatus"]) => (status === "paid" ? "Paid" : "Unpaid");

type RazorpayCheckoutResponse = {
  razorpay_payment_id: string;
  razorpay_order_id: string;
  razorpay_signature: string;
};

declare global {
  interface Window {
    Razorpay: new (options: Record<string, unknown>) => {
      open: () => void;
      on?: (event: string, handler: (response: { error?: { description?: string } }) => void) => void;
    };
  }
}

const loadRazorpayScript = async (): Promise<boolean> => {
  if (window.Razorpay) return true;

  return new Promise((resolve) => {
    const script = document.createElement("script");
    script.src = "https://checkout.razorpay.com/v1/checkout.js";
    script.async = true;
    script.onload = () => resolve(true);
    script.onerror = () => resolve(false);
    document.body.appendChild(script);
  });
};

const formatDate = (value?: string | null) => {
  if (!value) return "-";
  const d = new Date(value);
  if (Number.isNaN(d.getTime())) return value;
  return d.toLocaleDateString();
};

const StudentOrders = () => {
  const { toast } = useToast();
  const [loading, setLoading] = useState(true);
  const [processingPlanId, setProcessingPlanId] = useState<string | null>(null);
  const [plans, setPlans] = useState<LevelPlan[]>([]);
  const [current, setCurrent] = useState<StudentSubscription | null>(null);
  const [history, setHistory] = useState<StudentSubscription[]>([]);
  const [canPay, setCanPay] = useState(true);
  const [studentName, setStudentName] = useState("");
  const [studentEmail, setStudentEmail] = useState("");

  const token = localStorage.getItem(TOKEN_KEY) || "";

  const refreshData = async () => {
    if (!token) return;
    const [plansResp, summaryResp] = await Promise.all([getSubscriptionPlans(token), getSubscriptionSummary(token)]);
    setPlans(plansResp.plans || []);
    setCurrent(summaryResp.subscription?.current || null);
    setHistory(summaryResp.subscription?.history || []);
    setCanPay(summaryResp.canPay);
    setStudentName(summaryResp.student?.name || "");
    setStudentEmail(summaryResp.student?.email || "");
  };

  useEffect(() => {
    const run = async () => {
      if (!token) {
        setLoading(false);
        return;
      }

      try {
        await refreshData();
      } catch (error) {
        toast({
          title: "Unable to load subscriptions",
          description: error instanceof Error ? error.message : "Please try again later.",
        });
      } finally {
        setLoading(false);
      }
    };

    void run();
  }, [token]);

  const currentPlanByLevel = useMemo(() => {
    const map = new Map<string, StudentSubscription>();
    for (const sub of history) {
      if (sub.levelId && sub.status === "active" && sub.paymentStatus === "paid") {
        if (!map.has(sub.levelId)) map.set(sub.levelId, sub);
      }
    }
    return map;
  }, [history]);

  const handleBuy = async (plan: LevelPlan) => {
    if (!token) return;
    setProcessingPlanId(plan.id);

    try {
      const scriptReady = await loadRazorpayScript();
      if (!scriptReady || !window.Razorpay) {
        throw new Error("Unable to load Razorpay checkout.");
      }

      const orderResp = await createRazorpayOrder(token, plan.id);

      const razorpay = new window.Razorpay({
        key: orderResp.keyId,
        amount: orderResp.order.amount,
        currency: orderResp.order.currency,
        name: "Simple Abacus",
        description: `${orderResp.plan.name} subscription`,
        order_id: orderResp.order.id,
        prefill: {
          name: studentName,
          email: studentEmail,
        },
        notes: {
          planId: orderResp.plan.id,
          levelName: orderResp.plan.levelName || "",
        },
        handler: async (response: RazorpayCheckoutResponse) => {
          try {
            await verifyRazorpayPayment(token, {
              attemptId: orderResp.attemptId,
              razorpayOrderId: response.razorpay_order_id,
              razorpayPaymentId: response.razorpay_payment_id,
              razorpaySignature: response.razorpay_signature,
            });
            await refreshData();
            toast({
              title: "Subscription activated",
              description: `${orderResp.plan.name} is now active.`,
            });
          } catch (error) {
            toast({
              title: "Payment verification failed",
              description: error instanceof Error ? error.message : "Please contact support with payment reference.",
            });
          } finally {
            setProcessingPlanId(null);
          }
        },
      });

      if (typeof razorpay.on === "function") {
        razorpay.on("payment.failed", (response) => {
          toast({
            title: "Payment failed",
            description: response?.error?.description || "The payment could not be completed.",
          });
          setProcessingPlanId(null);
        });
      }

      razorpay.open();
    } catch (error) {
      toast({
        title: "Unable to start payment",
        description: error instanceof Error ? error.message : "Please try again.",
      });
      setProcessingPlanId(null);
    }
  };

  return (
    <StudentLayout
      header={(
        <div>
          <h1 className="text-2xl md:text-3xl font-heading font-bold text-slate-900">Subscriptions & Payments</h1>
          <p className="text-sm text-slate-500 mt-1">Purchase or renew level-wise subscription plans</p>
        </div>
      )}
    >
      {loading ? (
        <div className="bg-white rounded-2xl shadow-card p-6 text-slate-600">Loading subscriptions...</div>
      ) : (
        <div className="space-y-6">
          <div className="bg-white rounded-2xl shadow-card p-6">
            <h2 className="text-lg font-heading font-bold text-slate-900">Current Subscription</h2>
            {!current ? (
              <p className="mt-3 text-sm text-slate-600">No active subscription found. Choose a level plan below.</p>
            ) : (
              <div className="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4 text-sm">
                <div>
                  <div className="text-slate-500">Plan</div>
                  <div className="font-semibold text-slate-900">{current.planName}</div>
                </div>
                <div>
                  <div className="text-slate-500">Level</div>
                  <div className="font-semibold text-slate-900">{current.levelName || "Not set"}</div>
                </div>
                <div>
                  <div className="text-slate-500">Expiry</div>
                  <div className="font-semibold text-slate-900">{formatDate(current.expiryDate)}</div>
                </div>
                <div>
                  <div className="text-slate-500">Status</div>
                  <div className={`font-semibold ${current.status === "active" ? "text-emerald-600" : "text-red-600"}`}>
                    {current.status.toUpperCase()}
                  </div>
                </div>
              </div>
            )}

            {current?.status !== "active" && (
              <div className="mt-4 rounded-lg bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-800">
                Your subscription has expired. Please renew to restore access to level content.
              </div>
            )}

            {!canPay && (
              <div className="mt-4 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
                Online payment is currently unavailable. Please contact admin to configure Razorpay.
              </div>
            )}
          </div>

          <div className="bg-white rounded-2xl shadow-card p-6">
            <h2 className="text-lg font-heading font-bold text-slate-900">Level Plans</h2>
            <div className="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
              {plans.map((plan) => {
                const activeOnLevel = plan.levelId ? currentPlanByLevel.get(plan.levelId) : null;
                const buttonLabel = activeOnLevel ? "Renew Now" : "Subscribe Now";
                return (
                  <div key={plan.id} className="rounded-xl border border-slate-200 p-4">
                    <div className="text-sm text-slate-500">{plan.levelName || "Level plan"}</div>
                    <div className="text-lg font-semibold text-slate-900 mt-1">{plan.name}</div>
                    <div className="mt-2 text-sm text-slate-600">{plan.durationDays} days validity</div>
                    <div className="mt-3 text-xl font-heading font-bold text-[#5b21b6]">
                      {plan.currency} {plan.price.toFixed(2)}
                    </div>
                    {activeOnLevel && (
                      <div className="mt-2 text-xs text-emerald-700 bg-emerald-50 border border-emerald-200 px-2 py-1 rounded-md inline-block">
                        Active till {formatDate(activeOnLevel.expiryDate)}
                      </div>
                    )}
                    <Button
                      className="mt-4 w-full bg-slate-900 hover:bg-slate-800"
                      onClick={() => void handleBuy(plan)}
                      disabled={!canPay || processingPlanId === plan.id}
                    >
                      {processingPlanId === plan.id ? "Processing..." : buttonLabel}
                    </Button>
                  </div>
                );
              })}
            </div>
          </div>

          <div className="bg-white rounded-2xl shadow-card p-6">
            <h2 className="text-lg font-heading font-bold text-slate-900">Payment History</h2>
            {history.length === 0 ? (
              <p className="mt-3 text-sm text-slate-600">No payments yet.</p>
            ) : (
              <div className="mt-4 overflow-x-auto">
                <table className="w-full text-sm">
                  <thead>
                    <tr className="text-left text-slate-500 border-b">
                      <th className="py-2 pr-3">Plan</th>
                      <th className="py-2 pr-3">Level</th>
                      <th className="py-2 pr-3">Amount</th>
                      <th className="py-2 pr-3">Start</th>
                      <th className="py-2 pr-3">Expiry</th>
                      <th className="py-2 pr-3">Payment</th>
                      <th className="py-2">Status</th>
                    </tr>
                  </thead>
                  <tbody>
                    {history.map((row) => (
                      <tr key={row.id} className="border-b last:border-0 text-slate-700">
                        <td className="py-2 pr-3">{row.planName}</td>
                        <td className="py-2 pr-3">{row.levelName || "-"}</td>
                        <td className="py-2 pr-3">
                          {row.currency} {Number(row.amount || 0).toFixed(2)}
                        </td>
                        <td className="py-2 pr-3">{formatDate(row.startDate)}</td>
                        <td className="py-2 pr-3">{formatDate(row.expiryDate)}</td>
                        <td className="py-2 pr-3">{paymentStatusLabel(row.paymentStatus)}</td>
                        <td className="py-2">{row.status}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            )}
          </div>
        </div>
      )}
    </StudentLayout>
  );
};

export default StudentOrders;
