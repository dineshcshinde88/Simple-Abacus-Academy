import { useEffect, useState } from "react";
import StudentLayout from "@/layouts/StudentLayout";
import { Button } from "@/components/ui/button";
import { Dialog, DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { useToast } from "@/hooks/use-toast";
import { getSubscriptionOrders, SubscriptionOrder } from "@/services/subscriptionApi";
import { Info } from "lucide-react";

const TOKEN_KEY = "abacus_auth_token";
const money = (amount = 0, currency = "INR") => `${currency} ${Number(amount).toFixed(2)}`;
const dateTime = (value?: string | null) => value ? new Intl.DateTimeFormat("en-IN", { dateStyle: "medium", timeStyle: "short", timeZone: "Asia/Kolkata" }).format(new Date(value)) : "-";
const statusLabel = (status: string) => status === "paid_with_activation_pending" ? "Paid - Activation pending" : status.replaceAll("_", " ");

export default function StudentOrders() {
  const { toast } = useToast();
  const [loading, setLoading] = useState(true);
  const [orders, setOrders] = useState<SubscriptionOrder[]>([]);
  const [selected, setSelected] = useState<SubscriptionOrder | null>(null);
  const token = localStorage.getItem(TOKEN_KEY) || "";
  useEffect(() => { if (!token) { setLoading(false); return; } void getSubscriptionOrders(token).then((response) => setOrders(response.orders || [])).catch((error) => toast({ title: "Unable to load orders", description: error instanceof Error ? error.message : "Please try again." })).finally(() => setLoading(false)); }, [token]);

  return <StudentLayout header={<div><h1 className="text-2xl font-bold text-slate-900 md:text-3xl">Payment History</h1><p className="mt-1 text-sm text-slate-500">Payments and included worksheet subscriptions</p></div>}>
    {loading ? <div className="rounded-2xl bg-white p-6 shadow-card">Loading orders...</div> : <div className="overflow-hidden rounded-md bg-white shadow-card">
      <div className="bg-[#5b21b6] px-5 py-4"><h2 className="text-sm font-bold text-white">Payment Records</h2></div>
      {!orders.length ? <div className="px-5 py-8 text-sm text-slate-600">No payment records found.</div> : <div className="overflow-x-auto"><table className="w-full min-w-[760px] text-sm"><thead><tr className="bg-slate-400 text-left text-white"><th className="px-3 py-3">Payment ID</th><th className="px-3 py-3">Payment Date</th><th className="px-3 py-3">Items</th><th className="px-3 py-3">Total</th><th className="px-3 py-3">Status</th><th className="px-3 py-3">Action</th></tr></thead><tbody className="divide-y">{orders.map((order) => <tr key={order.id}><td className="px-3 py-3 font-mono text-xs">{order.providerOrderId || order.id.slice(0, 8)}</td><td className="px-3 py-3">{dateTime(order.createdAt)}</td><td className="px-3 py-3">{order.items.length}</td><td className="px-3 py-3 font-semibold">{money(order.totalAmount, order.currency)}</td><td className="px-3 py-3 capitalize">{statusLabel(order.paymentStatus)}</td><td className="px-3 py-3"><Button size="sm" className="h-7 rounded-full bg-cyan-500" onClick={() => setSelected(order)}><Info className="mr-1 h-3 w-3"/>Info</Button></td></tr>)}</tbody></table></div>}
    </div>}
    <Dialog open={Boolean(selected)} onOpenChange={(open) => !open && setSelected(null)}><DialogContent className="max-w-2xl"><DialogHeader><DialogTitle>Payment Details</DialogTitle></DialogHeader>{selected && <div className="space-y-4 text-sm"><div className="grid gap-3 sm:grid-cols-3"><div><div className="text-slate-500">Total</div><div className="font-semibold">{money(selected.totalAmount, selected.currency)}</div></div><div><div className="text-slate-500">Status</div><div className="font-semibold capitalize">{statusLabel(selected.paymentStatus)}</div></div><div><div className="text-slate-500">Paid At</div><div className="font-semibold">{dateTime(selected.paidAt)}</div></div></div><div className="divide-y rounded-lg border">{selected.items.map((item) => <div key={item.id} className="grid gap-2 p-3 sm:grid-cols-[1fr_1fr_auto]"><div><div className="font-semibold">{item.programName}</div><div className="text-slate-500">{item.levelName || item.planName}</div></div><div><div>{money(item.amount, selected.currency)}</div><div className="text-xs text-slate-500">{item.durationDays} days</div></div><div className="self-center rounded-full bg-slate-100 px-2 py-1 text-xs font-bold capitalize">{item.status}</div></div>)}</div></div>}</DialogContent></Dialog>
  </StudentLayout>;
}
