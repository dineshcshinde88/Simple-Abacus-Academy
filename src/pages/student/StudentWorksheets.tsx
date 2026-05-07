import { useMemo, useState } from "react";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card } from "@/components/ui/card";
import { Checkbox } from "@/components/ui/checkbox";
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from "@/components/ui/dialog";
import { useToast } from "@/hooks/use-toast";
import StudentLayout from "@/layouts/StudentLayout";
import {
  createRazorpayOrder,
  getSubscriptionPlans,
  getSubscriptionSummary,
  LevelPlan,
  verifyRazorpayPayment,
} from "@/services/subscriptionApi";

type CourseId = "abacus" | "vedic";
type DurationOption = "3_months" | "1_year";
type LevelStatus = "new" | "active" | "expired";
type RazorpayCheckoutResponse = {
  razorpay_payment_id: string;
  razorpay_order_id: string;
  razorpay_signature: string;
};

const TOKEN_KEY = "abacus_auth_token";

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

const durationOptions: Array<{ id: DurationOption; label: string; price: number; durationDays: number }> = [
  { id: "3_months", label: "3 Months", price: 99, durationDays: 90 },
  { id: "1_year", label: "1 Year", price: 199, durationDays: 365 },
];

const courses = [
  {
    id: "abacus" as const,
    name: "Abacus Worksheet Subscription",
    description: "Access level-wise abacus worksheets for focused practice.",
    planPrefix: "Abacus Worksheet",
    levels: [1, 2, 3, 4, 5, 6, 7],
  },
  {
    id: "vedic" as const,
    name: "Vedic Maths Worksheet Subscription",
    description: "Access level-wise Vedic Maths worksheets for speed calculation practice.",
    planPrefix: "Vedic Maths Worksheet",
    levels: [1, 2, 3, 4],
  },
];

const mockStatusMap: Record<CourseId, Record<number, LevelStatus>> = {
  abacus: { 1: "active", 2: "expired" },
  vedic: { 2: "active" },
};

const getStatusLabel = (status: LevelStatus) => {
  if (status === "active") return "Active";
  if (status === "expired") return "Expired";
  return "New to Subscribe";
};

const getStatusBadge = (status: LevelStatus) => {
  if (status === "active") return "bg-emerald-100 text-emerald-700 border-emerald-200";
  if (status === "expired") return "bg-rose-100 text-rose-700 border-rose-200";
  return "bg-slate-100 text-slate-700 border-slate-200";
};

const StudentWorksheets = () => {
  const { toast } = useToast();
  const [durationByCourse, setDurationByCourse] = useState<Record<CourseId, DurationOption>>({
    abacus: "3_months",
    vedic: "3_months",
  });
  const [selectedLevels, setSelectedLevels] = useState<Record<CourseId, number[]>>({
    abacus: [],
    vedic: [],
  });
  const [isRenewOpen, setIsRenewOpen] = useState(false);
  const [processingCourse, setProcessingCourse] = useState<CourseId | null>(null);

  const handleToggleLevel = (courseId: CourseId, level: number, status: LevelStatus) => {
    if (status === "active") return;
    setSelectedLevels((prev) => {
      const current = new Set(prev[courseId]);
      if (current.has(level)) current.delete(level);
      else current.add(level);
      return { ...prev, [courseId]: Array.from(current).sort((a, b) => a - b) };
    });
  };

  const prices = useMemo(() => {
    return courses.reduce<Record<CourseId, number>>((acc, course) => {
      const duration = durationOptions.find((option) => option.id === durationByCourse[course.id]);
      const count = selectedLevels[course.id].length;
      acc[course.id] = count * (duration?.price ?? 0);
      return acc;
    }, { abacus: 0, vedic: 0 });
  }, [durationByCourse, selectedLevels]);

  const findPlanForLevel = (plans: LevelPlan[], courseId: CourseId, level: number, duration: DurationOption) => {
    const course = courses.find((item) => item.id === courseId);
    const durationOption = durationOptions.find((option) => option.id === duration);
    const expectedName = `${course?.planPrefix} Level ${level} - ${durationOption?.label}`.toLowerCase();
    return plans.find((plan) => {
      const planName = (plan.name || "").trim().toLowerCase();
      return (
        plan.isActive &&
        plan.durationDays === durationOption?.durationDays &&
        Number(plan.price) === durationOption.price &&
        planName === expectedName
      );
    });
  };

  const handleProceed = async (courseId: CourseId, levelOverride?: number) => {
    const token = localStorage.getItem(TOKEN_KEY) || "";
    const duration = durationByCourse[courseId];
    const levels = typeof levelOverride === "number" ? [levelOverride] : selectedLevels[courseId];
    if (!token) {
      toast({ title: "Login required", description: "Please login again to continue payment." });
      return;
    }
    if (!levels.length) {
      toast({ title: "Select levels", description: "Please choose at least one level to continue." });
      return;
    }
    if (levels.length > 1) {
      toast({ title: "Choose one level", description: "Online checkout currently supports one level at a time." });
      return;
    }

    setProcessingCourse(courseId);
    try {
      const scriptReady = await loadRazorpayScript();
      if (!scriptReady || !window.Razorpay) {
        throw new Error("Unable to load Razorpay checkout. Please check your internet connection.");
      }

      const [plansResp, summaryResp] = await Promise.all([
        getSubscriptionPlans(token),
        getSubscriptionSummary(token),
      ]);
      if (!summaryResp.canPay) {
        throw new Error("Online payment is not configured. Please contact admin.");
      }

      const level = levels[0];
      const plan = findPlanForLevel(plansResp.plans || [], courseId, level, duration);
      if (!plan) {
        const courseName = courses.find((item) => item.id === courseId)?.name || "Worksheet";
        throw new Error(`No active payment plan found for ${courseName} Level ${level}. Please contact admin.`);
      }

      const orderResp = await createRazorpayOrder(token, plan.id);
      const razorpay = new window.Razorpay({
        key: orderResp.keyId,
        amount: orderResp.order.amount,
        currency: orderResp.order.currency,
        name: "Simple Abacus Academy",
        description: `${orderResp.plan.name} subscription`,
        order_id: orderResp.order.id,
        prefill: {
          name: summaryResp.student?.name || "",
          email: summaryResp.student?.email || "",
        },
        notes: {
          course: courseId,
          selectedDuration: duration,
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
            setSelectedLevels((prev) => ({ ...prev, [courseId]: [] }));
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
            setProcessingCourse(null);
          }
        },
      });

      if (typeof razorpay.on === "function") {
        razorpay.on("payment.failed", (response) => {
          toast({
            title: "Payment failed",
            description: response?.error?.description || "The payment could not be completed.",
          });
          setProcessingCourse(null);
        });
      }

      razorpay.open();
    } catch (error) {
      toast({
        title: "Unable to start payment",
        description: error instanceof Error ? error.message : "Please try again.",
      });
      setProcessingCourse(null);
    }
  };

  return (
    <StudentLayout
      header={
        <div>
          <h1 className="text-2xl font-semibold text-slate-900">Worksheets</h1>
          <p className="text-sm text-muted-foreground">Your worksheet subscription</p>
        </div>
      }
    >
      <div className="space-y-8">
        <div className="space-y-2">
          <h2 className="text-2xl font-semibold text-slate-900">Make Renewals</h2>
          <p className="text-sm text-muted-foreground">
            Select your course, levels, and duration to renew or subscribe.
          </p>
        </div>

        <div className="flex items-center justify-between">
          <h3 className="text-lg font-semibold text-slate-900">Subscribe Levels</h3>
          <Dialog open={isRenewOpen} onOpenChange={setIsRenewOpen}>
            <DialogTrigger asChild>
              <Button variant="outline">Renew</Button>
            </DialogTrigger>
            <DialogContent className="max-w-3xl">
              <DialogHeader>
                <DialogTitle>Existing Levels</DialogTitle>
              </DialogHeader>
              <div className="space-y-4">
                {courses.map((course) => (
                  <div key={`renew-${course.id}`} className="space-y-3">
                    <div className="flex items-center justify-between">
                      <div>
                        <h4 className="text-sm font-semibold text-slate-800">{course.name}</h4>
                        <p className="text-xs text-muted-foreground">Your current subscription status</p>
                      </div>
                    </div>
                    <div className="flex gap-4 overflow-x-auto pb-2 md:grid md:grid-cols-4 lg:grid-cols-6">
                      {course.levels.map((level) => {
                        const status = mockStatusMap[course.id][level] ?? "new";
                        return (
                          <div
                            key={`renew-${course.id}-${level}`}
                            className={`min-w-[140px] rounded-xl border p-3 text-left ${
                              status === "active"
                                ? "border-emerald-200 bg-emerald-50"
                                : status === "expired"
                                  ? "border-rose-200 bg-rose-50"
                                  : "border-border bg-white"
                            }`}
                          >
                            <p className="text-sm font-semibold text-slate-900">Level {level}</p>
                            <p className="text-xs text-muted-foreground mt-1">{getStatusLabel(status)}</p>
                            <div className="mt-3">
                              <span
                                className={`inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] font-medium ${getStatusBadge(
                                  status,
                                )}`}
                              >
                                {status.toUpperCase()}
                              </span>
                            </div>
                            <Button
                              type="button"
                              size="sm"
                              className={`mt-3 h-8 w-full ${
                                status === "expired"
                                  ? "bg-orange-500 hover:bg-orange-600"
                                  : "bg-slate-900 hover:bg-slate-800"
                              }`}
                              disabled={status === "active" || processingCourse === course.id}
                              onClick={() => {
                                setSelectedLevels((prev) => ({ ...prev, [course.id]: [level] }));
                                setIsRenewOpen(false);
                                void handleProceed(course.id, level);
                              }}
                            >
                              {status === "active"
                                ? "Active"
                                : processingCourse === course.id
                                  ? "Starting..."
                                  : status === "expired"
                                    ? "Renew"
                                    : "Subscribe"}
                            </Button>
                          </div>
                        );
                      })}
                    </div>
                  </div>
                ))}
              </div>
            </DialogContent>
          </Dialog>
        </div>

        <div className="space-y-6">
          {courses.map((course) => {
            const duration = durationByCourse[course.id];
            const selected = selectedLevels[course.id];
            return (
              <Card key={course.id} className="p-6 shadow-card space-y-6">
                <div className="flex flex-wrap items-start justify-between gap-4">
                  <div>
                    <h3 className="text-lg font-semibold text-slate-900">{course.name}</h3>
                    <p className="text-sm text-muted-foreground mt-1">{course.description}</p>
                  </div>
                  <div className="flex items-center gap-2">
                    {durationOptions.map((option) => (
                      <Button
                        key={option.id}
                        type="button"
                        variant={duration === option.id ? "default" : "outline"}
                        className="h-9 px-3"
                        onClick={() =>
                          setDurationByCourse((prev) => ({ ...prev, [course.id]: option.id }))
                        }
                      >
                        {option.label} - Rs {option.price}
                      </Button>
                    ))}
                  </div>
                </div>

                <div className="space-y-3">
                  <div className="flex items-center justify-between">
                    <h4 className="text-sm font-semibold text-slate-700">Choose Levels</h4>
                    <Badge variant="outline" className="text-xs">
                      {selected.length} selected
                    </Badge>
                  </div>
                  <div className="flex gap-4 overflow-x-auto pb-2 md:grid md:grid-cols-4 lg:grid-cols-6">
                    {course.levels.map((level) => {
                      const status = mockStatusMap[course.id][level] ?? "new";
                      const isSelected = selected.includes(level);
                      const isActive = status === "active";
                      return (
                        <button
                          key={`${course.id}-${level}`}
                          type="button"
                          title={isActive ? "Already Subscribed" : undefined}
                          onClick={() => handleToggleLevel(course.id, level, status)}
                          className={`min-w-[140px] rounded-xl border p-3 text-left transition ${
                            isSelected
                              ? "border-orange-500 bg-orange-50 shadow-sm"
                              : "border-border bg-white hover:border-orange-300"
                          } ${isActive ? "cursor-not-allowed opacity-70" : ""}`}
                        >
                          <div className="flex items-start justify-between gap-2">
                            <div>
                              <p className="text-sm font-semibold text-slate-900">Level {level}</p>
                              <p className="text-xs text-muted-foreground mt-1">{getStatusLabel(status)}</p>
                            </div>
                            <Checkbox
                              checked={isSelected}
                              disabled={isActive}
                              onCheckedChange={() => handleToggleLevel(course.id, level, status)}
                            />
                          </div>
                          <div className="mt-3">
                            <span
                              className={`inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] font-medium ${getStatusBadge(
                                status,
                              )}`}
                            >
                              {status.toUpperCase()}
                            </span>
                          </div>
                        </button>
                      );
                    })}
                  </div>
                </div>

                <div className="flex flex-wrap items-center justify-between gap-4 border-t border-border pt-4">
                  <div>
                    <p className="text-xs text-muted-foreground">Estimated Price</p>
                    <p className="text-xl font-semibold text-slate-900">
                      Rs {prices[course.id].toLocaleString("en-IN")}
                    </p>
                  </div>
                  <Button
                    onClick={() => void handleProceed(course.id)}
                    disabled={processingCourse === course.id}
                  >
                    {processingCourse === course.id ? "Starting Payment..." : "Proceed to Payment"}
                  </Button>
                </div>
              </Card>
            );
          })}
        </div>
      </div>
    </StudentLayout>
  );
};

export default StudentWorksheets;

