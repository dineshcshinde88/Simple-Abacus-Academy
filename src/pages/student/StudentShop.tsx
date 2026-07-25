import { useEffect, useMemo, useState } from "react";
import StudentLayout from "@/layouts/StudentLayout";
import { Button } from "@/components/ui/button";
import { Checkbox } from "@/components/ui/checkbox";
import { useToast } from "@/hooks/use-toast";
import { ShoppingCart, Trash2 } from "lucide-react";
import { calculateCartTotal, toggleCartPlan } from "@/lib/shopCart";
import {
  createRazorpayOrder,
  getSubscriptionPlans,
  getSubscriptionSummary,
  LevelPlan,
  StudentSubscription,
  verifyRazorpayPayment,
} from "@/services/subscriptionApi";

const TOKEN_KEY = "abacus_auth_token";
const CART_KEY = "worksheet_subscription_cart_v1";

type RazorpayCheckoutResponse = { razorpay_payment_id: string; razorpay_order_id: string; razorpay_signature: string };
declare global { interface Window { Razorpay: new (options: Record<string, unknown>) => { open: () => void; on?: (event: string, handler: (response: { error?: { description?: string } }) => void) => void } } }

const loadRazorpayScript = async () => {
  if (window.Razorpay) return true;
  return new Promise<boolean>((resolve) => {
    const script = document.createElement("script");
    script.src = "https://checkout.razorpay.com/v1/checkout.js";
    script.async = true;
    script.onload = () => resolve(true);
    script.onerror = () => resolve(false);
    document.body.appendChild(script);
  });
};
const planText = (plan: LevelPlan) => `${plan.courseSlug || ""} ${plan.courseName || ""} ${plan.levelName || ""} ${plan.name}`;
const isWorksheetPlan = (plan: LevelPlan) => /worksheet/i.test(planText(plan));
const programName = (plan: LevelPlan) => /vedic/i.test(planText(plan)) ? "Vedic Maths" : "Abacus";
const levelOrder = (plan: LevelPlan) => /foundation/i.test(planText(plan)) ? 0 : Number(planText(plan).match(/level\s*(\d+)/i)?.[1] || 999);
const sortPlans = (plans: LevelPlan[]) => [...plans].sort((a, b) => programName(a).localeCompare(programName(b)) || levelOrder(a) - levelOrder(b) || a.durationDays - b.durationDays);
const money = (amount: number, currency = "INR") => `${currency} ${amount.toFixed(2)}`;
const formatDate = (value?: string | null) => value ? new Intl.DateTimeFormat("en-IN", { dateStyle: "medium" }).format(new Date(value)) : "-";

export default function StudentShop() {
  const { toast } = useToast();
  const token = localStorage.getItem(TOKEN_KEY) || "";
  const [loading, setLoading] = useState(true);
  const [processing, setProcessing] = useState(false);
  const [plans, setPlans] = useState<LevelPlan[]>([]);
  const [history, setHistory] = useState<StudentSubscription[]>([]);
  const [selectedIds, setSelectedIds] = useState<string[]>(() => {
    try { return JSON.parse(sessionStorage.getItem(CART_KEY) || "[]"); } catch { return []; }
  });
  const [canPay, setCanPay] = useState(true);
  const [studentName, setStudentName] = useState("");
  const [studentEmail, setStudentEmail] = useState("");

  const refresh = async () => {
    const [planResponse, summary] = await Promise.all([getSubscriptionPlans(token), getSubscriptionSummary(token)]);
    const nextPlans = sortPlans((planResponse.plans || []).filter(isWorksheetPlan));
    setPlans(nextPlans);
    setHistory(summary.subscription?.history || []);
    setCanPay(summary.canPay);
    setStudentName(summary.student?.name || "");
    setStudentEmail(summary.student?.email || "");
    setSelectedIds((ids) => ids.filter((id) => nextPlans.some((plan) => plan.id === id)));
  };

  useEffect(() => { if (!token) { setLoading(false); return; } void refresh().catch((error) => toast({ title: "Unable to load shop", description: error instanceof Error ? error.message : "Please try again." })).finally(() => setLoading(false)); }, [token]);
  useEffect(() => { sessionStorage.setItem(CART_KEY, JSON.stringify(selectedIds)); }, [selectedIds]);

  const activePlanIds = useMemo(() => new Set(history.filter((sub) => sub.status === "active" && sub.paymentStatus === "paid" && (!sub.expiryDate || new Date(sub.expiryDate).getTime() >= Date.now())).map((sub) => sub.planId).filter(Boolean)), [history]);
  const latestByPlan = useMemo(() => new Map(history.filter((sub) => sub.planId).map((sub) => [sub.planId as string, sub])), [history]);
  const selectedPlans = useMemo(() => selectedIds.map((id) => plans.find((plan) => plan.id === id)).filter((plan): plan is LevelPlan => Boolean(plan)), [plans, selectedIds]);
  const subtotal = calculateCartTotal(plans, selectedIds);
  const currency = selectedPlans[0]?.currency || "INR";

  const toggle = (plan: LevelPlan) => {
    if (activePlanIds.has(plan.id)) { toast({ title: "This subscription is already active." }); return; }
    setSelectedIds((ids) => toggleCartPlan(ids, plan.id));
  };

  const checkout = async () => {
    if (!selectedIds.length) { toast({ title: "Please select at least one subscription." }); return; }
    setProcessing(true);
    try {
      if (!(await loadRazorpayScript()) || !window.Razorpay) throw new Error("Unable to load Razorpay checkout.");
      const response = await createRazorpayOrder(token, selectedIds);
      const checkoutInstance = new window.Razorpay({
        key: response.keyId, amount: response.order.amount, currency: response.order.currency, name: "Simple Abacus",
        description: `${response.plans?.length || 1} worksheet subscription${(response.plans?.length || 1) === 1 ? "" : "s"}`,
        order_id: response.order.id, prefill: { name: studentName, email: studentEmail },
        notes: { attemptId: response.attemptId },
        handler: async (payment: RazorpayCheckoutResponse) => {
          try {
            const verified = await verifyRazorpayPayment(token, { attemptId: response.attemptId, razorpayOrderId: payment.razorpay_order_id, razorpayPaymentId: payment.razorpay_payment_id, razorpaySignature: payment.razorpay_signature });
            if (verified.activationStatus !== "activated") throw new Error("Payment succeeded, but activation is pending. Please do not pay again.");
            sessionStorage.removeItem(CART_KEY); setSelectedIds([]); await refresh();
            toast({ title: "All selected subscriptions were activated successfully." });
          } catch (error) {
            toast({ title: "Payment captured", description: error instanceof Error ? error.message : "Payment succeeded, but activation is pending. Please do not pay again." });
          } finally { setProcessing(false); }
        },
      });
      checkoutInstance.on?.("payment.failed", (failure) => { toast({ title: "Payment failed", description: failure.error?.description || "The payment could not be completed." }); setProcessing(false); });
      checkoutInstance.open();
    } catch (error) { toast({ title: "Unable to start payment", description: error instanceof Error ? error.message : "Please try again." }); setProcessing(false); }
  };

  return <StudentLayout header={<div><h1 className="text-2xl font-bold text-slate-900 md:text-3xl">Shop</h1><p className="mt-1 text-sm text-slate-500">Select one or more level-wise worksheet subscriptions</p></div>}>
    {loading ? <div className="rounded-2xl bg-white p-6 shadow-card">Loading shop...</div> : <div className="grid gap-6 xl:grid-cols-[1fr_360px]">
      <section className="rounded-2xl bg-white p-6 shadow-card">
        <h2 className="text-lg font-bold text-slate-900">Worksheet Subscription Plans</h2>
        <div className="mt-4 grid gap-4 md:grid-cols-2">
          {plans.map((plan) => {
            const active = activePlanIds.has(plan.id); const selected = selectedIds.includes(plan.id); const previous = latestByPlan.get(plan.id);
            return <div key={plan.id} className={`rounded-xl border p-4 ${selected ? "border-[#5b21b6] bg-purple-50" : "border-slate-200"}`}>
              <div className="flex items-start gap-3"><Checkbox checked={selected} disabled={active || processing} onCheckedChange={() => toggle(plan)} aria-label={`Select ${plan.name}`} />
                <div className="min-w-0 flex-1"><div className="text-xs font-bold uppercase tracking-wide text-[#5b21b6]">{programName(plan)}</div><div className="mt-1 font-semibold text-slate-900">{plan.levelName || plan.name}</div><div className="mt-1 text-sm text-slate-600">{plan.name} · {plan.durationDays} days</div><div className="mt-3 text-xl font-bold text-[#5b21b6]">{money(plan.price, plan.currency)}</div>
                  <div className={`mt-2 inline-flex rounded-full px-2 py-1 text-xs font-bold ${active ? "bg-emerald-100 text-emerald-700" : previous?.status === "expired" ? "bg-amber-100 text-amber-700" : "bg-slate-100 text-slate-600"}`}>{active ? `Already Active until ${formatDate(previous?.expiryDate)}` : previous?.status === "expired" ? "Expired" : "Available"}</div>
                </div></div>
              <Button variant={selected ? "default" : "outline"} className="mt-4 w-full" disabled={active || processing} onClick={() => toggle(plan)}>{active ? "Already Active" : selected ? "Remove from Cart" : "Add to Cart"}</Button>
            </div>;
          })}
        </div>
      </section>
      <aside className="h-fit rounded-2xl bg-white p-5 shadow-card xl:sticky xl:top-24">
        <div className="flex items-center gap-2"><ShoppingCart className="h-5 w-5 text-[#5b21b6]"/><h2 className="font-bold text-slate-900">Selected Plans: {selectedPlans.length}</h2></div>
        <div className="mt-4 space-y-3">{selectedPlans.length ? selectedPlans.map((plan) => <div key={plan.id} className="flex items-start justify-between gap-2 text-sm"><div><div className="font-semibold text-slate-900">{programName(plan)} {plan.levelName}</div><div className="text-slate-500">{money(plan.price, plan.currency)}</div></div><Button size="icon" variant="ghost" onClick={() => toggle(plan)} aria-label={`Remove ${plan.name}`}><Trash2 className="h-4 w-4"/></Button></div>) : <p className="text-sm text-slate-500">No plans selected.</p>}</div>
        <div className="mt-5 space-y-2 border-t pt-4 text-sm"><div className="flex justify-between"><span>Subtotal</span><span>{money(subtotal, currency)}</span></div><div className="flex justify-between"><span>Discount</span><span>{money(0, currency)}</span></div><div className="flex justify-between text-base font-bold"><span>Total</span><span>{money(subtotal, currency)}</span></div></div>
        <Button className="mt-5 w-full bg-[#5b21b6] hover:bg-[#49158a]" disabled={!canPay || !selectedIds.length || processing} onClick={() => void checkout()}>{processing ? "Processing..." : "Proceed to Payment"}</Button>
      </aside>
    </div>}
  </StudentLayout>;
}