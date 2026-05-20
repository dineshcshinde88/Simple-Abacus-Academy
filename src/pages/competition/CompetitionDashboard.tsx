import { useEffect, useMemo, useState } from "react";
import type { ReactNode } from "react";
import { Link, Navigate, useLocation, useNavigate } from "react-router-dom";
import {
  BarChart3,
  BookOpen,
  Calendar,
  Clock,
  FileText,
  Eye,
  Search,
  Home,
  LayoutPanelLeft,
  Lock,
  LogOut,
  Medal,
  Play,
  ShoppingBag,
  TrendingUp,
  Trophy,
  User,
  WalletCards,
} from "lucide-react";
import { Button } from "@/components/ui/button";
import {
  type CompetitionDashboardData,
  type CompetitionLeaderboardResponse,
  type CompetitionListItem,
  getCompetitionDashboard,
  getCompetitionList,
  getCompetitionLeaderboard,
} from "@/services/competitionApi";

const COMPETITION_TOKEN_KEY = "competition_auth_token";
const COMPETITION_USER_KEY = "competition_auth_user";
const PRACTICE_ATTEMPTS_KEY = "competition_practice_attempts";

const navItems = [
  { label: "Dashboard", icon: Home, to: "/competition/dashboard" },
  { label: "Practice Kits", icon: BookOpen, to: "/competition/practice-kits" },
  { label: "Competitions", icon: Trophy, to: "/competition/competitions" },
  { label: "Leaderboard", icon: Medal, to: "/competition/leaderboard" },
  { label: "Competition Results", icon: BarChart3, to: "/competition/results" },
  { label: "My Profile", icon: User, to: "/competition/profile" },
  { label: "My Purchases", icon: ShoppingBag, to: "/competition/purchases" },
];

const kit = {
  code: "PK-0003",
  title: "Category B",
  assignment: "Assignment #1",
  papers: 10,
  duration: "3 min",
  expires: "5 Aug 2026",
  daysLeft: 78,
};

const kitPapers = Array.from({ length: 10 }, (_, index) => {
  const number = 20 - index;
  return {
    id: `cat-b-${number}`,
    code: `QP-${String(number).padStart(4, "0")}`,
    title: `Cat B-${number - 10}`,
  };
});

const CompetitionDashboard = () => {
  const location = useLocation();
  const navigate = useNavigate();
  const token = localStorage.getItem(COMPETITION_TOKEN_KEY);
  const [dashboard, setDashboard] = useState<CompetitionDashboardData | null>(null);
  const [competitionList, setCompetitionList] = useState<{ upcomingCompetitions: CompetitionListItem[]; completedCompetitions: CompetitionListItem[] }>({
    upcomingCompetitions: [],
    completedCompetitions: [],
  });
  const [loadingDashboard, setLoadingDashboard] = useState(true);
  const [attempts, setAttempts] = useState<Record<string, Record<number, "available" | "in_progress" | "completed">>>(() => {
    try {
      return JSON.parse(localStorage.getItem(PRACTICE_ATTEMPTS_KEY) || "{}");
    } catch {
      return {};
    }
  });
  const user = useMemo(() => {
    try {
      return JSON.parse(localStorage.getItem(COMPETITION_USER_KEY) || "{}") as { name?: string; email?: string };
    } catch {
      return {};
    }
  }, []);

  useEffect(() => {
    if (!token) return;
    let active = true;
    setLoadingDashboard(true);
    getCompetitionDashboard(token)
      .then((data) => {
        if (active) setDashboard(data);
      })
      .catch(() => {
        if (active) setDashboard(null);
      })
      .finally(() => {
        if (active) setLoadingDashboard(false);
      });
    return () => {
      active = false;
    };
  }, [token]);

  useEffect(() => {
    let active = true;
    getCompetitionList()
      .then((data) => {
        if (active) setCompetitionList(data);
      })
      .catch(() => {
        if (active) setCompetitionList({ upcomingCompetitions: [], completedCompetitions: [] });
      });
    return () => {
      active = false;
    };
  }, []);

  if (!token) {
    return <Navigate to="/online-competition" replace />;
  }

  const active = navItems.find((item) => location.pathname === item.to || (item.to !== "/competition/dashboard" && location.pathname.startsWith(item.to))) || navItems[0];
  const isDashboard = active.to === "/competition/dashboard";
  const isPractice = location.pathname.includes("practice-kits");
  const isPracticeDetail = location.pathname.startsWith("/competition/practice-kits/");
  const isCompetitions = location.pathname.includes("competitions");
  const isLeaderboard = location.pathname.includes("leaderboard");
  const isResults = location.pathname.includes("results");
  const isPurchases = location.pathname.includes("purchases");
  const isProfile = location.pathname.includes("profile");
  const summary = dashboard?.summary || {
    upcomingCompetitions: 0,
    purchasedCompetitions: 0,
    activeKits: 0,
    expiredKits: 0,
    examsCompleted: 0,
    averageScore: 0,
  };
  const profile = dashboard?.profile || {
    id: "",
    name: user.name || "Competition Student",
    email: user.email || "student@example.com",
    school: null,
    gender: null,
    dateOfBirth: null,
    phone: null,
    maatsCategory: null,
    maatsSubcategory: null,
    calculusGrade: null,
    streetAddress: null,
    city: null,
    state: null,
    pinCode: null,
    country: null,
  };
  const hasPracticeKitPurchase = (dashboard?.practiceKits.length || 0) > 0;
  const upcomingCompetitions = dashboard?.upcomingCompetitions?.length ? dashboard.upcomingCompetitions : competitionList.upcomingCompetitions;
  const completedCompetitions = dashboard?.completedCompetitions?.length ? dashboard.completedCompetitions : competitionList.completedCompetitions;

  const logout = () => {
    localStorage.removeItem(COMPETITION_TOKEN_KEY);
    localStorage.removeItem(COMPETITION_USER_KEY);
    navigate("/online-competition");
  };

  const setAttemptStatus = (paperId: string, attemptNo: number, status: "available" | "in_progress" | "completed") => {
    setAttempts((current) => {
      const next = {
        ...current,
        [paperId]: {
          ...(current[paperId] || {}),
          [attemptNo]: status,
        },
      };
      localStorage.setItem(PRACTICE_ATTEMPTS_KEY, JSON.stringify(next));
      return next;
    });
  };

  return (
    <div className="min-h-screen bg-white text-slate-950">
      <aside className="fixed inset-y-0 left-0 hidden w-64 bg-[#0b86b4] text-white lg:flex lg:flex-col">
        <div className="px-5 py-6">
          <div className="rounded-lg bg-white p-2">
            <img src="/abacus_logo.png" alt="Simple Abacus" className="h-9 w-auto object-contain" />
          </div>
          <div className="mt-2 text-xs font-semibold text-white/90">Student</div>
        </div>

        <div className="px-4 text-xs font-bold uppercase text-white/80">Menu</div>
        <nav className="mt-3 flex-1 space-y-1 px-2">
          {navItems.map((item) => {
            const selected = active.to === item.to;
            return (
              <Link
                key={item.label}
                to={item.to}
                className={`flex items-center gap-3 rounded-md px-4 py-2.5 text-sm font-semibold ${selected ? "bg-[#04739b]" : "hover:bg-white/10"}`}
              >
                <item.icon className="h-4 w-4" />
                {item.label}
              </Link>
            );
          })}
        </nav>

        <div className="px-4 py-5">
          <div className="flex items-center gap-3">
            <div className="grid h-9 w-9 place-items-center rounded-lg bg-white/20 text-xs">{getInitials(profile.name)}</div>
            <div className="min-w-0 flex-1">
              <div className="truncate text-sm font-bold">{profile.name}</div>
              <div className="truncate text-xs">{profile.email}</div>
            </div>
          </div>
        </div>
      </aside>

      <main className="lg:ml-64">
        <header className="sticky top-0 z-30 flex h-16 items-center border-b border-slate-200 bg-white">
          <div className="grid h-16 w-12 place-items-center border-r border-slate-200">
            <LayoutPanelLeft className="h-4 w-4 text-slate-600" />
          </div>
          <div className="flex items-center gap-2 px-4 text-sm text-slate-500">
            <Link to="/competition/dashboard">Online Competition Dashboard</Link>
            {!isDashboard && (
              <>
                <span>/</span>
                <span className="text-slate-700">{active.label}</span>
              </>
            )}
          </div>
          <button type="button" onClick={logout} className="ml-auto mr-4 hidden items-center gap-2 text-sm font-semibold text-slate-600 lg:flex">
            <LogOut className="h-4 w-4" /> Logout
          </button>
        </header>

        <nav className="flex gap-2 overflow-x-auto border-b border-slate-200 bg-white px-4 py-3 lg:hidden">
          {navItems.map((item) => {
            const selected = active.to === item.to;
            return (
              <Link
                key={item.label}
                to={item.to}
                className={`flex shrink-0 items-center gap-2 rounded-md px-3 py-2 text-xs font-semibold ${
                  selected ? "bg-[#0b86b4] text-white" : "bg-slate-100 text-slate-700"
                }`}
              >
                <item.icon className="h-3.5 w-3.5" />
                {item.label}
              </Link>
            );
          })}
        </nav>

        <section className="px-4 py-6 sm:px-5 lg:px-7 lg:py-8">
          {!isDashboard && (
            <div className="mb-7">
              <h1 className="text-3xl font-bold tracking-normal">{active.label}</h1>
              <p className="mt-1 text-slate-500">
                {isPractice ? "Browse and manage your practice materials" : "Manage your online competition activity"}
              </p>
            </div>
          )}

          {isDashboard && (
            <DashboardHome
              name={profile.name}
              onPractice={() => navigate("/competition/practice-kits")}
              summary={summary}
              loading={loadingDashboard}
              upcomingCompetitions={upcomingCompetitions}
            />
          )}

          {isPractice && !isPracticeDetail && (
            <PracticeKitsView kits={dashboard?.practiceKits || []} onStart={() => navigate("/competition/practice-kits/category-b")} />
          )}

          {isPracticeDetail && hasPracticeKitPurchase && (
            <PracticeKitDetail
              attempts={attempts}
              onStartAttempt={(paperId, attemptNo) => setAttemptStatus(paperId, attemptNo, "in_progress")}
              onCompleteAttempt={(paperId, attemptNo) => setAttemptStatus(paperId, attemptNo, "completed")}
            />
          )}

          {isPracticeDetail && !hasPracticeKitPurchase && (
            <EmptyState title="No active practice kit" text="Practice kit details will become available after a student purchases or receives access." />
          )}

          {isCompetitions && (
            <CompetitionsView
              upcoming={upcomingCompetitions}
              completed={completedCompetitions}
            />
          )}
          {isLeaderboard && <LeaderboardView />}
          {isResults && <Placeholder title="Competition Results" text="Published results and downloadable score cards will appear here." />}
          {isPurchases && <PurchasesView summary={summary} kits={dashboard?.practiceKits || []} />}
          {isProfile && <ProfileView profile={profile} />}
        </section>
      </main>
    </div>
  );
};

const DashboardHome = ({
  name,
  onPractice,
  summary,
  loading,
  upcomingCompetitions,
}: {
  name: string;
  onPractice: () => void;
  summary: CompetitionDashboardData["summary"];
  loading: boolean;
  upcomingCompetitions: CompetitionListItem[];
}) => (
  <div className="space-y-8">
    <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
      <div>
        <h1 className="text-2xl font-bold tracking-normal sm:text-3xl">Welcome back, {name}!</h1>
        <p className="mt-2 text-slate-500">Here's what's happening with your learning today.</p>
      </div>
      <Button className="h-10 bg-blue-600 hover:bg-blue-700" onClick={onPractice}>
        <BookOpen className="mr-2 h-4 w-4" /> Practice Now
      </Button>
    </div>

    <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
      <DashboardCard icon={Trophy} label="Upcoming Competitions" value={loading ? "..." : String(summary.upcomingCompetitions)} tone="blue" />
      <DashboardCard icon={Trophy} label="Exams Completed" value={loading ? "..." : String(summary.examsCompleted)} tone="green" />
      <DashboardCard icon={TrendingUp} label="Average Score" value={loading ? "..." : `${Math.round(summary.averageScore)}%`} tone="purple" />
      <DashboardCard icon={BookOpen} label="Active Kits" value={loading ? "..." : String(summary.activeKits)} tone="orange" />
    </div>

    <div className="grid gap-6 xl:grid-cols-[1fr_328px]">
      <section>
        <div className="mb-5 flex items-center justify-between">
          <h2 className="flex items-center gap-2 text-xl font-bold">
            <Trophy className="h-5 w-5 text-purple-600" /> Upcoming Competitions
          </h2>
          <Link to="/competition/competitions" className="text-sm font-semibold text-purple-600">
            View All
          </Link>
        </div>
        {upcomingCompetitions.length > 0 ? (
          <div className="space-y-3">
            {upcomingCompetitions.slice(0, 2).map((competition) => (
              <CompetitionCard key={competition.id} competition={competition} />
            ))}
          </div>
        ) : (
          <div className="rounded-lg border border-slate-200 bg-white p-8 text-center shadow-sm">
            <Calendar className="mx-auto h-9 w-9 text-slate-300" />
            <h3 className="mt-4 text-lg font-bold text-slate-900">No upcoming competitions</h3>
            <p className="mt-2 text-sm text-slate-500">
              Competitions added by admin will appear here automatically.
            </p>
          </div>
        )}
      </section>

      <aside className="rounded-lg bg-gradient-to-br from-blue-600 to-violet-700 p-6 text-white shadow-sm">
        <h2 className="flex items-center gap-2 text-lg font-bold">
          <span className="grid h-5 w-5 place-items-center rounded-full border border-white/70 text-xs">i</span>
          Quick Access
        </h2>
        <div className="mt-7 space-y-3">
          <QuickLink to="/competition/competitions" icon={Calendar} label="Upcoming Competitions" />
          <QuickLink to="/competition/practice-kits" icon={BookOpen} label="Browse Practice Kits" />
          <QuickLink to="/competition/profile" icon={TrendingUp} label="Update Profile" />
        </div>
      </aside>
    </div>
  </div>
);

const DashboardCard = ({
  icon: Icon,
  label,
  value,
  tone,
}: {
  icon: typeof Trophy;
  label: string;
  value: string;
  tone: "blue" | "green" | "purple" | "orange";
}) => {
  const styles = {
    blue: "bg-blue-50 text-blue-600",
    green: "bg-green-50 text-green-600",
    purple: "bg-purple-50 text-purple-600",
    orange: "bg-orange-50 text-orange-600",
  };
  return (
    <div className="flex min-h-36 items-center gap-5 rounded-lg border border-slate-200 bg-white p-5 shadow-sm sm:min-h-44 sm:p-6">
      <div className={`grid h-12 w-12 place-items-center rounded-full ${styles[tone]}`}>
        <Icon className="h-6 w-6" />
      </div>
      <div>
        <p className="max-w-28 text-sm font-semibold leading-6 text-slate-500">{label}</p>
        <p className="mt-1 text-2xl font-bold text-slate-950">{value}</p>
      </div>
    </div>
  );
};

const QuickLink = ({ to, icon: Icon, label }: { to: string; icon: typeof Calendar; label: string }) => (
  <Link to={to} className="flex items-center justify-between rounded-md bg-white/15 px-4 py-3 text-sm font-semibold hover:bg-white/25">
    <span className="flex items-center gap-3">
      <Icon className="h-4 w-4" /> {label}
    </span>
    <span>-&gt;</span>
  </Link>
);

const CompetitionsView = ({ upcoming, completed }: { upcoming: CompetitionListItem[]; completed: CompetitionListItem[] }) => (
  <div className="space-y-8">
    <section>
      <div className="mb-4 flex items-center gap-2">
        <Trophy className="h-5 w-5 text-blue-600" />
        <h2 className="text-xl font-bold">Upcoming Competitions</h2>
      </div>
      {upcoming.length > 0 ? (
        <div className="grid gap-4 xl:grid-cols-2">
          {upcoming.map((competition) => (
            <CompetitionCard key={competition.id} competition={competition} />
          ))}
        </div>
      ) : (
        <EmptyState title="No upcoming competitions" text="When admin schedules a competition, it will appear here with date and time." />
      )}
    </section>

    <section>
      <div className="mb-4 flex items-center justify-between gap-3">
        <h2 className="flex items-center gap-2 text-xl font-bold">
          <Medal className="h-5 w-5 text-yellow-500" /> Completed Competitions
        </h2>
        <Link to="/competition/leaderboard" className="text-sm font-semibold text-blue-600">View Leaderboard</Link>
      </div>
      {completed.length > 0 ? (
        <div className="grid gap-4 xl:grid-cols-2">
          {completed.map((competition) => (
            <CompetitionCard key={competition.id} competition={competition} completed />
          ))}
        </div>
      ) : (
        <EmptyState title="No completed competitions" text="Completed competitions will appear here after the scheduled end time. Use Leaderboard to view participant marks listwise." />
      )}
    </section>
  </div>
);

const CompetitionCard = ({ competition, completed = false }: { competition: CompetitionListItem; completed?: boolean }) => {
  const category = competition.category_name || competition.categoryName || "Simple Abacus";
  const subcategory = competition.subcategory_name || competition.subcategoryName || "";
  const startsAt = competition.starts_at || competition.startsAt;
  const endsAt = competition.ends_at || competition.endsAt;
  const questions = competition.total_questions ?? competition.totalQuestions ?? 0;
  const duration = competition.duration_minutes ?? competition.durationMinutes ?? 0;

  return (
    <div className="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
      <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
          <h3 className="text-lg font-bold text-slate-950">{competition.title}</h3>
          <p className="mt-1 text-sm text-slate-500">{category}{subcategory ? ` / ${subcategory}` : ""}</p>
        </div>
        <span className={`w-max rounded-full px-3 py-1 text-xs font-bold ${completed ? "bg-slate-100 text-slate-600" : "bg-blue-100 text-blue-700"}`}>
          {completed ? "Completed" : competition.status}
        </span>
      </div>
      {competition.description && <p className="mt-3 text-sm text-slate-600">{competition.description}</p>}
      <div className="mt-5 grid gap-3 text-sm text-slate-600 sm:grid-cols-3">
        <span><Calendar className="mr-1 inline h-4 w-4" /> {formatCompetitionDate(startsAt)}</span>
        <span><Clock className="mr-1 inline h-4 w-4" /> {formatCompetitionTime(startsAt, endsAt)}</span>
        <span><FileText className="mr-1 inline h-4 w-4" /> {questions} questions / {duration} min</span>
      </div>
      {completed && (
        <Link to="/competition/leaderboard" className="mt-5 inline-flex rounded-md bg-yellow-500 px-4 py-2 text-sm font-semibold text-slate-950 hover:bg-yellow-600">
          View Marks List
        </Link>
      )}
    </div>
  );
};

const PracticeKitsView = ({ kits, onStart }: { kits: CompetitionDashboardData["practiceKits"]; onStart: () => void }) => (
  <>
    <div className="mb-7 flex rounded-md bg-slate-100 p-1">
      <button type="button" className="rounded-md bg-white px-5 py-2 text-sm font-semibold text-blue-600 shadow-sm">My Kits ({kits.length})</button>
      <button type="button" className="px-5 py-2 text-sm font-semibold text-slate-700">Available Kits</button>
    </div>
    <div className="mb-5 flex items-center justify-between">
      <h2 className="text-xl font-bold">My Practice Kits</h2>
      <span className="rounded-full border border-slate-200 px-3 py-1 text-sm font-semibold">{kits.filter((item) => item.accessStatus === "active").length} active kit</span>
    </div>
    {kits.length > 0 ? (
      <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        {kits.map((access) => (
          <div key={access.accessId} className="rounded-lg border border-slate-200 bg-white shadow-sm">
            <div className="h-6 rounded-t-lg border-b border-slate-100" />
            <div className={access.accessStatus === "active" ? "h-1 bg-green-500" : "h-1 bg-slate-300"} />
            <div className="space-y-5 p-4">
              <div className="mt-5 flex items-center justify-between gap-3">
                <div className="flex flex-wrap gap-2">
                  <span className="rounded-full border border-slate-200 px-3 py-1 text-xs font-semibold">PK</span>
                  <span className="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">Practice Kit</span>
                </div>
                <span className={`rounded-full px-3 py-1 text-xs font-bold ${access.accessStatus === "active" ? "bg-green-100 text-green-700" : "bg-slate-100 text-slate-500"}`}>
                  {access.accessStatus}
                </span>
              </div>
              <h3 className="font-bold">{access.title}</h3>
              <p className="min-h-10 text-sm text-slate-500">{access.description || "PDFs, videos, mock tests and MCQs will appear inside this kit."}</p>
              <div className="flex items-center justify-between text-sm text-slate-600">
                <span>{kit.papers} Papers</span>
                <span>{kit.duration}</span>
              </div>
              <p className="text-sm text-slate-500">Expires: {access.expiryDate || "after 90 days"}</p>
              <Button className="h-9 w-full bg-green-600 text-slate-950 hover:bg-green-700" onClick={onStart} disabled={access.accessStatus !== "active"}>
                <Play className="mr-2 h-4 w-4" /> Start Practice
              </Button>
            </div>
          </div>
        ))}
      </div>
    ) : (
      <EmptyState title="No practice kits purchased" text="Practice kits will appear here after a student purchases competition access or an admin assigns a kit." />
    )}
  </>
);

const PracticeKitDetail = ({
  attempts,
  onStartAttempt,
  onCompleteAttempt,
}: {
  attempts: Record<string, Record<number, "available" | "in_progress" | "completed">>;
  onStartAttempt: (paperId: string, attemptNo: number) => void;
  onCompleteAttempt: (paperId: string, attemptNo: number) => void;
}) => (
  <div className="space-y-8">
    <section className="rounded-lg bg-gradient-to-br from-blue-50 via-white to-pink-50 p-5 shadow-sm sm:p-6">
      <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
          <span className="inline-flex rounded-full border border-slate-200 bg-white px-3 py-1 text-sm font-semibold">{kit.code}</span>
          <h1 className="mt-4 text-2xl font-bold tracking-normal text-slate-950">{kit.title}</h1>
        </div>
      </div>

      <div className="mt-10 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <KitStat icon={FileText} value="10" label="Question Papers" tone="blue" />
        <KitStat icon={Clock} value="3" label="Minutes/Paper" tone="green" />
        <KitStat icon={TrendingUp} value="6" label="Attempts/Paper" tone="purple" />
        <KitStat icon={Trophy} value="90" label="Days Access" tone="orange" />
      </div>
    </section>

    <section>
      <h2 className="mb-5 text-xl font-bold">Question Papers</h2>
      <div className="space-y-4">
        {kitPapers.map((paper) => (
          <QuestionPaperCard
            key={paper.id}
            paper={paper}
            attempts={attempts[paper.id] || {}}
            onStartAttempt={(attemptNo) => onStartAttempt(paper.id, attemptNo)}
            onCompleteAttempt={(attemptNo) => onCompleteAttempt(paper.id, attemptNo)}
          />
        ))}
      </div>
    </section>
  </div>
);

const KitStat = ({
  icon: Icon,
  value,
  label,
  tone,
}: {
  icon: typeof FileText;
  value: string;
  label: string;
  tone: "blue" | "green" | "purple" | "orange";
}) => {
  const styles = {
    blue: "text-blue-600",
    green: "text-green-600",
    purple: "text-purple-600",
    orange: "text-orange-600",
  };
  return (
    <div className="rounded-lg bg-white/80 p-5 text-center shadow-sm">
      <Icon className={`mx-auto h-8 w-8 ${styles[tone]}`} />
      <p className={`mt-3 text-2xl font-bold ${styles[tone]}`}>{value}</p>
      <p className="mt-1 text-sm text-slate-600">{label}</p>
    </div>
  );
};

const QuestionPaperCard = ({
  paper,
  attempts,
  onStartAttempt,
  onCompleteAttempt,
}: {
  paper: { id: string; code: string; title: string };
  attempts: Record<number, "available" | "in_progress" | "completed">;
  onStartAttempt: (attemptNo: number) => void;
  onCompleteAttempt: (attemptNo: number) => void;
}) => {
  const used = Object.values(attempts).filter((status) => status === "completed" || status === "in_progress").length;
  const bestScore = Object.values(attempts).some((status) => status === "completed") ? 40 : 0;

  return (
    <div className="rounded-lg border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
      <div className="flex items-start justify-between gap-4">
        <div>
          <span className="inline-flex rounded-full border border-slate-200 px-3 py-1 text-xs font-semibold">{paper.code}</span>
          <h3 className="mt-3 font-bold">{paper.title}</h3>
        </div>
        <span className="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold">Best score: {bestScore}%</span>
      </div>

      <div className="mt-10 grid gap-3 text-sm text-slate-600 sm:grid-cols-2">
        <span className="text-blue-600">80 Questions</span>
        <span className="text-green-600 sm:text-center">400 Marks</span>
      </div>

      <div className="mt-3 flex items-center justify-between text-sm text-slate-600">
        <span>Attempts</span>
        <span>{used} / 6 used</span>
      </div>

      <div className="mt-3 grid gap-2 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
        {Array.from({ length: 6 }, (_, index) => {
          const attemptNo = index + 1;
          const status = attempts[attemptNo] || "available";
          return (
            <AttemptCard
              key={attemptNo}
              attemptNo={attemptNo}
              status={status}
              onStart={() => onStartAttempt(attemptNo)}
              onComplete={() => onCompleteAttempt(attemptNo)}
            />
          );
        })}
      </div>
    </div>
  );
};

const AttemptCard = ({
  attemptNo,
  status,
  onStart,
  onComplete,
}: {
  attemptNo: number;
  status: "available" | "in_progress" | "completed";
  onStart: () => void;
  onComplete: () => void;
}) => {
  const isCompleted = status === "completed";
  const isProgress = status === "in_progress";
  const score = attemptNo % 2 === 0 ? "40.0%" : "30.0%";

  return (
    <div
      className={`rounded-lg border p-3 ${
        isCompleted
          ? "border-green-200 bg-green-50"
          : isProgress
            ? "border-blue-200 bg-blue-50"
            : "border-slate-200 bg-white"
      }`}
    >
      <div className="flex items-center justify-between gap-2">
        <h4 className="font-bold">Attempt {attemptNo}</h4>
        <span className={`rounded-full px-2 py-1 text-[10px] font-bold ${
          isCompleted ? "bg-green-100 text-green-700" : isProgress ? "bg-blue-100 text-blue-700" : "bg-yellow-100 text-yellow-700"
        }`}>
          {isCompleted ? "Completed" : isProgress ? "In Progress" : "Available"}
        </span>
      </div>

      {isCompleted ? (
        <>
          <div className="mt-3 flex justify-between text-xs">
            <span>Score</span>
            <span>{score}</span>
          </div>
          <div className="mt-1 h-1.5 overflow-hidden rounded-full bg-cyan-100">
            <div className="h-full w-2/5 bg-cyan-600" />
          </div>
          <div className="mt-3 grid grid-cols-2 gap-2">
            <Button variant="outline" size="sm" className="h-8">
              <Eye className="mr-1 h-3.5 w-3.5" /> View
            </Button>
            <Button size="sm" className="h-8 bg-yellow-500 text-slate-950 hover:bg-yellow-600">
              <BarChart3 className="mr-1 h-3.5 w-3.5" /> Details
            </Button>
          </div>
        </>
      ) : (
        <Button
          className="mt-3 h-9 w-full bg-yellow-500 text-slate-950 hover:bg-yellow-600"
          onClick={isProgress ? onComplete : onStart}
        >
          <Play className="mr-2 h-4 w-4" /> {isProgress ? "Finish" : "Start"}
        </Button>
      )}
    </div>
  );
};

const PurchasesView = ({ summary, kits }: { summary: CompetitionDashboardData["summary"]; kits: CompetitionDashboardData["practiceKits"] }) => (
  <div className="space-y-7">
    <div>
      <h1 className="text-3xl font-bold tracking-normal">My Purchases</h1>
      <p className="mt-1 text-slate-500">View and manage your purchased practice kits and competition registrations</p>
    </div>

    <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
      <PurchaseStat icon={BookOpen} label="Practice Kits" value={String(kits.length)} tone="blue" />
      <PurchaseStat icon={Trophy} label="Competitions" value={String(summary.purchasedCompetitions)} tone="purple" />
      <PurchaseStat icon={TrendingUp} label="Active Kits" value={String(summary.activeKits)} tone="green" />
      <PurchaseStat icon={WalletCards} label="Expired Kits" value={String(summary.expiredKits)} tone="red" />
    </div>

    <div className="flex w-max max-w-full overflow-x-auto rounded-md bg-slate-100 p-1">
      <button type="button" className="rounded-md bg-white px-5 py-2 text-sm font-semibold text-blue-600 shadow-sm">All ({kits.length + summary.purchasedCompetitions})</button>
      <button type="button" className="px-5 py-2 text-sm font-semibold text-slate-600">Practice Kits ({kits.length})</button>
      <button type="button" className="px-5 py-2 text-sm font-semibold text-slate-600">Competitions ({summary.purchasedCompetitions})</button>
    </div>

    <section>
      <h2 className="mb-4 flex items-center gap-2 text-lg font-bold">
        <BookOpen className="h-5 w-5" /> Practice Kits
      </h2>
      {kits.length > 0 ? (
        <div className="space-y-3">
          {kits.map((item) => (
            <div key={item.accessId} className="flex flex-col gap-4 rounded-lg border border-slate-200 bg-white p-5 shadow-sm md:flex-row md:items-center md:justify-between">
              <div>
                <h3 className="font-bold">{item.title}</h3>
                <p className="mt-1 text-sm text-slate-500">{item.description || "Practice kit access"}</p>
              </div>
              <div className="flex flex-wrap items-center gap-3 text-sm text-slate-600">
                <span>Purchased: {item.startDate}</span>
                <span>Expires: {item.expiryDate}</span>
                <span className={`rounded-full px-3 py-1 text-xs font-bold ${item.accessStatus === "active" ? "bg-green-100 text-green-700" : "bg-slate-100 text-slate-500"}`}>
                  {item.accessStatus}
                </span>
              </div>
            </div>
          ))}
        </div>
      ) : (
        <EmptyState title="No practice kit purchases" text="Purchased practice kits will appear here with purchase date, expiry date, invoice, and start practice action." />
      )}
    </section>

    <section>
      <h2 className="mb-4 flex items-center gap-2 text-lg font-bold">
        <Trophy className="h-5 w-5" /> Competition Registrations
      </h2>
      <EmptyState title="No competition registrations" text="Competition registrations will appear here after a student applies or pays for a competition." />
    </section>
  </div>
);

const LeaderboardView = () => {
  const [leaderboard, setLeaderboard] = useState<CompetitionLeaderboardResponse>({ competitions: [], participants: [] });
  const [selectedCompetition, setSelectedCompetition] = useState("");
  const selected = leaderboard.competitions.find((competition) => competition.id === selectedCompetition);

  useEffect(() => {
    let active = true;
    getCompetitionLeaderboard(selectedCompetition)
      .then((data) => {
        if (active) setLeaderboard(data);
      })
      .catch(() => {
        if (active) setLeaderboard({ competitions: [], participants: [] });
      });
    return () => {
      active = false;
    };
  }, [selectedCompetition]);

  const participants = leaderboard.participants.map((participant) => ({
    rank: participant.rankPosition,
    name: participant.name,
    marks: participant.marks,
    totalMarks: participant.totalMarks,
    accuracy: participant.accuracy,
    time: formatDuration(participant.completionTimeSeconds),
  }));

  return (
    <div className="space-y-6">
      <div className="flex items-start gap-3">
        <Trophy className="mt-1 h-7 w-7 text-yellow-500" />
        <div>
          <h1 className="text-2xl font-bold tracking-normal">Competition Leaderboard</h1>
          <p className="mt-1 text-sm text-slate-500">View rankings and scores from all published competitions</p>
        </div>
      </div>

      <section className="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
        <h2 className="font-bold">Filters</h2>
        <div className="mt-12 grid gap-3 md:grid-cols-[1fr_200px_220px]">
          <select
            className="h-10 rounded-md border border-slate-200 bg-white px-3 text-sm text-slate-500"
            value={selectedCompetition}
            onChange={(event) => setSelectedCompetition(event.target.value)}
          >
            <option value="">Select a competition...</option>
            {leaderboard.competitions.map((competition) => (
              <option key={competition.id} value={competition.id}>{competition.title}</option>
            ))}
          </select>
          <select className="h-10 rounded-md border border-slate-200 bg-white px-3 text-sm text-slate-400" value={selected?.categoryName || ""} disabled>
            <option value="">{selected?.categoryName || "All Categories"}</option>
          </select>
          <select className="h-10 rounded-md border border-slate-200 bg-white px-3 text-sm text-slate-400" value={selected?.subcategoryName || ""} disabled>
            <option value="">{selected?.subcategoryName || "All Subcategories"}</option>
          </select>
        </div>
      </section>

      <section className="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
        {!selectedCompetition ? (
          <div className="grid min-h-64 place-items-center text-center">
            <div>
              <Search className="mx-auto h-11 w-11 text-slate-300" />
              <p className="mt-5 text-sm text-slate-500">Select a competition above to view its leaderboard</p>
            </div>
          </div>
        ) : (
          <LeaderboardTable participants={participants} />
        )}
      </section>
    </div>
  );
};

const formatDuration = (seconds: number) => {
  const minutes = Math.floor(seconds / 60);
  const remaining = Math.max(0, seconds % 60);
  return `${minutes}m ${String(remaining).padStart(2, "0")}s`;
};

const formatCompetitionDate = (value?: string | null) => {
  if (!value) return "Date not set";
  const date = new Date(value.replace(" ", "T"));
  return Number.isNaN(date.getTime()) ? value : date.toLocaleDateString("en-IN", { day: "numeric", month: "short", year: "numeric" });
};

const formatCompetitionTime = (start?: string | null, end?: string | null) => {
  const clean = (value?: string | null) => {
    if (!value) return "";
    const time = value.includes(" ") ? value.split(" ")[1] : value;
    return time.slice(0, 5);
  };
  const startText = clean(start);
  const endText = clean(end);
  return startText && endText ? `${startText} - ${endText}` : startText || "Time not set";
};

const getInitials = (name?: string | null) =>
  (name || "Competition Student")
    .split(/\s+/)
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part[0]?.toUpperCase())
    .join("") || "CS";

const formatProfileDate = (value?: string | null) => {
  if (!value) return "-";
  const date = new Date(`${value}T00:00:00`);
  if (Number.isNaN(date.getTime())) return value;
  return date.toLocaleDateString("en-IN");
};

const ProfileView = ({ profile }: { profile: CompetitionDashboardData["profile"] }) => (
  <div className="space-y-6">
    <section className="rounded-lg border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
      <div className="flex flex-col gap-5 sm:flex-row sm:items-center">
        <div className="grid h-20 w-20 shrink-0 place-items-center rounded-full bg-slate-100 text-xl font-semibold text-slate-800">
          {getInitials(profile.name)}
        </div>
        <div>
          <h2 className="text-2xl font-bold tracking-normal text-slate-950">{profile.name}</h2>
          <p className="mt-1 text-slate-500">{profile.email}</p>
        </div>
      </div>

      <div className="mt-7 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        <ProfileField label="School" value={profile.school} />
        <ProfileField label="Gender" value={profile.gender} />
        <ProfileField label="Date of Birth" value={formatProfileDate(profile.dateOfBirth)} />
        <ProfileField label="Phone" value={profile.phone} />
        <ProfileField label="Simple Abacus Category" value={profile.maatsCategory} highlight />
        <ProfileField label="Simple Abacus Subcategory" value={profile.maatsSubcategory} highlight />
        <ProfileField label="Calculus Grade" value={profile.calculusGrade} />
      </div>

      <div className="mt-8">
        <h3 className="border-b border-slate-200 pb-3 text-lg font-bold">Address</h3>
        <div className="mt-4 grid gap-4 md:grid-cols-2">
          <ProfileField label="Street Address" value={profile.streetAddress} />
          <ProfileField label="City, State" value={[profile.city, profile.state].filter(Boolean).join(", ")} />
          <ProfileField label="Pin Code" value={profile.pinCode} />
          <ProfileField label="Country" value={profile.country} />
        </div>
      </div>
    </section>

    <section className="rounded-lg border border-slate-200 bg-white shadow-sm">
      <div className="flex items-center gap-3 border-b border-slate-200 px-5 py-5 sm:px-7">
        <Lock className="h-5 w-5 text-red-600" />
        <h2 className="font-bold">Security Settings</h2>
      </div>
      <div className="p-5 sm:p-7">
        <h3 className="text-lg font-bold">Change Password</h3>
        <div className="mt-5 max-w-xl space-y-4">
          <PasswordField label="Current Password" />
          <PasswordField label="New Password" />
          <PasswordField label="Confirm New Password" />
          <Button className="bg-red-600 hover:bg-red-700">
            <Lock className="mr-2 h-4 w-4" /> Change Password
          </Button>
        </div>
      </div>
    </section>
  </div>
);

const ProfileField = ({ label, value, highlight = false }: { label: string; value?: string | null; highlight?: boolean }) => (
  <div className={`rounded-md p-4 ${highlight ? "bg-cyan-50" : "bg-slate-50"}`}>
    <p className="text-sm font-semibold text-slate-500">{label}</p>
    <p className="mt-2 whitespace-pre-line font-semibold text-slate-950">{value || "-"}</p>
  </div>
);

const PasswordField = ({ label }: { label: string }) => (
  <label className="block">
    <span className="text-sm font-semibold text-slate-950">{label}</span>
    <div className="relative mt-2">
      <input type="password" className="h-10 w-full rounded-md border border-slate-200 bg-white px-3 pr-10 shadow-sm outline-none focus:border-blue-400" />
      <Eye className="absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" />
    </div>
  </label>
);

type LeaderboardParticipant = {
  rank: number;
  name: string;
  marks: number;
  totalMarks: number;
  accuracy: number;
  time: string;
};

const LeaderboardTable = ({ participants }: { participants: LeaderboardParticipant[] }) => (
  <div className="overflow-x-auto">
    <table className="w-full min-w-[720px] text-left text-sm">
      <thead className="border-b border-slate-200 text-xs uppercase text-slate-500">
        <tr>
          <th className="py-3">Rank</th>
          <th className="py-3">Participant</th>
          <th className="py-3">Marks</th>
          <th className="py-3">Accuracy</th>
          <th className="py-3">Completion Time</th>
        </tr>
      </thead>
      <tbody className="divide-y divide-slate-100">
        {participants.map((participant) => (
          <tr key={participant.rank}>
            <td className="py-3 font-bold">#{participant.rank}</td>
            <td className="py-3">{participant.name}</td>
            <td className="py-3 font-semibold">{participant.marks}/{participant.totalMarks}</td>
            <td className="py-3">{participant.accuracy}%</td>
            <td className="py-3">{participant.time}</td>
          </tr>
        ))}
        {participants.length === 0 && (
          <tr>
            <td className="py-6 text-center text-slate-500" colSpan={5}>
              No participant marks are published for this competition yet.
            </td>
          </tr>
        )}
      </tbody>
    </table>
  </div>
);

const PurchaseStat = ({
  icon: Icon,
  label,
  value,
  tone,
}: {
  icon: typeof BookOpen;
  label: string;
  value: string;
  tone: "blue" | "purple" | "green" | "red";
}) => {
  const styles = {
    blue: "text-blue-600",
    purple: "text-purple-600",
    green: "text-green-600",
    red: "text-red-600",
  };
  return (
    <div className="flex min-h-32 items-center justify-between rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
      <div>
        <p className="text-sm text-slate-500">{label}</p>
        <p className={`mt-3 text-2xl font-bold ${styles[tone]}`}>{value}</p>
      </div>
      <Icon className={`h-8 w-8 ${styles[tone]}`} />
    </div>
  );
};

const EmptyState = ({ title, text }: { title: string; text: string }) => (
  <div className="rounded-lg border border-dashed border-slate-300 bg-white p-8 text-center">
    <p className="font-bold text-slate-900">{title}</p>
    <p className="mt-2 text-sm text-slate-500">{text}</p>
  </div>
);

const Placeholder = ({ title, text, icon }: { title: string; text: string; icon?: ReactNode }) => (
  <div className="rounded-lg border border-slate-200 bg-white p-8 shadow-sm">
    <div className="mb-4 text-[#0b86b4]">{icon || <Trophy className="h-8 w-8" />}</div>
    <h2 className="text-xl font-bold">{title}</h2>
    <p className="mt-2 text-slate-500">{text}</p>
  </div>
);

export default CompetitionDashboard;
