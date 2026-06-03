import { useEffect, useState } from "react";
import StudentLayout from "@/layouts/StudentLayout";
import { Button } from "@/components/ui/button";
import { Dialog, DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { useToast } from "@/hooks/use-toast";
import { getSubscriptionSummary, StudentSubscription } from "@/services/subscriptionApi";
import { Info } from "lucide-react";

const TOKEN_KEY = "abacus_auth_token";

const paymentStatusLabel = (status: StudentSubscription["paymentStatus"]) => (status === "paid" ? "Paid" : "Unpaid");

const formatDate = (value?: string | null) => {
  if (!value) return "-";
  const d = new Date(value);
  if (Number.isNaN(d.getTime())) return value;
  return d.toLocaleDateString();
};

const formatDateTime = (value?: string | null) => {
  if (!value) return "-";
  const d = new Date(value);
  if (Number.isNaN(d.getTime())) return value;
  return d.toLocaleString();
};

const formatMoney = (amount?: number, currency = "INR") => `${currency} ${Number(amount || 0).toFixed(2)}`;

const StudentOrders = () => {
  const { toast } = useToast();
  const [loading, setLoading] = useState(true);
  const [history, setHistory] = useState<StudentSubscription[]>([]);
  const [selectedOrder, setSelectedOrder] = useState<StudentSubscription | null>(null);

  const token = localStorage.getItem(TOKEN_KEY) || "";

  useEffect(() => {
    const run = async () => {
      if (!token) {
        setLoading(false);
        return;
      }

      try {
        const summaryResp = await getSubscriptionSummary(token);
        setHistory(summaryResp.subscription?.history || []);
      } catch (error) {
        toast({
          title: "Unable to load orders",
          description: error instanceof Error ? error.message : "Please try again later.",
        });
      } finally {
        setLoading(false);
      }
    };

    void run();
  }, [token]);

  return (
    <StudentLayout
      header={(
        <div>
          <h1 className="text-2xl md:text-3xl font-heading font-bold text-slate-900">Orders</h1>
          <p className="text-sm text-slate-500 mt-1">Student order details and payment status</p>
        </div>
      )}
    >
      {loading ? (
        <div className="bg-white rounded-2xl shadow-card p-6 text-slate-600">Loading orders...</div>
      ) : (
        <div className="rounded-md bg-white shadow-card">
          <div className="rounded-t-md bg-[#5b21b6] px-5 py-4">
            <h2 className="text-sm font-bold text-white">Orders List</h2>
          </div>
          {history.length === 0 ? (
            <div className="px-5 py-8 text-sm text-slate-600">No orders found.</div>
          ) : (
            <div className="overflow-x-auto">
              <table className="w-full min-w-[720px] border-collapse text-sm">
                <thead>
                  <tr className="bg-slate-400 text-left text-white">
                    <th className="w-14 border border-slate-300 px-3 py-3 font-semibold">#</th>
                    <th className="border border-slate-300 px-3 py-3 font-semibold">Ordered On</th>
                    <th className="border border-slate-300 px-3 py-3 font-semibold">Amount</th>
                    <th className="border border-slate-300 px-3 py-3 font-semibold">Payment Status</th>
                    <th className="w-28 border border-slate-300 px-3 py-3 text-center font-semibold">Action</th>
                  </tr>
                </thead>
                <tbody>
                  {history.map((order, index) => (
                    <tr key={order.id} className="text-slate-700">
                      <td className="border border-slate-200 px-3 py-3">{index + 1}</td>
                      <td className="border border-slate-200 px-3 py-3">{formatDateTime(order.createdAt)}</td>
                      <td className="border border-slate-200 px-3 py-3">{formatMoney(order.amount, order.currency)}</td>
                      <td className="border border-slate-200 px-3 py-3">{paymentStatusLabel(order.paymentStatus).toUpperCase()}</td>
                      <td className="border border-slate-200 px-3 py-3 text-center">
                        <Button
                          type="button"
                          size="sm"
                          className="h-7 rounded-full bg-cyan-500 px-3 text-xs text-white hover:bg-cyan-600"
                          onClick={() => setSelectedOrder(order)}
                        >
                          <Info className="mr-1 h-3 w-3" />
                          Info
                        </Button>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </div>
      )}

      <Dialog open={Boolean(selectedOrder)} onOpenChange={(open) => !open && setSelectedOrder(null)}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Order Details</DialogTitle>
          </DialogHeader>
          {selectedOrder && (
            <div className="grid gap-3 text-sm sm:grid-cols-2">
              <div>
                <div className="text-slate-500">Plan</div>
                <div className="font-semibold text-slate-900">{selectedOrder.planName}</div>
              </div>
              <div>
                <div className="text-slate-500">Level</div>
                <div className="font-semibold text-slate-900">{selectedOrder.levelName || "-"}</div>
              </div>
              <div>
                <div className="text-slate-500">Amount</div>
                <div className="font-semibold text-slate-900">{formatMoney(selectedOrder.amount, selectedOrder.currency)}</div>
              </div>
              <div>
                <div className="text-slate-500">Payment Status</div>
                <div className="font-semibold text-slate-900">{paymentStatusLabel(selectedOrder.paymentStatus)}</div>
              </div>
              <div>
                <div className="text-slate-500">Start Date</div>
                <div className="font-semibold text-slate-900">{formatDate(selectedOrder.startDate)}</div>
              </div>
              <div>
                <div className="text-slate-500">Expiry Date</div>
                <div className="font-semibold text-slate-900">{formatDate(selectedOrder.expiryDate)}</div>
              </div>
            </div>
          )}
        </DialogContent>
      </Dialog>
    </StudentLayout>
  );
};

export default StudentOrders;
