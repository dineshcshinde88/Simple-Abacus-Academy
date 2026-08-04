import { useEffect, useMemo, useState } from "react";
import { useNavigate } from "react-router-dom";
import { Award, Download, LockKeyhole } from "lucide-react";
import StudentLayout from "@/layouts/StudentLayout";
import { Button } from "@/components/ui/button";
import { useToast } from "@/hooks/use-toast";
import { downloadStudentCertificate } from "@/lib/certificatePdf";
import { fetchStudentDashboard, StudentDashboardData } from "@/services/studentApi";

const TOKEN_KEY = "abacus_auth_token";

const levelNumber = (value?: string | null) => {
  const values = value?.match(/level\s*(\d+)/gi) || [];
  return values.reduce((highest, item) => Math.max(highest, Number(item.match(/\d+/)?.[0] || 0)), 0);
};

const StudentCertificates = () => {
  const navigate = useNavigate();
  const { toast } = useToast();
  const [data, setData] = useState<StudentDashboardData | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const token = localStorage.getItem(TOKEN_KEY);
    if (!token) {
      navigate("/student-login", { replace: true });
      return;
    }

    fetchStudentDashboard(token)
      .then(setData)
      .catch((error) => toast({
        title: "Certificates unavailable",
        description: error instanceof Error ? error.message : "Unable to load certificate details.",
        variant: "destructive",
      }))
      .finally(() => setLoading(false));
  }, [navigate, toast]);

  const availableLevels = useMemo(() => {
    const values = [levelNumber(data?.level)];
    for (const subscription of data?.subscriptions || []) {
      values.push(levelNumber(subscription.levelName), levelNumber(subscription.planName));
    }
    return Math.min(7, Math.max(...values, 0));
  }, [data]);

  const handleDownload = async (level: number) => {
    if (!data?.name) return;
    try {
      await downloadStudentCertificate({
        studentName: data.name,
        levelName: `Level ${level}`,
        courseName: "Abacus",
      });
      toast({ title: "Certificate downloaded", description: `Your Level ${level} certificate PDF is ready.` });
    } catch {
      toast({ title: "Download failed", description: "Please try downloading the certificate again.", variant: "destructive" });
    }
  };

  return (
    <StudentLayout
      header={(
        <div>
          <h1 className="text-2xl md:text-3xl font-heading font-bold text-slate-900">Certificates</h1>
          <p className="mt-1 text-sm text-slate-500">Download certificates for your completed course levels.</p>
        </div>
      )}
    >
      <div className="mx-auto max-w-5xl space-y-6">
        <div className="rounded-2xl bg-gradient-to-r from-[#4b1e83] to-[#6d28d9] p-6 text-white shadow-card">
          <div className="flex items-center gap-4">
            <div className="rounded-full bg-white/20 p-4"><Award className="h-8 w-8" /></div>
            <div>
              <h2 className="text-xl font-heading font-bold">Course Completion Certificates</h2>
              <p className="mt-1 text-sm text-white/80">Available PDFs include your name, course level, issue date and certificate ID.</p>
            </div>
          </div>
        </div>

        {loading ? (
          <div className="rounded-2xl bg-white p-8 text-center text-slate-500 shadow-card">Loading certificates...</div>
        ) : availableLevels === 0 ? (
          <div className="rounded-2xl border border-amber-200 bg-amber-50 p-8 text-center shadow-card">
            <LockKeyhole className="mx-auto h-9 w-9 text-amber-600" />
            <h3 className="mt-3 font-heading text-lg font-bold text-slate-900">No certificate is available yet</h3>
            <p className="mt-1 text-sm text-slate-600">A certificate becomes available after a course level is assigned and completed.</p>
          </div>
        ) : (
          <div className="grid gap-4 sm:grid-cols-2">
            {Array.from({ length: availableLevels }, (_, index) => index + 1).map((level) => (
              <div key={level} className="flex items-center justify-between gap-4 rounded-2xl bg-white p-5 shadow-card">
                <div className="flex items-center gap-4">
                  <div className="flex h-12 w-12 items-center justify-center rounded-full bg-purple-100 font-bold text-[#5b21b6]">{level}</div>
                  <div>
                    <h3 className="font-heading font-bold text-slate-900">Abacus Level {level}</h3>
                    <p className="text-xs font-semibold text-emerald-600">Available</p>
                  </div>
                </div>
                <Button onClick={() => void handleDownload(level)} className="bg-orange-500 hover:bg-orange-600">
                  <Download className="mr-2 h-4 w-4" /> PDF
                </Button>
              </div>
            ))}
          </div>
        )}
      </div>
    </StudentLayout>
  );
};

export default StudentCertificates;
