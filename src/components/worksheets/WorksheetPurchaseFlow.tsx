import { useEffect, useMemo, useState } from "react";
import { ArrowLeft, CheckCircle2, Clock, Layers, List, LogIn, ShieldCheck, Trash2, UserPlus } from "lucide-react";
import { Link, useNavigate, useSearchParams } from "react-router-dom";
import Navbar from "@/components/layout/Navbar";
import Footer from "@/components/layout/Footer";
import { Button } from "@/components/ui/button";
import { Checkbox } from "@/components/ui/checkbox";
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { Label } from "@/components/ui/label";
import { RadioGroup, RadioGroupItem } from "@/components/ui/radio-group";
import { useAuth } from "@/context/AuthContext";
import { placeholderImages } from "@/data/placeholderImages";
import { useToast } from "@/hooks/use-toast";
import {
  createRazorpayOrder,
  ensureWorksheetPlan,
  getSubscriptionPlans,
  verifyRazorpayPayment,
  type LevelPlan,
} from "@/services/subscriptionApi";

type Duration = "3-months" | "1-year";

type CourseConfig = {
  title: string;
  courseName: string;
  courseSlug: "abacus-worksheet" | "vedic-maths-worksheet";
  backLabel: string;
  backPath: string;
  levels: string[];
  image: string;
  accentClass: string;
};

type RazorpayCheckoutResponse = {
  razorpay_payment_id: string;
  razorpay_order_id: string;
  razorpay_signature: string;
};

declare global {
  interface Window {
    Razorpay?: new (options: Record<string, unknown>) => {
      open: () => void;
      on?: (event: string, handler: (response: { error?: { description?: string } }) => void) => void;
    };
  }
}

const durationPrices: Record<Duration, { label: string; days: number; price: number; originalPrice: number }> = {
  "3-months": { label: "3 Months", days: 90, price: 99, originalPrice: 199 },
  "1-year": { label: "1 Year", days: 365, price: 199, originalPrice: 399 },
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

const normalize = (value: string) => value.toLowerCase().replace(/[^a-z0-9]+/g, " ").trim();
const singularize = (value: string) => normalize(value).replace(/\bworksheets\b/g, "worksheet");
const planId = (plan: LevelPlan) => (plan as LevelPlan & { _id?: string }).id || (plan as LevelPlan & { _id?: string })._id || "";
const levelMatches = (plan: LevelPlan, level: string) => {
  const source = singularize(`${plan.levelName || ""} ${plan.name || ""}`);
  const target = singularize(level);
  const levelNumber = target.match(/\d+/)?.[0];

  if (target.includes("foundation")) {
    return source.includes("foundation") || /\blevel 0\b/.test(source);
  }

  return levelNumber ? new RegExp(`\\blevel ${levelNumber}\\b`).test(source) : source.includes(target);
};

const WorksheetPurchaseFlow = ({ config }: { config: CourseConfig }) => {
  const { toast } = useToast();
  const { token, user, loading: authLoading } = useAuth();
  const navigate = useNavigate();
  const [searchParams] = useSearchParams();
  const [duration, setDuration] = useState<Duration>("3-months");
  const [selectedLevels, setSelectedLevels] = useState<string[]>(() => {
    const redirectedLevels = searchParams.getAll("level").filter((level) => config.levels.includes(level));
    return redirectedLevels.length > 0 ? Array.from(new Set(redirectedLevels)) : [config.levels[0]];
  });
  const [showCart, setShowCart] = useState(false);
  const [showAuthChoice, setShowAuthChoice] = useState(false);
  const [agreedToTerms, setAgreedToTerms] = useState(false);
  const [isProcessingPayment, setIsProcessingPayment] = useState(false);

  const selectedPlan = durationPrices[duration];
  const totalPrice = selectedPlan.price * selectedLevels.length;
  const totalOriginalPrice = selectedPlan.originalPrice * selectedLevels.length;

  useEffect(() => {
    const incomingDuration = searchParams.get("duration");
    if (incomingDuration === "1-year" || incomingDuration === "3-months") {
      setDuration(incomingDuration);
    }
  }, [searchParams]);

  const checkoutRedirect = useMemo(() => {
    const params = new URLSearchParams();
    (selectedLevels.length > 0 ? selectedLevels : [config.levels[0]]).forEach((level) => params.append("level", level));
    params.set("duration", duration);
    return `${window.location.pathname}?${params.toString()}`;
  }, [config.levels, duration, selectedLevels]);

  const matchingPlan = (plans: LevelPlan[], level: string) =>
    plans.find((plan) => {
      const planText = singularize(`${plan.courseName || ""} ${plan.name || ""} ${plan.courseSlug || ""}`);
      const sameCourse =
        plan.courseSlug === config.courseSlug ||
        planText.includes(singularize(config.courseName)) ||
        planText.includes(singularize(config.courseSlug));
      const sameLevel = levelMatches(plan, level);
      return sameCourse && sameLevel && plan.durationDays === selectedPlan.days;
    });

  const toggleLevel = (level: string) => {
    setSelectedLevels((current) => {
      if (current.includes(level)) {
        return current.length > 1 ? current.filter((item) => item !== level) : current;
      }
      return [...current, level];
    });
  };

  const removeLevel = (level: string) => {
    setSelectedLevels((current) => current.filter((item) => item !== level));
  };

  const handleProceedToPayment = async () => {
    if (!agreedToTerms || isProcessingPayment || authLoading || selectedLevels.length === 0) return;
    const activeToken = token || window.localStorage.getItem(TOKEN_KEY) || "";

    if (!activeToken || !user) {
      setShowAuthChoice(true);
      return;
    }

    if (user.role !== "student") {
      toast({ title: "Student login required", description: "Please login with a student account before payment." });
      navigate(`/student-login?redirect=${encodeURIComponent(checkoutRedirect)}`);
      return;
    }

    setIsProcessingPayment(true);
    try {
      const scriptReady = await loadRazorpayScript();
      if (!scriptReady || !window.Razorpay) throw new Error("Unable to load Razorpay checkout.");

      const plansResp = await getSubscriptionPlans(activeToken);
      const matchedPlans = await Promise.all(selectedLevels.map(async (level) => {
        const existingPlan = matchingPlan(plansResp.plans || [], level);
        if (existingPlan && planId(existingPlan)) {
          return { level, plan: existingPlan };
        }

        const ensured = await ensureWorksheetPlan(activeToken, {
          courseSlug: config.courseSlug,
          level,
          durationDays: selectedPlan.days,
        });
        if (!ensured.plan || !planId(ensured.plan)) {
          throw new Error(`${level} subscription plan was not found.`);
        }
        return { level, plan: ensured.plan };
      }));

      const orderResp = await createRazorpayOrder(activeToken, matchedPlans.map((item) => planId(item.plan)));
      const razorpay = new window.Razorpay({
        key: orderResp.keyId,
        amount: orderResp.order.amount,
        currency: orderResp.order.currency,
        name: "Simple Abacus",
        description: matchedPlans.length === 1 ? orderResp.plan.name : `${matchedPlans.length} worksheet levels`,
        order_id: orderResp.order.id,
        prefill: { name: user.name, email: user.email },
        notes: { planId: orderResp.plan.id, levelName: selectedLevels.join(", ") },
        handler: async (response: RazorpayCheckoutResponse) => {
          try {
            const verification = await verifyRazorpayPayment(activeToken, {
              attemptId: orderResp.attemptId,
              razorpayOrderId: response.razorpay_order_id,
              razorpayPaymentId: response.razorpay_payment_id,
              razorpaySignature: response.razorpay_signature,
            });
            if (verification.activationStatus === "pending_manual_review") {
              toast({
                title: "Payment captured",
                description: verification.message || "Your payment is saved and activation is pending manual review.",
              });
            } else {
              toast({ title: "Payment successful", description: `${selectedLevels.length} level subscription${selectedLevels.length > 1 ? "s are" : " is"} active.` });
              navigate("/student/dashboard");
            }
          } catch (error) {
            toast({
              title: "Payment verification failed",
              description: error instanceof Error ? error.message : "Please contact support.",
            });
          } finally {
            setIsProcessingPayment(false);
          }
        },
      });

      if (typeof razorpay.on === "function") {
        razorpay.on("payment.failed", (response) => {
          toast({ title: "Payment failed", description: response?.error?.description || "Payment could not be completed." });
          setIsProcessingPayment(false);
        });
      }

      razorpay.open();
    } catch (error) {
      toast({ title: "Unable to start payment", description: error instanceof Error ? error.message : "Please try again." });
      setIsProcessingPayment(false);
    }
  };

  const continueToStudentAuth = (path: "/student-login" | "/student-registration") => {
    setShowAuthChoice(false);
    navigate(`${path}?redirect=${encodeURIComponent(checkoutRedirect)}`);
  };

  const perks = useMemo(
    () => ["View Questions", "Practice Mode", "Progress Tracking"],
    [],
  );

  return (
    <div className="min-h-screen bg-background">
      <Navbar />
      <main className="pt-16">
        <section className={showCart ? "min-h-[70vh] bg-[#f3eff8] py-14" : "py-14"}>
          <div className="container mx-auto px-4">
            {showCart ? (
              <>
                <div>
                  <h1 className="text-3xl font-heading font-bold text-[#4B1E83] md:text-4xl">Simple Abacus Cart</h1>
                  <p className="mt-2 text-sm text-slate-600">{selectedLevels.length} Level{selectedLevels.length > 1 ? "s" : ""} Selected</p>
                </div>
                <div className="mt-8 grid items-start gap-8 lg:grid-cols-[1fr_280px]">
                  <div>
                    <div className="rounded-xl bg-white p-5 shadow-card">
                      <div className="grid gap-4 sm:grid-cols-[120px_1fr_auto]">
                        <img src={config.image} alt={config.title} className="h-24 w-full rounded-lg object-cover sm:w-28" />
                        <div>
                          <h2 className="text-lg font-heading font-bold text-[#4B1E83]">{config.title}</h2>
                          <p className="mt-2 text-sm text-slate-600">
                            {selectedLevels.length ? `${selectedLevels.join(", ")} Selected` : "No level selected"}
                          </p>
                          <div className="mt-4 grid gap-2">
                            {selectedLevels.length ? (
                              selectedLevels.map((level) => (
                                <div key={level} className="flex items-center justify-between rounded-md border border-[#6f2dbd] px-3 py-2 text-sm text-[#4B1E83]">
                                  <span>
                                    {level} <strong>Rs.{selectedPlan.price}</strong>{" "}
                                    <span className="text-xs text-red-500 line-through">Rs.{selectedPlan.originalPrice}</span>
                                  </span>
                                  <button type="button" onClick={() => removeLevel(level)} aria-label={`Remove ${level}`}>
                                    <Trash2 className="h-4 w-4 text-orange-500" />
                                  </button>
                                </div>
                              ))
                            ) : (
                              <div className="rounded-md border border-dashed border-slate-300 px-3 py-3 text-sm text-slate-500">
                                Your cart is empty. Continue shopping to select an Abacus or Vedic Maths level.
                              </div>
                            )}
                          </div>
                        </div>
                        <div className="text-right">
                          <div className="mt-5 text-xl font-heading font-bold text-orange-500">Rs.{totalPrice}</div>
                          <div className="text-sm text-orange-500 line-through">Rs.{totalOriginalPrice}</div>
                        </div>
                      </div>
                    </div>
                    <Button className="mt-6 rounded-md bg-[#4B1E83] px-5 hover:bg-[#3c176a]" onClick={() => setShowCart(false)}>
                      <ArrowLeft className="mr-2 h-4 w-4" />
                      Continue Shopping
                    </Button>
                  </div>

                  <aside className="rounded-xl bg-white p-6 shadow-card">
                    <h3 className="text-lg font-heading font-bold text-[#4B1E83]">Total:</h3>
                    <div className="mt-3 flex items-center gap-2">
                      <span className="text-3xl font-heading font-bold text-orange-500">Rs.{totalPrice}</span>
                      <span className="text-sm text-orange-500 line-through">Rs.{totalOriginalPrice}</span>
                    </div>
                    <div className="mt-4 inline-flex rounded-full bg-emerald-100 px-3 py-2 text-sm font-semibold text-emerald-700">
                      You Saved Rs.{totalOriginalPrice - totalPrice}
                    </div>
                    <p className="mt-5 text-sm font-semibold text-slate-900">Special Bundle Discount Applied</p>
                    <label className="mt-5 flex items-center gap-2 text-xs text-slate-700">
                      <Checkbox checked={agreedToTerms} onCheckedChange={(checked) => setAgreedToTerms(checked === true)} />
                      <span>
                        I agree to the <Link to="/terms-and-conditions" className="text-[#4B1E83] underline">Terms & Conditions</Link>
                      </span>
                    </label>
                    <Button
                      className="mt-5 w-full rounded-md bg-[#4B1E83] py-6 font-semibold hover:bg-[#3c176a]"
                      disabled={!agreedToTerms || isProcessingPayment || authLoading || selectedLevels.length === 0}
                      onClick={() => void handleProceedToPayment()}
                    >
                      {authLoading ? "Checking Login..." : isProcessingPayment ? "Starting Payment..." : "Proceed to Payment ->"}
                    </Button>
                  </aside>
                </div>
              </>
            ) : (
              <>
                <div className="grid gap-8 lg:grid-cols-[360px_1fr]">
                  <div className={`rounded-2xl p-6 text-white shadow-card ${config.accentClass}`}>
                    <img src={config.image} alt={config.title} className="h-56 w-full rounded-xl object-cover" />
                    <h1 className="mt-6 text-3xl font-heading font-bold">{config.title}</h1>
                    <p className="mt-3 text-sm text-white/85">Choose your level, pick a duration, and unlock worksheet practice instantly after payment.</p>
                    <div className="mt-5 grid gap-3">
                      {perks.map((perk) => (
                        <div key={perk} className="flex items-center gap-2 text-sm">
                          <CheckCircle2 className="h-4 w-4" />
                          {perk}
                        </div>
                      ))}
                    </div>
                  </div>

                  <div className="rounded-2xl border border-border bg-white p-6 shadow-card">
                    <div className="flex flex-wrap items-center justify-between gap-4">
                      <h2 className="text-2xl font-heading font-bold text-orange-500">{config.courseName}</h2>
                      <RadioGroup value={duration} onValueChange={(value) => setDuration(value as Duration)} className="flex flex-wrap items-center gap-4 text-sm">
                        <span className="font-semibold text-purple-700">Select Duration:</span>
                        <div className="flex items-center gap-2">
                          <RadioGroupItem id={`${config.courseSlug}-3m`} value="3-months" />
                          <Label htmlFor={`${config.courseSlug}-3m`}>3 Months</Label>
                        </div>
                        <div className="flex items-center gap-2">
                          <RadioGroupItem id={`${config.courseSlug}-1y`} value="1-year" />
                          <Label htmlFor={`${config.courseSlug}-1y`}>1 Year</Label>
                        </div>
                      </RadioGroup>
                    </div>

                    <div className="mt-6 grid gap-4 md:grid-cols-2">
                      {config.levels.map((level) => {
                        const active = selectedLevels.includes(level);
                        return (
                          <button
                            key={level}
                            type="button"
                            className={`flex items-center justify-between rounded-xl border px-4 py-3 text-left shadow-sm transition ${
                              active ? "border-[#4B1E83] bg-purple-50" : "border-orange-300 bg-white hover:border-orange-500"
                            }`}
                            onClick={() => toggleLevel(level)}
                          >
                            <span className="flex items-center gap-3">
                              <span className={`h-4 w-4 rounded border ${active ? "border-[#4B1E83] bg-[#4B1E83]" : "border-slate-300"}`} />
                              <span className="font-semibold text-purple-700">
                                {level}
                                <span className="ml-2 text-xs text-red-500 line-through">Rs.{selectedPlan.originalPrice}</span>
                                <span className="ml-1 text-base font-bold text-purple-800">Rs.{selectedPlan.price}</span>
                              </span>
                            </span>
                            <List className="h-4 w-4 text-orange-500" />
                          </button>
                        );
                      })}
                    </div>

                    <div className="mt-6 grid gap-3 rounded-xl bg-slate-50 p-4 text-sm text-slate-600 md:grid-cols-3">
                      <div className="flex items-center gap-2"><ShieldCheck className="h-4 w-4 text-emerald-600" /> Secure Razorpay</div>
                      <div className="flex items-center gap-2"><Clock className="h-4 w-4 text-orange-500" /> {selectedPlan.label} access</div>
                      <div className="flex items-center gap-2"><Layers className="h-4 w-4 text-[#4B1E83]" /> Multiple levels supported</div>
                    </div>
                  </div>
                </div>

                <div className="mt-8 flex flex-wrap items-center justify-between gap-4">
                  <Button className="rounded-full bg-[#4B1E83] px-6 py-6 text-sm font-semibold hover:bg-[#3c176a]" asChild>
                    <Link to={config.backPath}>{config.backLabel}</Link>
                  </Button>
                  <Button className="rounded-full bg-[#4B1E83] px-6 py-6 text-sm font-semibold hover:bg-[#3c176a]" onClick={() => setShowCart(true)}>
                    Subscribe Now
                  </Button>
                </div>
              </>
            )}
          </div>
        </section>
      </main>
      <Dialog open={showAuthChoice} onOpenChange={setShowAuthChoice}>
        <DialogContent className="max-w-md rounded-2xl">
          <DialogHeader>
            <DialogTitle className="text-center text-2xl font-heading text-[#4B1E83]">Continue Your Subscription</DialogTitle>
            <DialogDescription className="text-center">
              Login with your existing student account or register as a new student before payment.
            </DialogDescription>
          </DialogHeader>
          <div className="grid gap-3 pt-3 sm:grid-cols-2">
            <Button
              type="button"
              variant="outline"
              className="h-auto min-h-24 flex-col gap-2 border-[#4B1E83] px-4 py-4 text-[#4B1E83] hover:bg-purple-50 hover:text-[#4B1E83]"
              onClick={() => continueToStudentAuth("/student-login")}
            >
              <LogIn className="h-6 w-6" />
              <span>Already Registered?</span>
              <span className="text-xs font-normal">Student Login</span>
            </Button>
            <Button
              type="button"
              className="h-auto min-h-24 flex-col gap-2 bg-[#4B1E83] px-4 py-4 hover:bg-[#3c176a]"
              onClick={() => continueToStudentAuth("/student-registration")}
            >
              <UserPlus className="h-6 w-6" />
              <span>New Student?</span>
              <span className="text-xs font-normal text-white/80">Register Now</span>
            </Button>
          </div>
        </DialogContent>
      </Dialog>
      <Footer />
    </div>
  );
};

export const abacusWorksheetConfig: CourseConfig = {
  title: "Abacus Worksheet Subscription",
  courseName: "Abacus Worksheets",
  courseSlug: "abacus-worksheet",
  backLabel: "Buy Vedic Maths Worksheets",
  backPath: "/vedic-maths-worksheet-subscription",
  levels: ["Level 0 (Foundation)", "Level 1", "Level 2", "Level 3", "Level 4", "Level 5", "Level 6", "Level 7"],
  image: placeholderImages.moduleSection,
  accentClass: "bg-gradient-to-br from-[#4B1E83] via-[#6f2dbd] to-orange-500",
};

export const vedicWorksheetConfig: CourseConfig = {
  title: "Vedic Maths Worksheet Subscription",
  courseName: "Vedic Maths Worksheets",
  courseSlug: "vedic-maths-worksheet",
  backLabel: "Buy Abacus Worksheets",
  backPath: "/abacus-worksheet-subscription",
  levels: ["Level 1", "Level 2", "Level 3", "Level 4"],
  image: placeholderImages.vedicMathsHero,
  accentClass: "bg-gradient-to-br from-orange-500 via-[#6f2dbd] to-[#4B1E83]",
};

export default WorksheetPurchaseFlow;
