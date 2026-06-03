import { useEffect, useMemo, useState } from "react";
import { Link, useNavigate, useParams, useSearchParams } from "react-router-dom";
import {
  ArrowLeft,
  BarChart3,
  BookOpen,
  CalendarDays,
  CheckCircle2,
  ChevronLeft,
  ChevronRight,
  Clock,
  Eye,
  History,
  Play,
  RotateCcw,
  Save,
  Search,
  Send,
  Volume2,
} from "lucide-react";
import { toast } from "sonner";
import { Button } from "@/components/ui/button";
import { Card } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Skeleton } from "@/components/ui/skeleton";
import StudentLayout from "@/layouts/StudentLayout";
import { useAuth } from "@/context/AuthContext";
import { fetchStudentDashboard, StudentDashboardData } from "@/services/studentApi";
import {
  fetchWorksheetDashboard,
  fetchWorksheetPractices,
  fetchWorksheetQuestions,
  submitWorksheetPractice,
  WorksheetLevel,
  WorksheetPractice,
  WorksheetQuestion,
  WorksheetTopic,
} from "@/services/worksheetSubApi";

const PAGE_SIZE = 16;
const PRACTICE_LIMIT = 10;
const TOKEN_KEY = "abacus_auth_token";

type StudentSubscription = NonNullable<StudentDashboardData["subscriptions"]>[number];

const formatTime = (seconds: number) => {
  const mins = Math.floor(seconds / 60).toString().padStart(2, "0");
  const secs = (seconds % 60).toString().padStart(2, "0");
  return `${mins}:${secs}`;
};

const Header = ({ title, subtitle }: { title: string; subtitle?: string }) => {
  const { user } = useAuth();
  return (
    <div className="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
      <div>
        <p className="text-sm font-semibold text-[#5b21b6]">Welcome, {user?.name || "Student"} !</p>
        <h1 className="mt-1 text-xl font-bold text-slate-900 md:text-2xl">{title}</h1>
        {subtitle ? <p className="mt-1 text-sm text-slate-500">{subtitle}</p> : null}
      </div>
      <div className="flex w-fit items-center gap-3 rounded-full bg-slate-100 px-3 py-2">
        <div className="flex h-9 w-9 items-center justify-center rounded-full border border-slate-200 bg-white font-bold text-[#5b21b6]">
          {(user?.name || "S").slice(0, 1).toUpperCase()}
        </div>
        <span className="text-sm font-semibold text-slate-700">{user?.name || "Student"}</span>
      </div>
    </div>
  );
};

const Breadcrumbs = ({ items }: { items: { label: string; to?: string }[] }) => (
  <div className="flex flex-wrap items-center gap-2 text-xs font-semibold text-slate-500">
    {items.map((item, index) => (
      <span key={item.label} className="flex items-center gap-2">
        {item.to ? (
          <Link to={item.to} className="text-[#5b21b6] hover:text-[#ff6500]">
            {item.label}
          </Link>
        ) : (
          <span>{item.label}</span>
        )}
        {index < items.length - 1 ? <ChevronRight className="h-3 w-3" /> : null}
      </span>
    ))}
  </div>
);

const LevelBox = ({ level, onBack }: { level?: WorksheetLevel; onBack?: () => void }) => (
  <Card className="mx-auto max-w-5xl rounded-xl border-0 bg-white p-4 shadow-md">
    <div className="flex items-center justify-between rounded-lg bg-[#551896] px-4 py-3 text-white">
      <h2 className="text-sm font-semibold sm:text-base">{level?.level_name || "Worksheet Subscription"}</h2>
      {onBack ? (
        <Button type="button" size="icon" variant="secondary" className="h-8 w-10 rounded-md bg-white text-[#551896] hover:bg-white/90" onClick={onBack}>
          <ArrowLeft className="h-4 w-4" />
        </Button>
      ) : null}
    </div>
  </Card>
);

const LoadingGrid = () => (
  <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
    {Array.from({ length: 8 }, (_, index) => (
      <Skeleton key={index} className="h-36 rounded-xl bg-white" />
    ))}
  </div>
);

const formatShortDate = (value?: string | null) => {
  if (!value) return "-";
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return value;
  return date.toLocaleDateString("en-GB").replace(/\//g, "-");
};

const normalizeCourseTitle = (subscription: StudentSubscription) => {
  const plan = subscription.planName || "Worksheet Subscription";
  if (/vedic/i.test(plan)) return "Vedic Maths";
  if (/abacus/i.test(plan)) return "Abacus Senior";
  return plan.replace(/\s*worksheet\s*subscription\s*/gi, " ").replace(/\s+/g, " ").trim();
};

const sortByLevelName = <T extends { levelName?: string | null }>(items: T[]) =>
  [...items].sort((a, b) => {
    const aLevel = Number((a.levelName || "").match(/\d+/)?.[0] || 0);
    const bLevel = Number((b.levelName || "").match(/\d+/)?.[0] || 0);
    return aLevel - bLevel;
  });

const groupSubscriptions = (subscriptions: StudentSubscription[]) => {
  const groups = new Map<string, StudentSubscription[]>();
  subscriptions.forEach((subscription) => {
    const title = normalizeCourseTitle(subscription);
    groups.set(title, [...(groups.get(title) || []), subscription]);
  });
  return Array.from(groups.entries()).map(([title, items]) => ({ title, items: sortByLevelName(items) }));
};

const WorksheetCourseGroup = ({
  title,
  items,
  currentLevel,
  disabled = false,
}: {
  title: string;
  items: StudentSubscription[];
  currentLevel?: WorksheetLevel;
  disabled?: boolean;
}) => (
  <div className="rounded-xl bg-slate-100 p-4">
    <div className="flex items-start gap-3">
      <div className="flex h-11 w-11 shrink-0 items-center justify-center rounded-md bg-[#551896] text-white">
        <BookOpen className="h-5 w-5" />
      </div>
      <div>
        <h3 className="text-sm font-bold text-[#551896]">{title}</h3>
        <p className="text-xs text-slate-500">Learn and practice the concepts in this course</p>
      </div>
    </div>
    <p className="mt-4 text-xs font-semibold text-slate-700">Course Levels</p>
    <div className="mt-3 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
      {items.map((item) => {
        const levelName = item.levelName || currentLevel?.level_name || "Level";
        const card = (
          <div
            className={`min-h-24 rounded-md border bg-white p-4 text-center transition ${
              disabled
                ? "border-slate-300 opacity-75"
                : "border-[#ff6500] hover:-translate-y-0.5 hover:shadow-md"
            }`}
          >
            <h4 className="text-lg font-bold text-slate-950">{levelName}</h4>
            <div className="mt-2 flex items-center justify-center gap-1 text-[11px] font-semibold text-slate-600">
              <CalendarDays className="h-3 w-3" />
              <span>Exp Date: {formatShortDate(item.expiryDate)}</span>
            </div>
            <p className="mt-3 text-[11px] font-semibold text-slate-400">0% completed</p>
          </div>
        );

        if (disabled) return <div key={item.id}>{card}</div>;
        return (
          <Link key={item.id} to="/student/worksheets?view=topics" aria-label={`Open ${levelName} worksheet topics`}>
            {card}
          </Link>
        );
      })}
    </div>
  </div>
);

const WorksheetOverviewPage = ({
  level,
  dashboard,
}: {
  level?: WorksheetLevel;
  dashboard?: StudentDashboardData | null;
}) => {
  const subscriptions = dashboard?.subscriptions || [];
  const activeSubscriptions = subscriptions.filter((item) => item.status === "active" && item.paymentStatus === "paid");
  const expiredSubscriptions = subscriptions.filter((item) => item.status === "expired" || item.status === "cancelled");
  const fallbackActive: StudentSubscription[] =
    activeSubscriptions.length || !level
      ? []
      : [{
          id: level.id,
          planName: "Abacus Worksheet Subscription",
          levelName: level.level_name,
          amount: 0,
          currency: "INR",
          startDate: null,
          expiryDate: dashboard?.expiryDate || null,
          status: "active",
          paymentStatus: "paid",
        }];
  const activeGroups = groupSubscriptions(activeSubscriptions.length ? activeSubscriptions : fallbackActive);
  const expiredGroups = groupSubscriptions(expiredSubscriptions);

  return (
    <div className="mx-auto max-w-6xl space-y-6">
      <Breadcrumbs items={[{ label: "Dashboard", to: "/student/dashboard" }, { label: "Worksheet Subscription" }]} />

      <section className="rounded-xl bg-white p-5 shadow-md">
        <div className="mb-4 flex items-center gap-2">
          <BookOpen className="h-5 w-5 text-[#551896]" />
          <h2 className="text-lg font-bold text-[#551896]">Active Worksheet Subscriptions</h2>
        </div>
        {activeGroups.length ? (
          <div className="space-y-4">
            {activeGroups.map((group) => (
              <WorksheetCourseGroup key={group.title} title={group.title} items={group.items} currentLevel={level} />
            ))}
          </div>
        ) : (
          <div className="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-8 text-center">
            <p className="font-semibold text-slate-800">No active worksheet subscription found.</p>
            <Button asChild className="mt-4 bg-[#551896] hover:bg-[#421173]">
              <Link to="/student/shop">Go to Shop</Link>
            </Button>
          </div>
        )}
      </section>

      {expiredGroups.length ? (
        <section className="rounded-xl bg-white p-5 shadow-md">
          <div className="mb-4 flex items-center gap-2">
            <BookOpen className="h-5 w-5 text-[#551896]" />
            <h2 className="text-lg font-bold text-[#551896]">Expired Worksheet Subscriptions</h2>
          </div>
          <div className="space-y-4">
            {expiredGroups.map((group) => (
              <WorksheetCourseGroup key={group.title} title={group.title} items={group.items} currentLevel={level} disabled />
            ))}
          </div>
        </section>
      ) : null}
    </div>
  );
};

const StudentWorksheets = () => {
  const navigate = useNavigate();
  const { topicId, view } = useParams<{ topicId?: string; view?: string }>();
  const [searchParams] = useSearchParams();
  const [level, setLevel] = useState<WorksheetLevel>();
  const [topics, setTopics] = useState<WorksheetTopic[]>([]);
  const [dashboard, setDashboard] = useState<StudentDashboardData | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");

  useEffect(() => {
    let alive = true;
    const token = window.localStorage.getItem(TOKEN_KEY);
    if (token) {
      fetchStudentDashboard(token)
        .then((payload) => {
          if (alive) setDashboard(payload);
        })
        .catch(() => {
          if (alive) setDashboard(null);
        });
    }

    fetchWorksheetDashboard()
      .then((payload) => {
        if (!alive) return;
        setLevel(payload.level);
        setTopics(payload.topics);
      })
      .catch((err) => {
        if (!alive) return;
        setError(err instanceof Error ? err.message : "Unable to load worksheet subscription.");
        setLevel(undefined);
        setTopics([]);
      })
      .finally(() => alive && setLoading(false));
    return () => {
      alive = false;
    };
  }, []);

  const selectedTopic = topics.find((topic) => topic.id === topicId);

  const content = () => {
    if (loading) {
      return (
        <div className="space-y-6">
          <LoadingGrid />
        </div>
      );
    }

    if (error) {
      if (dashboard?.subscriptions?.length) {
        return <WorksheetOverviewPage level={level} dashboard={dashboard} />;
      }

      return (
        <div className="mx-auto max-w-3xl">
          <Card className="rounded-xl border border-amber-200 bg-white p-8 text-center shadow-sm">
            <h2 className="text-xl font-bold text-slate-900">Worksheet Subscription Required</h2>
            <p className="mt-3 text-sm text-slate-600">
              {error.includes("expired") || error.includes("renew")
                ? error
                : "Please purchase an active worksheet subscription to access worksheet topics and practice questions."}
            </p>
            <Button className="mt-5 bg-[#551896] hover:bg-[#421173]" onClick={() => navigate("/student/shop")}>
              Go to Shop
            </Button>
          </Card>
        </div>
      );
    }

    if (topicId && !selectedTopic) {
      return (
        <Card className="rounded-xl border border-dashed border-slate-300 bg-white p-8 text-center shadow-sm">
          <p className="font-semibold text-slate-800">Topic not found</p>
          <Button className="mt-4 bg-[#551896] hover:bg-[#421173]" onClick={() => navigate("/student/worksheets")}>
            Back to Worksheet Subscription
          </Button>
        </Card>
      );
    }

    if (selectedTopic && view === "questions") return <QuestionsPage level={level} topic={selectedTopic} />;
    if (selectedTopic && view === "practice") return <PracticePage level={level} topic={selectedTopic} />;
    if (selectedTopic && view === "visualization") return <VisualizationPage level={level} topic={selectedTopic} />;
    if (selectedTopic && view === "practices") return <PracticesPage level={level} topic={selectedTopic} />;

    if (searchParams.get("view") !== "topics") {
      return <WorksheetOverviewPage level={level} dashboard={dashboard} />;
    }

    return <TopicListPage level={level} topics={topics} />;
  };

  return (
    <StudentLayout header={<Header title="Worksheet Subscription" subtitle="Practice, visualize and review your abacus worksheet progress." />}>
      {content()}
    </StudentLayout>
  );
};

const TopicListPage = ({ level, topics }: { level?: WorksheetLevel; topics: WorksheetTopic[] }) => {
  const [search, setSearch] = useState("");
  const filtered = useMemo(
    () => topics.filter((topic) => topic.topic_name.toLowerCase().includes(search.toLowerCase())),
    [search, topics],
  );

  return (
    <div className="mx-auto max-w-6xl space-y-6">
      <Breadcrumbs items={[{ label: "Dashboard", to: "/student/dashboard" }, { label: "Worksheet Subscription" }]} />
      <LevelBox level={level} />

      <div className="flex flex-col gap-3 rounded-xl bg-white p-4 shadow-sm md:flex-row md:items-center md:justify-between">
        <div>
          <h2 className="text-base font-bold text-slate-900">Worksheet Topics</h2>
          <p className="text-sm text-slate-500">Select a topic to view questions, practice or visualize steps.</p>
        </div>
        <div className="relative w-full md:w-80">
          <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
          <Input value={search} onChange={(event) => setSearch(event.target.value)} placeholder="Search topics" className="pl-9" />
        </div>
      </div>

      <div className="space-y-4">
        {filtered.length === 0 && (
          <Card className="rounded-xl border border-dashed border-slate-300 bg-white p-8 text-center shadow-sm">
            <p className="font-semibold text-slate-800">No worksheet topics are available for this subscription yet.</p>
          </Card>
        )}
        {filtered.map((topic, index) => (
          <Card key={topic.id} className="group rounded-xl border border-slate-100 bg-white p-4 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-lg">
            <div className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
              <div className="min-w-0">
                <h3 className="text-sm font-bold text-slate-950 sm:text-base">
                  {index + 1}. {topic.topic_name}
                </h3>
                <p className="mt-1 text-xs font-medium text-slate-500">{topic.total_questions} questions available</p>
              </div>
              <div className="grid grid-cols-2 gap-2 sm:flex sm:flex-wrap lg:justify-end">
                <Button asChild variant="outline" className="h-9 border-slate-800 bg-white px-3 text-xs text-slate-950 hover:bg-slate-50">
                  <Link to={`/student/worksheets/${topic.id}/questions`}>View Questions [{topic.total_questions}]</Link>
                </Button>
                <Button asChild className="h-9 bg-[#ff6500] px-3 text-xs text-white shadow-sm transition hover:scale-[1.02] hover:bg-[#e95c00]">
                  <Link to={`/student/worksheets/${topic.id}/practice`}>Practice Now</Link>
                </Button>
                <Button asChild className="h-9 bg-[#11894e] px-3 text-xs text-white shadow-sm transition hover:scale-[1.02] hover:bg-[#0e7442]">
                  <Link to={`/student/worksheets/${topic.id}/visualization`}>Visualization</Link>
                </Button>
                <Button asChild variant="outline" className="h-9 border-slate-800 bg-white px-3 text-xs text-slate-950 hover:bg-slate-50">
                  <Link to={`/student/worksheets/${topic.id}/practices`}>View Practices</Link>
                </Button>
              </div>
            </div>
          </Card>
        ))}
      </div>
    </div>
  );
};

const QuestionsPage = ({ level, topic }: { level?: WorksheetLevel; topic: WorksheetTopic }) => {
  const navigate = useNavigate();
  const [questions, setQuestions] = useState<WorksheetQuestion[]>([]);
  const [loading, setLoading] = useState(true);
  const [page, setPage] = useState(1);

  useEffect(() => {
    setLoading(true);
    fetchWorksheetQuestions(topic).then(setQuestions).finally(() => setLoading(false));
  }, [topic]);

  const totalPages = Math.max(1, Math.ceil(questions.length / PAGE_SIZE));
  const visible = questions.slice((page - 1) * PAGE_SIZE, page * PAGE_SIZE);

  return (
    <div className="mx-auto max-w-6xl space-y-6">
      <Breadcrumbs items={[{ label: "Worksheet Subscription", to: "/student/worksheets" }, { label: "View Questions" }]} />
      <LevelBox level={level} onBack={() => navigate("/student/worksheets")} />
      <h2 className="text-lg font-bold text-slate-900">{topic.topic_name}</h2>

      {loading ? <LoadingGrid /> : (
        <>
          <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            {visible.map((question, index) => (
              <Card key={question.id} className="flex min-h-36 flex-col rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                <span className="mb-4 inline-flex h-9 w-9 items-center justify-center rounded-lg bg-[#5b21b6] text-xs font-bold text-white">
                  Q{(page - 1) * PAGE_SIZE + index + 1}
                </span>
                <p className="flex-1 text-lg font-bold text-slate-900">{question.question}</p>
                <div className="mt-4 rounded-lg bg-slate-50 px-3 py-2 text-sm font-semibold text-[#551896]">Answer: {question.answer}</div>
              </Card>
            ))}
          </div>
          <div className="flex items-center justify-center gap-3">
            <Button variant="outline" disabled={page === 1} onClick={() => setPage((prev) => prev - 1)}><ChevronLeft className="mr-1 h-4 w-4" />Previous</Button>
            <span className="text-sm font-semibold text-slate-600">Page {page} of {totalPages}</span>
            <Button variant="outline" disabled={page === totalPages} onClick={() => setPage((prev) => prev + 1)}>Next<ChevronRight className="ml-1 h-4 w-4" /></Button>
          </div>
        </>
      )}
    </div>
  );
};

const PracticePage = ({ level, topic }: { level?: WorksheetLevel; topic: WorksheetTopic }) => {
  const navigate = useNavigate();
  const [questions, setQuestions] = useState<WorksheetQuestion[]>([]);
  const [index, setIndex] = useState(0);
  const [answer, setAnswer] = useState("");
  const [answers, setAnswers] = useState<Record<string, string>>({});
  const [seconds, setSeconds] = useState(0);
  const [complete, setComplete] = useState(false);
  const [saving, setSaving] = useState(false);

  useEffect(() => {
    fetchWorksheetQuestions(topic).then((items) => setQuestions(items.slice(0, PRACTICE_LIMIT)));
  }, [topic]);

  useEffect(() => {
    if (complete) return undefined;
    const timer = window.setInterval(() => setSeconds((prev) => prev + 1), 1000);
    return () => window.clearInterval(timer);
  }, [complete]);

  const current = questions[index];
  const correct = questions.reduce((total, question) => total + (answers[question.id]?.trim() === question.answer ? 1 : 0), 0);
  const accuracy = questions.length ? Math.round((correct / questions.length) * 100) : 0;

  const goNext = () => {
    if (!current) return;
    const nextAnswers = { ...answers, [current.id]: answer.trim() };
    setAnswers(nextAnswers);
    setAnswer("");
    if (index >= questions.length - 1) {
      setComplete(true);
      return;
    }
    setIndex((prev) => prev + 1);
  };

  const save = async () => {
    setSaving(true);
    const finalCorrect = questions.reduce((total, question) => total + (answers[question.id]?.trim() === question.answer ? 1 : 0), 0);
    const finalAccuracy = questions.length ? Math.round((finalCorrect / questions.length) * 100) : 0;
    await submitWorksheetPractice({
      topicId: topic.id,
      score: finalCorrect,
      accuracy: finalAccuracy,
      totalQuestions: questions.length,
      correctAnswers: finalCorrect,
      timeTaken: seconds,
    });
    toast.success("Practice result saved");
    navigate(`/student/worksheets/${topic.id}/practices`);
  };

  return (
    <div className="mx-auto max-w-5xl space-y-6">
      <Breadcrumbs items={[{ label: "Worksheet Subscription", to: "/student/worksheets" }, { label: "Practice Now" }]} />
      <LevelBox level={level} onBack={() => navigate("/student/worksheets")} />

      <Card className="rounded-xl border-0 bg-white p-5 shadow-md">
        <div className="flex flex-col gap-3 border-b border-slate-100 pb-4 md:flex-row md:items-center md:justify-between">
          <div>
            <h2 className="text-lg font-bold text-slate-900">{topic.topic_name}</h2>
            <p className="text-sm text-slate-500">One question at a time with automatic scoring.</p>
          </div>
          <div className="flex gap-2">
            <span className="inline-flex items-center gap-2 rounded-full bg-[#f7f2ff] px-3 py-2 text-sm font-bold text-[#551896]"><Clock className="h-4 w-4" />{formatTime(seconds)}</span>
            <span className="inline-flex items-center gap-2 rounded-full bg-orange-50 px-3 py-2 text-sm font-bold text-[#ff6500]"><BarChart3 className="h-4 w-4" />{accuracy}%</span>
          </div>
        </div>

        {!complete ? (
          <div className="space-y-6 pt-6">
            <div className="rounded-xl bg-slate-50 p-6 text-center">
              <p className="text-xs font-bold uppercase text-slate-500">Question {Math.min(index + 1, questions.length)} of {questions.length || PRACTICE_LIMIT}</p>
              <p className="mt-4 text-4xl font-bold text-[#551896]">{current?.question || "Loading..."}</p>
            </div>
            <Input
              inputMode="numeric"
              value={answer}
              placeholder="Enter answer"
              onChange={(event) => setAnswer(event.target.value.replace(/[^0-9-]/g, ""))}
              onKeyDown={(event) => event.key === "Enter" && goNext()}
              className="h-12 text-lg font-semibold"
            />
            <div className="flex items-center justify-between">
              <Button variant="outline" disabled={index === 0} onClick={() => {
                setIndex((prev) => Math.max(0, prev - 1));
                setAnswer(answers[questions[index - 1]?.id] || "");
              }}>Previous</Button>
              <Button className="bg-[#ff6500] hover:bg-[#e95c00]" disabled={!current} onClick={goNext}>
                {index === questions.length - 1 ? "Finish Practice" : "Next Question"}
              </Button>
            </div>
          </div>
        ) : (
          <div className="space-y-5 pt-8 text-center">
            <CheckCircle2 className="mx-auto h-14 w-14 text-emerald-600" />
            <h3 className="text-2xl font-bold text-slate-900">Practice Complete</h3>
            <div className="mx-auto grid max-w-xl gap-3 sm:grid-cols-3">
              <div className="rounded-xl bg-slate-50 p-4"><p className="text-xs text-slate-500">Score</p><p className="text-xl font-bold">{correct}/{questions.length}</p></div>
              <div className="rounded-xl bg-slate-50 p-4"><p className="text-xs text-slate-500">Accuracy</p><p className="text-xl font-bold">{accuracy}%</p></div>
              <div className="rounded-xl bg-slate-50 p-4"><p className="text-xs text-slate-500">Time</p><p className="text-xl font-bold">{formatTime(seconds)}</p></div>
            </div>
            <Button disabled={saving} className="bg-[#11894e] hover:bg-[#0e7442]" onClick={save}>
              {saving ? "Saving..." : "Save Result"}
            </Button>
          </div>
        )}
      </Card>
    </div>
  );
};

const VisualizationPage = ({ level, topic }: { level?: WorksheetLevel; topic: WorksheetTopic }) => {
  const navigate = useNavigate();
  const [questions, setQuestions] = useState<WorksheetQuestion[]>([]);
  const [index, setIndex] = useState(0);
  const [answer, setAnswer] = useState("");
  const [answers, setAnswers] = useState<Record<string, string>>({});
  const [seconds, setSeconds] = useState(0);
  const [submitting, setSubmitting] = useState(false);
  const [voices, setVoices] = useState<SpeechSynthesisVoice[]>([]);
  const [speaking, setSpeaking] = useState(false);
  const current = questions[index];

  useEffect(() => {
    fetchWorksheetQuestions(topic).then((items) => setQuestions(items.slice(0, 40)));
  }, [topic]);

  useEffect(() => {
    const timer = window.setInterval(() => setSeconds((prev) => prev + 1), 1000);
    return () => window.clearInterval(timer);
  }, []);

  useEffect(() => {
    if (!("speechSynthesis" in window)) {
      return undefined;
    }

    const loadVoices = () => {
      setVoices(window.speechSynthesis.getVoices());
    };

    loadVoices();
    window.speechSynthesis.addEventListener("voiceschanged", loadVoices);

    return () => {
      window.speechSynthesis.cancel();
      window.speechSynthesis.removeEventListener("voiceschanged", loadVoices);
    };
  }, []);

  const speakQuestion = () => {
    if (!("speechSynthesis" in window) || typeof SpeechSynthesisUtterance === "undefined") {
      toast.error("Voice is not supported in this browser");
      return;
    }

    if (!current) {
      toast.error("Question is still loading");
      return;
    }

    if (window.speechSynthesis.speaking || window.speechSynthesis.pending) {
      window.speechSynthesis.cancel();
    }
    const spokenText = current.question
      .replace(/x/gi, " multiplied by ")
      .replace(/\+/g, " plus ")
      .replace(/-/g, " minus ")
      .replace(/\//g, " divided by ");
    const utterance = new SpeechSynthesisUtterance(`Question number ${index + 1}. ${spokenText}`);
    utterance.rate = 0.78;
    utterance.pitch = 1;
    const availableVoices = window.speechSynthesis.getVoices();
    const voiceChoices = availableVoices.length ? availableVoices : voices;
    const preferredVoice =
      voiceChoices.find((voice) => voice.default) ||
      voiceChoices.find((voice) => voice.lang.toLowerCase() === "en-in") ||
      voiceChoices.find((voice) => voice.lang.toLowerCase().startsWith("en")) ||
      null;

    if (preferredVoice) {
      utterance.voice = preferredVoice;
      utterance.lang = preferredVoice.lang;
    }

    utterance.onstart = () => setSpeaking(true);
    utterance.onend = () => setSpeaking(false);
    utterance.onerror = (event) => {
      setSpeaking(false);
      if (event.error === "canceled" || event.error === "interrupted") {
        return;
      }
      toast.error("Voice could not start. Turn on system/browser sound, then click Voice again.");
    };

    setSpeaking(true);
    if (window.speechSynthesis.paused) {
      window.speechSynthesis.resume();
    }
    window.speechSynthesis.speak(utterance);
  };

  const selectQuestion = (nextIndex: number) => {
    if (current) {
      setAnswers((prev) => ({ ...prev, [current.id]: answer.trim() }));
    }
    setIndex(nextIndex);
    setAnswer(answers[questions[nextIndex]?.id] || "");
  };

  const saveAndNext = () => {
    if (!current) return;
    const nextAnswers = { ...answers, [current.id]: answer.trim() };
    setAnswers(nextAnswers);
    if (index < questions.length - 1) {
      const nextIndex = index + 1;
      setIndex(nextIndex);
      setAnswer(nextAnswers[questions[nextIndex]?.id] || "");
      return;
    }
    setAnswer("");
    toast.success("Last answer saved");
  };

  const submitExam = async () => {
    if (!questions.length) return;
    setSubmitting(true);
    const finalAnswers = current ? { ...answers, [current.id]: answer.trim() } : answers;
    const correct = questions.reduce((total, question) => total + (finalAnswers[question.id]?.trim() === question.answer ? 1 : 0), 0);
    const accuracy = Math.round((correct / questions.length) * 100);
    await submitWorksheetPractice({
      topicId: topic.id,
      score: correct,
      accuracy,
      totalQuestions: questions.length,
      correctAnswers: correct,
      timeTaken: seconds,
    });
    toast.success(`Exam submitted: ${correct}/${questions.length}`);
    navigate(`/student/worksheets/${topic.id}/practices`);
  };

  const beadRows = current?.answer
    ? current.answer.replace(/\D/g, "").slice(-5).padStart(5, "0").split("").map((digit) => Number(digit))
    : [0, 0, 0, 0, 0];

  return (
    <div className="mx-auto max-w-6xl space-y-6">
      <Breadcrumbs items={[{ label: "Worksheet Subscription", to: "/student/worksheets" }, { label: "Visualization" }]} />
      <LevelBox level={level} onBack={() => navigate("/student/worksheets")} />

      <Card className="rounded-xl border-0 bg-white p-5 shadow-md">
        <div className="border-b border-slate-200 pb-4">
          <h2 className="text-lg font-bold text-[#30008a]">{topic.topic_name}</h2>
          <p className="mt-1 text-sm font-semibold text-slate-900">Timer : {seconds} seconds</p>
          <div className="mt-3 h-0.5 w-16 rounded-full bg-[#551896]" />
        </div>

        <div className="grid gap-6 pt-6 lg:grid-cols-[1fr_280px]">
          <div className="min-h-[420px] space-y-7">
            <div className="flex items-center justify-between rounded-lg bg-slate-50 px-4 py-3">
              <div className="flex items-center gap-3">
                <span className="h-9 rounded-l-md border-l-4 border-[#551896]" />
                <span className="text-sm font-bold text-slate-950">Question No : {index + 1}</span>
                <button
                  type="button"
                  aria-label="Replay question voice"
                  className="rounded-full p-1 text-slate-800 hover:bg-white hover:text-[#551896]"
                  onClick={speakQuestion}
                >
                  <RotateCcw className="h-4 w-4" />
                </button>
              </div>
              <Button type="button" size="sm" className="bg-[#551896] hover:bg-[#421173]" onClick={speakQuestion} disabled={!current || speaking}>
                <Volume2 className="mr-2 h-4 w-4" />
                {speaking ? "Speaking" : "Voice"}
              </Button>
            </div>

            <div>
              <p className="text-2xl font-bold text-slate-950">Answer</p>
              <p className="mt-7 text-sm font-medium text-slate-900">Your Answer:</p>
              <Input
                value={answer}
                inputMode="numeric"
                placeholder="Type your answer here..."
                onChange={(event) => setAnswer(event.target.value.replace(/[^0-9-]/g, ""))}
                onKeyDown={(event) => event.key === "Enter" && saveAndNext()}
                className="mt-2 h-10 border-[#551896] shadow-[0_0_0_2px_rgba(85,24,150,0.12)] focus-visible:ring-[#551896]"
              />
            </div>

            <div className="rounded-xl border border-[#551896]/10 bg-[#fbf8ff] p-4">
              <div className="mb-4 flex items-center justify-between">
                <span className="text-sm font-bold text-[#551896]">Abacus bead movement</span>
                <span className="text-xs font-semibold text-slate-500">Answer rods preview</span>
              </div>
              <div className="space-y-4">
                {beadRows.map((active, rod) => (
                  <div key={`${current?.id || "q"}-${rod}`} className="flex items-center gap-3">
                    <span className="w-8 text-xs font-semibold text-slate-500">{rod + 1}</span>
                    <div className="h-1 flex-1 rounded-full bg-[#551896]/25" />
                    <div className="flex min-w-44 gap-1.5">
                      {Array.from({ length: 10 }, (_, bead) => (
                        <span
                          key={bead}
                          className={`h-5 w-5 rounded-full border border-[#551896]/20 shadow-sm transition-all duration-500 ${
                            bead < active ? "translate-y-1 bg-[#ff6500]" : "-translate-y-1 bg-white"
                          }`}
                          style={{ transitionDelay: `${bead * 35}ms` }}
                        />
                      ))}
                    </div>
                  </div>
                ))}
              </div>
            </div>

            <div className="flex flex-wrap items-center gap-3">
              <Button
                type="button"
                variant="ghost"
                className="text-sm font-semibold text-slate-500 hover:text-[#551896]"
                disabled={index === 0}
                onClick={() => selectQuestion(Math.max(0, index - 1))}
              >
                <ChevronLeft className="mr-1 h-4 w-4" />
                Previous
              </Button>
              <Button type="button" className="bg-[#10c986] text-white hover:bg-[#0fb777]" onClick={saveAndNext}>
                <Save className="mr-2 h-4 w-4" />
                Save & Next
              </Button>
              <Button type="button" disabled={submitting} className="bg-[#f04b3f] text-white hover:bg-[#dc382d]" onClick={submitExam}>
                <Send className="mr-2 h-4 w-4" />
                {submitting ? "Submitting..." : "Submit Exam"}
              </Button>
            </div>
          </div>

          <aside className="rounded-xl bg-slate-50 p-5 shadow-[0_8px_24px_rgba(15,23,42,0.10)]">
            <div className="mb-4 flex items-center gap-2 text-sm font-medium text-slate-700">
              <BarChart3 className="h-4 w-4 text-[#551896]" />
              Question Navigator
            </div>
            <div className="grid grid-cols-5 gap-3">
              {questions.map((question, questionIndex) => {
                const isActive = questionIndex === index;
                const isAnswered = Boolean(answers[question.id]);
                return (
                  <button
                    key={question.id}
                    type="button"
                    className={`h-10 rounded-md text-sm font-bold text-white shadow-md transition hover:-translate-y-0.5 ${
                      isActive
                        ? "bg-[#3b087a] ring-2 ring-[#ff6500]"
                        : isAnswered
                          ? "bg-[#11894e]"
                          : "bg-[#551896] hover:bg-[#431275]"
                    }`}
                    onClick={() => selectQuestion(questionIndex)}
                  >
                    {questionIndex + 1}
                  </button>
                );
              })}
              {!questions.length ? Array.from({ length: 40 }, (_, placeholder) => (
                <span key={placeholder} className="h-10 animate-pulse rounded-md bg-[#551896]/30" />
              )) : null}
            </div>
          </aside>
        </div>
      </Card>
    </div>
  );
};

const PracticesPage = ({ level, topic }: { level?: WorksheetLevel; topic: WorksheetTopic }) => {
  const navigate = useNavigate();
  const [practices, setPractices] = useState<WorksheetPractice[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetchWorksheetPractices(topic.id).then(setPractices).finally(() => setLoading(false));
  }, [topic.id]);

  return (
    <div className="mx-auto max-w-6xl space-y-6">
      <Breadcrumbs items={[{ label: "Worksheet Subscription", to: "/student/worksheets" }, { label: "View Practices" }]} />
      <LevelBox level={level} onBack={() => navigate("/student/worksheets")} />
      <Card className="rounded-xl border-0 bg-white p-5 shadow-md">
        <div className="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
          <div>
            <h2 className="text-lg font-bold text-slate-900">{topic.topic_name}</h2>
            <p className="text-sm text-slate-500">Previous attempts and results.</p>
          </div>
          <Button asChild className="bg-[#ff6500] hover:bg-[#e95c00]"><Link to={`/student/worksheets/${topic.id}/practice`}><Play className="mr-2 h-4 w-4" />Practice Now</Link></Button>
        </div>
        <div className="mt-5 overflow-x-auto">
          {loading ? <Skeleton className="h-48 rounded-xl" /> : practices.length ? (
            <table className="w-full min-w-[720px] text-left text-sm">
              <thead className="bg-slate-50 text-xs uppercase text-slate-500">
                <tr>
                  <th className="px-4 py-3">Date</th>
                  <th className="px-4 py-3">Score</th>
                  <th className="px-4 py-3">Accuracy</th>
                  <th className="px-4 py-3">Time Taken</th>
                  <th className="px-4 py-3">Status</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100">
                {practices.map((practice) => (
                  <tr key={practice.id} className="hover:bg-slate-50">
                    <td className="px-4 py-4 font-semibold text-slate-800">{new Date(practice.created_at).toLocaleString()}</td>
                    <td className="px-4 py-4">{practice.correct_answers}/{practice.total_questions}</td>
                    <td className="px-4 py-4">{practice.accuracy}%</td>
                    <td className="px-4 py-4">{formatTime(Number(practice.time_taken))}</td>
                    <td className="px-4 py-4">
                      <span className="rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-700">{practice.status}</span>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          ) : (
            <div className="rounded-xl border border-dashed border-slate-300 p-10 text-center">
              <History className="mx-auto h-10 w-10 text-slate-400" />
              <p className="mt-3 font-semibold text-slate-800">No practice attempts yet</p>
              <p className="text-sm text-slate-500">Start a practice to save your first result.</p>
            </div>
          )}
        </div>
        <div className="mt-5 flex flex-wrap justify-end gap-2">
          <Button asChild variant="outline"><Link to={`/student/worksheets/${topic.id}/questions`}><Eye className="mr-2 h-4 w-4" />View Questions</Link></Button>
          <Button asChild className="bg-[#11894e] hover:bg-[#0e7442]"><Link to={`/student/worksheets/${topic.id}/visualization`}>Visualization</Link></Button>
        </div>
      </Card>
    </div>
  );
};

export default StudentWorksheets;
