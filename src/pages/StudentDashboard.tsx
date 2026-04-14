import { useEffect, useMemo, useState } from "react";
import { useNavigate } from "react-router-dom";
import { fetchStudentDashboard, StudentDashboardData } from "@/services/studentApi";
import { Button } from "@/components/ui/button";
import { useToast } from "@/hooks/use-toast";
import StudentLayout from "@/layouts/StudentLayout";
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from "@/components/ui/dialog";
import { Download, Trophy } from "lucide-react";

const TOKEN_KEY = "abacus_auth_token";

const StudentDashboard = () => {
  const { toast } = useToast();
  const navigate = useNavigate();
  const [data, setData] = useState<StudentDashboardData | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const token = localStorage.getItem(TOKEN_KEY);
    if (!token) {
      navigate("/student-login", { replace: true });
      return;
    }

    const loadDashboard = async () => {
      try {
        const response = await fetchStudentDashboard(token);
        setData(response);
      } catch (error) {
        const message = error instanceof Error ? error.message : "Failed to load dashboard";
        toast({ title: "Dashboard error", description: message });
      } finally {
        setLoading(false);
      }
    };

    void loadDashboard();
  }, [navigate, toast]);

  const isExpired = data?.subscriptionStatus === "expired";
  const currentLevelNumber = Number((data?.level || "").match(/\d+/)?.[0] || 0);

  const handleCertificateDownload = (level: number) => {
    toast({ title: "Download started", description: `Level ${level} certificate downloading...` });
  };

  const summaryCards = useMemo(
    () => [
      {
        title: "Enrolled",
        subtitle: "Batches",
        count: data?.batchesCount ?? 0,
        color: "bg-emerald-500",
        button: "View More Details",
        to: "/student/batches",
      },
      {
        title: "Subscribed",
        subtitle: "Worksheets",
        count: data?.worksheetsCount ?? 0,
        color: "bg-blue-500",
        button: "View More Details",
        to: "/student/worksheets",
        disabledWhenExpired: true,
      },
      {
        title: "Subscribed",
        subtitle: "Video Tutorials",
        count: data?.videosCount ?? 0,
        color: "bg-orange-500",
        button: "View More Details",
        to: "/student/videos",
        disabledWhenExpired: true,
      },
      {
        title: "Allocated",
        subtitle: "Courses",
        count: data?.level ? 1 : 0,
        color: "bg-purple-600",
        button: null,
        to: "/student/courses",
      },
    ],
    [data],
  );

  if (loading) {
    return (
      <div className="min-h-screen flex items-center justify-center text-muted-foreground">Loading dashboard...</div>
    );
  }

  return (
    <StudentLayout
      header={(
        <div className="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
          <div>
            <h1 className="text-2xl md:text-3xl font-heading font-bold text-slate-900">
              Welcome, {data?.name || "Student"}
            </h1>
            <p className="text-sm text-slate-500 mt-1">
              Current Level: {data?.level || "Not Assigned"}
            </p>
          </div>
          <div className="flex items-center gap-3 rounded-full bg-slate-100 px-3 py-2">
            <div className="h-9 w-9 rounded-full bg-white border border-slate-200 flex items-center justify-center text-slate-500">
              {(data?.name || "S").slice(0, 1).toUpperCase()}
            </div>
            <div className="text-sm font-semibold text-slate-700">{data?.name || "Student"}</div>
          </div>
        </div>
      )}
    >
      <div className="space-y-6">
        <div className="bg-white rounded-2xl shadow-card px-6 py-4 flex items-center gap-3">
          <div className="h-10 w-10 rounded-full bg-[#5b21b6] text-white flex items-center justify-center text-lg font-bold">
            D
          </div>
          <h2 className="text-xl font-heading font-bold text-[#5b21b6]">Dashboard</h2>
        </div>

        {isExpired && (
          <div className="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-red-700 font-medium">
            Your subscription has expired.
          </div>
        )}

        <div className="grid gap-6 sm:grid-cols-2 xl:grid-cols-4">
          {summaryCards.map((card) => (
            <div key={card.subtitle} className="bg-white rounded-2xl shadow-card p-5">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-xs text-slate-500 uppercase tracking-wide">{card.title}</p>
                  <h3 className="text-lg font-heading font-bold text-slate-900">{card.subtitle}</h3>
                </div>
                <div className={`h-11 w-11 rounded-full ${card.color} text-white flex items-center justify-center text-lg font-bold`}>
                  {card.count}
                </div>
              </div>
              {card.button && (
                <Button
                  className="mt-5 w-full bg-slate-900 hover:bg-slate-800"
                  onClick={
                    isExpired && card.disabledWhenExpired ? undefined : () => navigate(card.to)
                  }
                  disabled={isExpired && card.disabledWhenExpired}
                  title={isExpired && card.disabledWhenExpired ? "Subscription expired" : undefined}
                >
                  {card.button}
                </Button>
              )}
            </div>
          ))}
        </div>

        <Dialog>
          <DialogTrigger asChild>
            <button
              type="button"
              className="w-full text-left bg-gradient-to-r from-[#4b1e83] via-[#5b21b6] to-[#6d28d9] rounded-2xl shadow-card px-6 py-6 text-white flex flex-col gap-4 md:flex-row md:items-center md:justify-between"
            >
              <div className="flex items-center gap-4">
                <div className="h-14 w-14 rounded-full bg-white/20 flex items-center justify-center text-2xl">
                  <Trophy className="h-7 w-7 text-white" />
                </div>
                <div>
                  <h3 className="text-xl font-heading font-bold">Course Completion Certificate</h3>
                  <p className="text-sm text-white/80">
                    Congratulations! You have successfully completed Abacus Level 7. Download your certificate to
                    showcase your achievement.
                  </p>
                </div>
              </div>
              <div className="inline-flex items-center gap-2 rounded-full bg-orange-500 px-5 py-2 text-sm font-semibold shadow-md whitespace-nowrap">
                <Download className="h-4 w-4" /> Download Certificate
              </div>
            </button>
          </DialogTrigger>
          <DialogContent className="max-w-3xl p-0 overflow-hidden">
            <div className="bg-gradient-to-r from-[#4b1e83] to-[#6d28d9] text-white px-6 py-6">
              <DialogHeader>
                <DialogTitle className="text-xl md:text-2xl font-heading font-bold">Course Certificates</DialogTitle>
              </DialogHeader>
              <p className="text-sm text-white/80">Track your progress and unlock certificates</p>
            </div>
            <div className="p-6">
              <div className="rounded-xl border border-slate-200 overflow-hidden">
                <div className="grid grid-cols-[1fr_1fr] bg-slate-100 text-slate-600 text-xs font-semibold uppercase tracking-wide px-6 py-3">
                  <span>Course Level</span>
                  <span>Certificate Download</span>
                </div>
                <div className="divide-y divide-slate-200">
                  {Array.from({ length: 7 }, (_, index) => {
                    const level = index + 1;
                    const isUnlocked = currentLevelNumber >= level;
                    return (
                      <div key={level} className="grid grid-cols-[1fr_1fr] items-center px-6 py-4">
                        <div className="flex items-center gap-4">
                          <div className="h-10 w-10 rounded-full bg-[#5b21b6] text-white flex items-center justify-center font-bold">
                            {level}
                          </div>
                          <div className="font-semibold text-slate-800">Level {level}</div>
                        </div>
                        <div className="flex items-center gap-3">
                          <button
                            type="button"
                            disabled={!isUnlocked}
                            onClick={() => handleCertificateDownload(level)}
                            className={`inline-flex items-center gap-2 rounded-full px-5 py-2 text-sm font-semibold ${
                              isUnlocked
                                ? "bg-orange-500 text-white hover:bg-orange-600"
                                : "bg-slate-200 text-slate-400 cursor-not-allowed"
                            }`}
                          >
                            <Download className="h-4 w-4" /> Download
                          </button>
                          <span
                            className={`text-xs font-semibold px-3 py-1 rounded-full ${
                              isUnlocked ? "bg-emerald-100 text-emerald-700" : "bg-slate-100 text-slate-400"
                            }`}
                          >
                            {isUnlocked ? "Available" : "Locked"}
                          </span>
                        </div>
                      </div>
                    );
                  })}
                </div>
              </div>
            </div>
          </DialogContent>
        </Dialog>
      </div>
    </StudentLayout>
  );
};

export default StudentDashboard;
