import { useMemo, useState } from "react";
import { Link, useLocation } from "react-router-dom";
import Navbar from "@/components/layout/Navbar";
import Footer from "@/components/layout/Footer";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import {
  teacherCompleteShopOrderPayment,
  teacherVerifyShopOrderPayment,
  type TrainingShopOrder,
} from "@/services/trainingApi";
import { useAuth } from "@/context/AuthContext";
import { useTrainingAuth } from "@/context/TrainingAuthContext";
import { toast } from "@/hooks/use-toast";
import { CheckCircle2, CreditCard, Loader2, ShieldCheck } from "lucide-react";

type RazorpayCheckoutResponse = {
  razorpay_payment_id: string;
  razorpay_order_id: string;
  razorpay_signature: string;
};

declare global {
  interface Window {
    Razorpay?: new (options: Record<string, unknown>) => {
      open: () => void;
      on?: (event: string, callback: (response: { error?: { description?: string } }) => void) => void;
    };
  }
}

const currency = new Intl.NumberFormat("en-IN", {
  style: "currency",
  currency: "INR",
  maximumFractionDigits: 0,
});

const loadRazorpayScript = async (): Promise<boolean> => {
  if (window.Razorpay) return true;

  return new Promise((resolve) => {
    const existing = document.querySelector<HTMLScriptElement>('script[src="https://checkout.razorpay.com/v1/checkout.js"]');
    if (existing) {
      existing.addEventListener("load", () => resolve(true), { once: true });
      existing.addEventListener("error", () => resolve(false), { once: true });
      return;
    }

    const script = document.createElement("script");
    script.src = "https://checkout.razorpay.com/v1/checkout.js";
    script.async = true;
    script.onload = () => resolve(true);
    script.onerror = () => resolve(false);
    document.body.appendChild(script);
  });
};

const TrainingPaymentGateway = () => {
  const location = useLocation();
  const { token: appToken } = useAuth();
  const { token: trainingToken } = useTrainingAuth();
  const initialOrder = useMemo(() => {
    const stateOrder = (location.state as { order?: TrainingShopOrder } | null)?.order;
    if (stateOrder) return stateOrder;
    try {
      return JSON.parse(sessionStorage.getItem("training_shop_checkout") || "null") as TrainingShopOrder | null;
    } catch {
      return null;
    }
  }, [location.state]);
  const [order, setOrder] = useState<TrainingShopOrder | null>(initialOrder);
  const [paying, setPaying] = useState(false);
  const backPath = (location.state as { backPath?: string } | null)?.backPath || "/training/dashboard";
  const token = appToken || trainingToken;

  const handleProceedToPay = async () => {
    if (!order || !token) {
      toast({ title: "Payment cannot start", description: "Please login again and retry.", variant: "destructive" });
      return;
    }

    setPaying(true);
    try {
      const scriptReady = await loadRazorpayScript();
      if (!scriptReady || !window.Razorpay) {
        throw new Error("Unable to load Razorpay checkout.");
      }

      const paymentOrder = await teacherCompleteShopOrderPayment(token, order.id);
      setOrder(paymentOrder.order);
      sessionStorage.setItem("training_shop_checkout", JSON.stringify(paymentOrder.order));

      const razorpay = new window.Razorpay({
        key: paymentOrder.keyId,
        amount: paymentOrder.razorpayOrder.amount,
        currency: paymentOrder.razorpayOrder.currency,
        name: "Simple Abacus",
        description: paymentOrder.order.productName,
        order_id: paymentOrder.razorpayOrder.id,
        prefill: {},
        theme: { color: "#f97316" },
        modal: {
          ondismiss: () => setPaying(false),
        },
        method: {
          upi: true,
          card: true,
          netbanking: true,
          wallet: true,
        },
        handler: async (response: RazorpayCheckoutResponse) => {
          try {
            const verified = await teacherVerifyShopOrderPayment(token, order.id, {
              razorpayOrderId: response.razorpay_order_id,
              razorpayPaymentId: response.razorpay_payment_id,
              razorpaySignature: response.razorpay_signature,
            });
            setOrder(verified.order);
            sessionStorage.setItem("training_shop_checkout", JSON.stringify(verified.order));
            toast({ title: "Payment successful", description: "Razorpay payment verified and order marked paid." });
          } catch {
            toast({ title: "Payment verification failed", description: "Please contact admin with your payment ID.", variant: "destructive" });
          } finally {
            setPaying(false);
          }
        },
      });

      if (typeof razorpay.on === "function") {
        razorpay.on("payment.failed", (response) => {
          toast({
            title: "Payment failed",
            description: response.error?.description || "Razorpay payment was not completed.",
            variant: "destructive",
          });
          setPaying(false);
        });
      }

      razorpay.open();
    } catch {
      toast({ title: "Payment could not start", description: "Please check Razorpay configuration and try again.", variant: "destructive" });
      setPaying(false);
    }
  };

  return (
    <div className="min-h-screen bg-background">
      <Navbar />
      <main className="pt-16">
        <section className="py-12">
          <div className="container mx-auto max-w-3xl px-4">
            <div className="rounded-2xl border border-border bg-white p-6 shadow-card md:p-8">
              <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                  <div className="inline-flex items-center gap-2 rounded-full bg-purple-50 px-3 py-1 text-sm font-semibold text-purple-800">
                    <ShieldCheck className="h-4 w-4" />
                    Payment Gateway
                  </div>
                  <h1 className="mt-4 text-2xl font-heading font-bold text-foreground">Complete your teacher shop payment</h1>
                  <p className="mt-2 text-sm text-muted-foreground">
                    This checkout page receives the selected product, option, quantity, and final price from the dashboard.
                  </p>
                </div>
                <CreditCard className="h-10 w-10 text-orange-500" />
              </div>

              {order ? (
                <div className="mt-6 space-y-4">
                  <div className="rounded-2xl border border-border bg-slate-50 p-5">
                    <div className="flex flex-wrap items-start justify-between gap-3">
                      <div>
                        <p className="text-sm text-muted-foreground">Invoice</p>
                        <p className="font-bold text-foreground">{order.invoiceNumber}</p>
                      </div>
                      <Badge
                        variant="outline"
                        className={
                          order.paymentStatus === "successful"
                            ? "border-emerald-200 bg-emerald-50 text-emerald-700"
                            : "border-amber-200 bg-amber-50 text-amber-700"
                        }
                      >
                        {order.paymentStatus}
                      </Badge>
                    </div>
                    <div className="mt-5 grid gap-4 sm:grid-cols-2">
                      <div>
                        <p className="text-xs font-semibold uppercase text-muted-foreground">Product</p>
                        <p className="font-semibold">{order.productName}</p>
                      </div>
                      <div>
                        <p className="text-xs font-semibold uppercase text-muted-foreground">{order.optionLabel}</p>
                        <p className="font-semibold">{order.selectedOption}</p>
                      </div>
                      <div>
                        <p className="text-xs font-semibold uppercase text-muted-foreground">Quantity</p>
                        <p className="font-semibold">{order.quantity}</p>
                      </div>
                      <div>
                        <p className="text-xs font-semibold uppercase text-muted-foreground">Amount</p>
                        <p className="text-xl font-bold text-purple-900">{currency.format(order.finalPrice)}</p>
                      </div>
                    </div>
                  </div>

                  <div className="rounded-xl border border-dashed border-orange-200 bg-orange-50 p-4 text-sm text-orange-900">
                    <div className="flex gap-2">
                      <CheckCircle2 className="mt-0.5 h-4 w-4 shrink-0" />
                      <p>
                        Order details are saved with pending payment status. Click Proceed to Pay to open Razorpay checkout,
                        including UPI QR where Razorpay is enabled for your account.
                      </p>
                    </div>
                  </div>

                  <div className="flex flex-col gap-3 sm:flex-row">
                    <Button
                      className="bg-orange-500 hover:bg-orange-600"
                      onClick={handleProceedToPay}
                      disabled={paying || order.paymentStatus === "successful"}
                    >
                      {paying ? <Loader2 className="mr-2 h-4 w-4 animate-spin" /> : null}
                      {order.paymentStatus === "successful" ? "Payment Successful" : "Proceed to Pay"}
                    </Button>
                    <Button variant="outline" asChild>
                      <Link to={backPath}>Back to Dashboard</Link>
                    </Button>
                  </div>
                </div>
              ) : (
                <div className="mt-6 rounded-xl border border-border bg-slate-50 p-5">
                  <p className="text-muted-foreground">No checkout order found. Please start again from the teacher shop.</p>
                  <Button className="mt-4" asChild>
                    <Link to={backPath}>Open Dashboard</Link>
                  </Button>
                </div>
              )}
            </div>
          </div>
        </section>
      </main>
      <Footer />
    </div>
  );
};

export default TrainingPaymentGateway;
