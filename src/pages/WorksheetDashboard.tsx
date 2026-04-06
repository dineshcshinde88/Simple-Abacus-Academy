import { useEffect, useState } from "react";
import Navbar from "@/components/layout/Navbar";
import Footer from "@/components/layout/Footer";
import { useAuth } from "@/context/AuthContext";
import { getMySubscription, getWorksheetDownload, getWorksheets, Subscription, Worksheet } from "@/lib/subscription";
import { Button } from "@/components/ui/button";
import { CalendarCheck, FileText, ShieldCheck } from "lucide-react";

const WorksheetDashboard = () => {
  const { user, token } = useAuth();
  const [subscription, setSubscription] = useState<Subscription | null>(null);
  const [worksheets, setWorksheets] = useState<Worksheet[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    const loadData = async () => {
      if (!token) return;
      try {
        const subResponse = await getMySubscription(token);
        setSubscription(subResponse.subscription);
        if (subResponse.subscription?.status === "active") {
          const wsResponse = await getWorksheets(token);
          setWorksheets(wsResponse.worksheets);
        }
      } catch (err) {
        setError(err instanceof Error ? err.message : "Failed to load data");
      } finally {
        setLoading(false);
      }
    };

    void loadData();
  }, [token]);

  const handleDownload = async (id: string) => {
    if (!token) return;
    const response = await getWorksheetDownload(token, id);
    window.open(response.url, "_blank", "noopener,noreferrer");
  };

  return (
    <div className="min-h-screen bg-background">
      <Navbar />
      <main className="pt-16">
        <section className="bg-muted/40 py-16">
          <div className="container mx-auto px-4">
            <h1 className="text-3xl md:text-4xl font-heading font-bold text-foreground">
              Worksheet Dashboard
            </h1>
            <p className="text-muted-foreground mt-2">
              Welcome {user?.name}. Manage your subscription and worksheets here.
            </p>
          </div>
        </section>

        <section className="py-12">
          <div className="container mx-auto px-4 space-y-6">
            {loading ? (
              <div className="text-muted-foreground">Loading subscription...</div>
            ) : error ? (
              <div className="text-destructive">{error}</div>
            ) : (
              <div className="grid md:grid-cols-3 gap-6">
                <div className="bg-card border border-border rounded-xl p-5 shadow-card">
                  <div className="flex items-center gap-2 mb-2">
                    <ShieldCheck className="w-5 h-5 text-secondary" />
                    <h3 className="text-lg font-semibold">Active Plan</h3>
                  </div>
                  <div className="text-sm text-muted-foreground">Plan</div>
                  <div className="text-xl font-heading font-bold text-foreground">
                    {subscription?.planName || "No active plan"}
                  </div>
                </div>

                <div className="bg-card border border-border rounded-xl p-5 shadow-card">
                  <div className="flex items-center gap-2 mb-2">
                    <CalendarCheck className="w-5 h-5 text-secondary" />
                    <h3 className="text-lg font-semibold">Expiry Date</h3>
                  </div>
                  <div className="text-sm text-muted-foreground">Valid until</div>
                  <div className="text-xl font-heading font-bold text-foreground">
                    {subscription?.expiryDate
                      ? new Date(subscription.expiryDate).toLocaleDateString()
                      : "—"}
                  </div>
                </div>

                <div className="bg-card border border-border rounded-xl p-5 shadow-card">
                  <div className="flex items-center gap-2 mb-2">
                    <FileText className="w-5 h-5 text-secondary" />
                    <h3 className="text-lg font-semibold">Worksheets</h3>
                  </div>
                  <div className="text-sm text-muted-foreground">Available</div>
                  <div className="text-xl font-heading font-bold text-foreground">{worksheets.length}</div>
                </div>
              </div>
            )}

            {subscription?.status !== "active" ? (
              <div className="bg-muted/40 border border-border rounded-xl p-6 text-muted-foreground">
                Your subscription is not active. Please subscribe to access worksheets.
              </div>
            ) : (
              <div className="bg-card border border-border rounded-xl p-6 shadow-card">
                <h2 className="text-xl font-heading font-bold text-foreground mb-4">Worksheet Library</h2>
                <div className="grid md:grid-cols-2 gap-4">
                  {worksheets.map((ws) => (
                    <div key={ws._id} className="border border-border rounded-lg p-4 flex items-center justify-between">
                      <div>
                        <div className="text-sm text-muted-foreground">{ws.level}</div>
                        <div className="font-semibold text-foreground">{ws.title}</div>
                      </div>
                      <Button size="sm" variant="outline" onClick={() => handleDownload(ws._id)}>
                        Download
                      </Button>
                    </div>
                  ))}
                </div>
              </div>
            )}
          </div>
        </section>
      </main>
      <Footer />
    </div>
  );
};

export default WorksheetDashboard;
