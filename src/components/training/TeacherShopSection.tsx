import { useEffect, useMemo, useState } from "react";
import { useNavigate } from "react-router-dom";
import { motion } from "framer-motion";
import { Award, CheckCircle2, Download, Loader2, Minus, PackageCheck, Plus, Search, ShoppingBag } from "lucide-react";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { toast } from "@/hooks/use-toast";
import { trainingShopCategories, trainingShopProducts, type TrainingShopProduct } from "@/data/trainingShopProducts";
import {
  teacherCreateShopOrder,
  teacherShopOrders,
  type TrainingShopOrder,
  type TrainingShopOrderPayload,
} from "@/services/trainingApi";

type ProductSelection = {
  option: string;
  quantity: number;
};

type TeacherShopSectionProps = {
  token: string | null;
  paymentPath?: string;
  backPath?: string;
};

const currency = new Intl.NumberFormat("en-IN", {
  style: "currency",
  currency: "INR",
  maximumFractionDigits: 0,
});

const statusStyles: Record<TrainingShopOrder["paymentStatus"], string> = {
  pending: "border-amber-200 bg-amber-50 text-amber-700",
  successful: "border-emerald-200 bg-emerald-50 text-emerald-700",
  failed: "border-rose-200 bg-rose-50 text-rose-700",
};

const getInitialSelections = () =>
  Object.fromEntries(
    trainingShopProducts.map((product) => [
      product.id,
      { option: product.options[0]?.label || "", quantity: 1 },
    ]),
  ) as Record<string, ProductSelection>;

const selectedPrice = (product: TrainingShopProduct, option: string) =>
  product.options.find((item) => item.label === option)?.price || product.options[0]?.price || 0;

const downloadInvoice = (order: TrainingShopOrder) => {
  const invoice = [
    "Simple Abacus - Teacher Shop Invoice",
    `Invoice: ${order.invoiceNumber}`,
    `Order ID: ${order.id}`,
    `Date: ${new Date(order.createdAt).toLocaleString()}`,
    "",
    `Product: ${order.productName}`,
    `${order.optionLabel}: ${order.selectedOption}`,
    `Quantity: ${order.quantity}`,
    `Unit Price: ${currency.format(order.unitPrice)}`,
    `Total: ${currency.format(order.finalPrice)}`,
    `Payment Status: ${order.paymentStatus}`,
  ].join("\n");

  const url = URL.createObjectURL(new Blob([invoice], { type: "text/plain;charset=utf-8" }));
  const link = document.createElement("a");
  link.href = url;
  link.download = `${order.invoiceNumber}.txt`;
  link.click();
  URL.revokeObjectURL(url);
};

const TeacherShopSection = ({ token, paymentPath = "/training/payment-gateway", backPath = "/training/dashboard" }: TeacherShopSectionProps) => {
  const navigate = useNavigate();
  const [search, setSearch] = useState("");
  const [category, setCategory] = useState("All");
  const [selections, setSelections] = useState<Record<string, ProductSelection>>(getInitialSelections);
  const [orders, setOrders] = useState<TrainingShopOrder[]>([]);
  const [loadingOrders, setLoadingOrders] = useState(false);
  const [redirectingProductId, setRedirectingProductId] = useState<string | null>(null);

  const filteredProducts = useMemo(() => {
    const query = search.trim().toLowerCase();
    return trainingShopProducts.filter((product) => {
      const matchesCategory = category === "All" || product.category === category;
      const matchesSearch = !query || `${product.name} ${product.description} ${product.category}`.toLowerCase().includes(query);
      return matchesCategory && matchesSearch;
    });
  }, [category, search]);

  const orderGroups = useMemo(() => {
    const pending = orders.filter((order) => order.paymentStatus === "pending");
    const successful = orders.filter((order) => order.paymentStatus === "successful");
    return { pending, successful, recent: orders.slice(0, 3) };
  }, [orders]);

  const loadOrders = async () => {
    if (!token) return;
    setLoadingOrders(true);
    try {
      const response = await teacherShopOrders(token);
      setOrders(response.orders || []);
    } catch {
      toast({
        title: "Could not load shop orders",
        description: "Order history will appear once the training shop API is available.",
        variant: "destructive",
      });
    } finally {
      setLoadingOrders(false);
    }
  };

  useEffect(() => {
    void loadOrders();
  }, [token]);

  const updateSelection = (productId: string, patch: Partial<ProductSelection>) => {
    setSelections((current) => ({
      ...current,
      [productId]: { ...current[productId], ...patch },
    }));
  };

  const createOrder = async (product: TrainingShopProduct) => {
    if (!token) {
      toast({ title: "Login required", description: "Please login again before purchasing.", variant: "destructive" });
      return;
    }

    const selection = selections[product.id];
    const unitPrice = selectedPrice(product, selection.option);
    if (!selection.option || selection.quantity < 1 || unitPrice <= 0) {
      toast({ title: "Select a valid item", description: "Please choose an option and quantity before payment.", variant: "destructive" });
      return;
    }

    const payload: TrainingShopOrderPayload = {
      productId: product.id,
      productName: product.name,
      category: product.category,
      selectedOption: selection.option,
      optionLabel: product.optionLabel,
      quantity: selection.quantity,
      unitPrice,
      finalPrice: unitPrice * selection.quantity,
    };

    setRedirectingProductId(product.id);
    try {
      const response = await teacherCreateShopOrder(token, payload);
      sessionStorage.setItem("training_shop_checkout", JSON.stringify(response.order));
      toast({ title: "Order created", description: "Redirecting to payment gateway." });
      await loadOrders();
      navigate(`${paymentPath}?orderId=${encodeURIComponent(response.order.id)}`, {
        state: { order: response.order, paymentUrl: response.paymentUrl, backPath },
      });
    } catch {
      toast({ title: "Payment could not start", description: "Please try again in a moment.", variant: "destructive" });
    } finally {
      setRedirectingProductId(null);
    }
  };

  return (
    <section className="mt-10 space-y-6">
      <div className="overflow-hidden rounded-2xl border border-orange-100 bg-gradient-to-r from-purple-900 via-purple-800 to-orange-500 p-6 text-white shadow-card">
        <div className="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
          <div>
            <div className="inline-flex items-center gap-2 rounded-full bg-white/15 px-3 py-1 text-sm font-semibold">
              <ShoppingBag className="h-4 w-4" />
              Rate Card / Shop
            </div>
            <h2 className="mt-4 text-2xl font-heading font-bold md:text-3xl">Purchase kits and learning materials</h2>
            <p className="mt-2 max-w-2xl text-sm text-white/85">
              Select a level, size, or option, adjust quantity, and continue to checkout with a clear order record.
            </p>
          </div>
          <div className="grid grid-cols-2 gap-3 sm:grid-cols-3">
            <div className="rounded-xl bg-white/15 p-3">
              <p className="text-2xl font-bold">{orders.length}</p>
              <p className="text-xs text-white/80">Purchased orders</p>
            </div>
            <div className="rounded-xl bg-white/15 p-3">
              <p className="text-2xl font-bold">{orderGroups.pending.length}</p>
              <p className="text-xs text-white/80">Pending payments</p>
            </div>
            <div className="rounded-xl bg-white/15 p-3">
              <p className="text-2xl font-bold">{orderGroups.successful.length}</p>
              <p className="text-xs text-white/80">Successful</p>
            </div>
          </div>
        </div>
      </div>

      <div className="grid gap-6 xl:grid-cols-[1fr_360px]">
        <div className="space-y-5">
          <div className="flex flex-col gap-3 rounded-2xl border border-border bg-white p-4 shadow-card md:flex-row">
            <div className="relative flex-1">
              <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
              <Input
                value={search}
                onChange={(event) => setSearch(event.target.value)}
                placeholder="Search products"
                className="pl-9"
              />
            </div>
            <Select value={category} onValueChange={setCategory}>
              <SelectTrigger className="md:w-56">
                <SelectValue placeholder="Filter category" />
              </SelectTrigger>
              <SelectContent>
                {trainingShopCategories.map((item) => (
                  <SelectItem key={item} value={item}>{item}</SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>

          <div className="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
            {filteredProducts.map((product, index) => {
              const selection = selections[product.id];
              const unitPrice = selectedPrice(product, selection.option);
              const finalPrice = unitPrice * selection.quantity;
              const isRedirecting = redirectingProductId === product.id;

              return (
                <motion.article
                  key={product.id}
                  initial={{ opacity: 0, y: 16 }}
                  animate={{ opacity: 1, y: 0 }}
                  transition={{ duration: 0.25, delay: index * 0.03 }}
                  className="group flex min-h-[520px] flex-col overflow-hidden rounded-2xl border border-border bg-white shadow-card transition duration-300 hover:-translate-y-1 hover:border-orange-200 hover:shadow-xl"
                >
                  <div className="relative h-44 overflow-hidden bg-muted">
                    <img src={product.image} alt={product.name} className="h-full w-full object-cover transition duration-500 group-hover:scale-105" />
                    <Badge className="absolute left-3 top-3 bg-white text-purple-800 hover:bg-white">{product.category}</Badge>
                  </div>
                  <div className="flex flex-1 flex-col p-5">
                    <h3 className="text-lg font-bold text-foreground">{product.name}</h3>
                    <p className="mt-2 min-h-[44px] text-sm text-muted-foreground">{product.description}</p>
                    {product.includes && (
                      <ul className="mt-4 space-y-1.5 text-sm text-slate-600">
                        {product.includes.map((item) => (
                          <li key={item} className="flex gap-2">
                            <CheckCircle2 className="mt-0.5 h-4 w-4 shrink-0 text-emerald-500" />
                            <span>{item}</span>
                          </li>
                        ))}
                      </ul>
                    )}
                    <div className="mt-auto space-y-4 pt-5">
                      <div>
                        <p className="mb-2 text-xs font-semibold uppercase text-muted-foreground">{product.optionLabel}</p>
                        <Select value={selection.option} onValueChange={(value) => updateSelection(product.id, { option: value })}>
                          <SelectTrigger>
                            <SelectValue placeholder={`Select ${product.optionLabel.toLowerCase()}`} />
                          </SelectTrigger>
                          <SelectContent>
                            {product.options.map((option) => (
                              <SelectItem key={option.label} value={option.label}>
                                {option.label} - {currency.format(option.price)}
                              </SelectItem>
                            ))}
                          </SelectContent>
                        </Select>
                      </div>
                      <div className="flex items-center justify-between gap-3">
                        <div className="flex h-10 items-center rounded-full border border-border bg-slate-50">
                          <Button
                            type="button"
                            variant="ghost"
                            size="icon"
                            className="h-9 w-9 rounded-full"
                            onClick={() => updateSelection(product.id, { quantity: Math.max(1, selection.quantity - 1) })}
                          >
                            <Minus className="h-4 w-4" />
                          </Button>
                          <span className="w-8 text-center text-sm font-bold">{selection.quantity}</span>
                          <Button
                            type="button"
                            variant="ghost"
                            size="icon"
                            className="h-9 w-9 rounded-full"
                            onClick={() => updateSelection(product.id, { quantity: selection.quantity + 1 })}
                          >
                            <Plus className="h-4 w-4" />
                          </Button>
                        </div>
                        <div className="text-right">
                          <p className="text-xs text-muted-foreground">Total</p>
                          <p className="text-xl font-bold text-purple-900">{currency.format(finalPrice)}</p>
                        </div>
                      </div>
                      <Button className="w-full gap-2 bg-orange-500 hover:bg-orange-600" onClick={() => createOrder(product)} disabled={isRedirecting}>
                        {isRedirecting ? <Loader2 className="h-4 w-4 animate-spin" /> : <ShoppingBag className="h-4 w-4" />}
                        {isRedirecting ? "Redirecting..." : "Buy Now"}
                      </Button>
                    </div>
                  </div>
                </motion.article>
              );
            })}
          </div>
        </div>

        <aside className="space-y-5">
          <div className="rounded-2xl border border-border bg-white p-5 shadow-card">
            <div className="flex items-center justify-between">
              <h3 className="font-bold">Recently Purchased</h3>
              <PackageCheck className="h-5 w-5 text-orange-500" />
            </div>
            <div className="mt-4 space-y-3">
              {loadingOrders && <p className="text-sm text-muted-foreground">Loading orders...</p>}
              {!loadingOrders && orderGroups.recent.length === 0 && <p className="text-sm text-muted-foreground">No purchases yet.</p>}
              {orderGroups.recent.map((order) => (
                <div key={order.id} className="rounded-xl border border-border bg-slate-50 p-3">
                  <div className="flex items-start justify-between gap-2">
                    <div>
                      <p className="text-sm font-semibold">{order.productName}</p>
                      <p className="text-xs text-muted-foreground">{order.selectedOption} x {order.quantity}</p>
                    </div>
                    <Badge variant="outline" className={statusStyles[order.paymentStatus]}>{order.paymentStatus}</Badge>
                  </div>
                  <div className="mt-3 flex items-center justify-between">
                    <span className="text-sm font-bold">{currency.format(order.finalPrice)}</span>
                    <Button type="button" variant="ghost" size="sm" className="h-8 gap-1 px-2" onClick={() => downloadInvoice(order)}>
                      <Download className="h-3.5 w-3.5" />
                      Invoice
                    </Button>
                  </div>
                </div>
              ))}
            </div>
          </div>

          <div className="rounded-2xl border border-border bg-white p-5 shadow-card">
            <div className="flex items-center gap-2">
              <Award className="h-5 w-5 text-purple-700" />
              <h3 className="font-bold">Payment Summary</h3>
            </div>
            <div className="mt-4 grid grid-cols-2 gap-3 text-sm">
              <div className="rounded-xl bg-amber-50 p-3 text-amber-800">
                <p className="text-2xl font-bold">{orderGroups.pending.length}</p>
                <p>Pending payments</p>
              </div>
              <div className="rounded-xl bg-emerald-50 p-3 text-emerald-800">
                <p className="text-2xl font-bold">{orderGroups.successful.length}</p>
                <p>Successful payments</p>
              </div>
            </div>
          </div>
        </aside>
      </div>
    </section>
  );
};

export default TeacherShopSection;
