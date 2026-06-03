import { useEffect, useMemo, useRef, useState } from "react";
import { useNavigate, useParams } from "react-router-dom";
import { CheckCircle2, Clock, Expand, Lock, RotateCcw, Send, Target, XCircle } from "lucide-react";
import StudentLayout from "@/layouts/StudentLayout";
import { Button } from "@/components/ui/button";
import { Progress } from "@/components/ui/progress";
import { AlertDialog, AlertDialogAction, AlertDialogCancel, AlertDialogContent, AlertDialogDescription, AlertDialogFooter, AlertDialogHeader, AlertDialogTitle } from "@/components/ui/alert-dialog";
import { useToast } from "@/hooks/use-toast";
import {
  getPracticeLevels,
  getPracticePaper,
  PracticeLevel,
  PracticePaper,
  PracticeQuestion,
  PracticeResult,
  savePracticeProgress,
  submitPracticePaper,
} from "@/services/practiceApi";

const TOKEN_KEY = "abacus_auth_token";

const formatTime = (seconds: number) => {
  const safe = Math.max(0, seconds);
  const m = Math.floor(safe / 60).toString().padStart(2, "0");
  const s = Math.floor(safe % 60).toString().padStart(2, "0");
  return `${m}:${s}`;
};

const StudentPractice = () => {
  const { paperId } = useParams();
  const navigate = useNavigate();
  const { toast } = useToast();
  const token = localStorage.getItem(TOKEN_KEY) || "";
  const [levels, setLevels] = useState<PracticeLevel[]>([]);
  const [summary, setSummary] = useState({ completedPapers: 0, attempts: 0, averageAccuracy: 0, bestScore: 0 });
  const [paper, setPaper] = useState<PracticePaper | null>(null);
  const [questions, setQuestions] = useState<PracticeQuestion[]>([]);
  const [answers, setAnswers] = useState<Record<string, string>>({});
  const [currentIndex, setCurrentIndex] = useState(0);
  const [remaining, setRemaining] = useState(180);
  const [startedAt, setStartedAt] = useState(Date.now());
  const [loading, setLoading] = useState(true);
  const [submitting, setSubmitting] = useState(false);
  const [confirmOpen, setConfirmOpen] = useState(false);
  const [result, setResult] = useState<PracticeResult | null>(null);
  const submittedRef = useRef(false);

  const answeredCount = Object.keys(answers).filter((id) => answers[id] !== "").length;
  const progressPercent = questions.length ? Math.round((answeredCount / questions.length) * 100) : 0;

  const timeTaken = useMemo(() => {
    const limit = paper?.timerSeconds || 180;
    return Math.min(limit, Math.max(0, Math.round((Date.now() - startedAt) / 1000)));
  }, [paper?.timerSeconds, startedAt, remaining]);

  const loadLevels = async () => {
    if (!token) {
      navigate("/student-login", { replace: true });
      return;
    }
    const data = await getPracticeLevels(token);
    setLevels(data.levels || []);
    setSummary(data.summary);
  };

  useEffect(() => {
    const run = async () => {
      try {
        setLoading(true);
        if (paperId) {
          const data = await getPracticePaper(token, paperId);
          setPaper(data.paper);
          setQuestions(data.questions || []);
          setAnswers(data.progress.answers || {});
          setCurrentIndex(Math.max(0, (data.progress.lastQuestionNumber || 1) - 1));
          const left = Math.max(0, data.paper.timerSeconds - (data.progress.timeSpentSeconds || 0));
          setRemaining(left);
          setStartedAt(Date.now() - (data.progress.timeSpentSeconds || 0) * 1000);
          submittedRef.current = data.progress.status === "submitted";
        } else {
          await loadLevels();
        }
      } catch (error) {
        toast({ title: "Practice error", description: error instanceof Error ? error.message : "Please try again." });
        if (paperId) navigate("/student/online-competition", { replace: true });
      } finally {
        setLoading(false);
      }
    };
    void run();
  }, [paperId, token]);

  useEffect(() => {
    if (!paper || result || submittedRef.current) return;
    const timer = window.setInterval(() => {
      setRemaining((value) => {
        if (value <= 1) {
          window.clearInterval(timer);
          void handleSubmit(true);
          return 0;
        }
        return value - 1;
      });
    }, 1000);
    return () => window.clearInterval(timer);
  }, [paper, result]);

  useEffect(() => {
    if (!paper || result || submittedRef.current) return;
    const saver = window.setInterval(() => {
      void savePracticeProgress(token, {
        paperId: paper.id,
        answers,
        lastQuestionNumber: (questions[currentIndex]?.questionNumber || currentIndex + 1),
        timeSpentSeconds: timeTaken,
      }).catch(() => undefined);
    }, 8000);
    return () => window.clearInterval(saver);
  }, [paper, answers, currentIndex, questions, timeTaken, token, result]);

  const handleAnswer = (questionId: string, option: string) => {
    if (result || submittedRef.current) return;
    setAnswers((prev) => ({ ...prev, [questionId]: option }));
  };

  const handleSubmit = async (auto = false) => {
    if (!paper || submittedRef.current) return;
    try {
      submittedRef.current = true;
      setSubmitting(true);
      const response = await submitPracticePaper(token, { paperId: paper.id, answers, timeTakenSeconds: timeTaken });
      setResult(response.result);
      setConfirmOpen(false);
      toast({
        title: auto ? "Time finished" : "Practice submitted",
        description: `Score: ${response.result.score}/${response.result.totalQuestions}`,
      });
    } catch (error) {
      submittedRef.current = false;
      toast({ title: "Submit failed", description: error instanceof Error ? error.message : "Please try again." });
    } finally {
      setSubmitting(false);
    }
  };

  const currentQuestion = questions[currentIndex];

  if (loading) {
    return <div className="min-h-screen flex items-center justify-center text-slate-500">Loading practice papers...</div>;
  }

  if (!paperId) {
    return (
      <StudentLayout
        header={(
          <div>
            <h1 className="text-2xl md:text-3xl font-heading font-bold text-slate-900">Online Competition</h1>
            <p className="text-sm text-slate-500 mt-1">Online competition practice papers and performance details</p>
          </div>
        )}
      >
        <div className="space-y-6">
          <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            {[
              ["Completed Papers", summary.completedPapers],
              ["Attempts", summary.attempts],
              ["Average Accuracy", `${summary.averageAccuracy}%`],
              ["Best Score", summary.bestScore],
            ].map(([label, value]) => (
              <div key={label} className="rounded-xl bg-white p-5 shadow-card">
                <p className="text-xs uppercase text-slate-500">{label}</p>
                <p className="mt-2 text-2xl font-bold text-slate-900">{value}</p>
              </div>
            ))}
          </div>

          {levels.map((level) => (
            <section key={level.id} className="rounded-xl bg-white p-5 shadow-card">
              <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                  <div className="flex items-center gap-2">
                    <h2 className="text-xl font-heading font-bold text-slate-900">{level.name}</h2>
                    {!level.unlocked && <Lock className="h-4 w-4 text-amber-600" />}
                  </div>
                  <p className="text-sm text-slate-500">10 papers, 60 questions, {Math.round(level.timerSeconds / 60)} minute timer</p>
                </div>
                {!level.unlocked && (
                  <Button className="bg-orange-500 hover:bg-orange-600" onClick={() => navigate("/student/shop")}>
                    Purchase Subscription
                  </Button>
                )}
              </div>

              {!level.unlocked ? (
                <div className="mt-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-800">
                  {level.lockedMessage || "Purchase subscription to access this level"}
                </div>
              ) : (
                <div className="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
                  {level.papers.map((p) => (
                    <button
                      key={p.id}
                      type="button"
                      onClick={() => navigate(`/student/online-competition/${p.id}`)}
                      className="rounded-lg border border-slate-200 bg-slate-50 p-4 text-left transition hover:border-[#5b21b6] hover:bg-white"
                    >
                      <p className="text-xs font-semibold uppercase text-slate-500">Paper {p.paperNumber}</p>
                      <p className="mt-1 min-h-10 font-semibold text-slate-900">{p.title}</p>
                      <div className="mt-3 flex items-center justify-between text-xs text-slate-500">
                        <span>{p.questionCount} questions</span>
                        <span>{p.status === "submitted" ? "Completed" : "Pending"}</span>
                      </div>
                      {p.bestAccuracy !== null && (
                        <p className="mt-2 text-xs font-semibold text-emerald-700">Best: {p.bestScore}/60 ({p.bestAccuracy}%)</p>
                      )}
                    </button>
                  ))}
                  {level.papers.length === 0 && (
                    <p className="col-span-full rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm text-slate-500">
                      No papers imported yet. Ask admin to import DOCX papers.
                    </p>
                  )}
                </div>
              )}
            </section>
          ))}
        </div>
      </StudentLayout>
    );
  }

  return (
    <StudentLayout
      header={(
        <div className="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
          <div>
            <h1 className="text-2xl md:text-3xl font-heading font-bold text-slate-900">{paper?.title || "Practice Paper"}</h1>
            <p className="text-sm text-slate-500 mt-1">{paper?.levelName} • {questions.length} questions</p>
          </div>
          <Button variant="outline" onClick={() => navigate("/student/online-competition")}>Back to Online Competition</Button>
        </div>
      )}
    >
      <div className="space-y-5">
        <div className="sticky top-0 z-30 rounded-xl border border-slate-200 bg-white p-4 shadow-card">
          <div className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div className="flex flex-wrap items-center gap-4">
              <div className={`inline-flex items-center gap-2 rounded-full px-4 py-2 font-bold ${remaining <= 30 ? "bg-red-100 text-red-700" : "bg-slate-900 text-white"}`}>
                <Clock className="h-4 w-4" /> {formatTime(remaining)}
              </div>
              <div className="inline-flex items-center gap-2 text-sm font-semibold text-slate-700">
                <Target className="h-4 w-4 text-[#5b21b6]" /> {answeredCount}/{questions.length} answered
              </div>
              <button
                type="button"
                className="inline-flex items-center gap-2 rounded-full border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700"
                onClick={() => document.documentElement.requestFullscreen?.()}
              >
                <Expand className="h-4 w-4" /> Fullscreen
              </button>
            </div>
            {!result && (
              <Button className="bg-orange-500 hover:bg-orange-600" onClick={() => setConfirmOpen(true)} disabled={submitting}>
                <Send className="mr-2 h-4 w-4" /> Submit
              </Button>
            )}
          </div>
          <Progress className="mt-4" value={progressPercent} />
        </div>

        {result ? (
          <section className="rounded-xl bg-white p-5 shadow-card">
            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
              {[
                ["Score", `${result.score}/${result.totalQuestions}`],
                ["Correct", result.correctCount],
                ["Wrong", result.wrongCount],
                ["Accuracy", `${result.accuracy}%`],
                ["Time", formatTime(result.timeTakenSeconds)],
              ].map(([label, value]) => (
                <div key={label} className="rounded-lg bg-slate-50 p-4">
                  <p className="text-xs uppercase text-slate-500">{label}</p>
                  <p className="mt-1 text-xl font-bold text-slate-900">{value}</p>
                </div>
              ))}
            </div>
            <div className="mt-6 space-y-3">
              {result.review.map((item) => (
                <div key={item.questionId} className="rounded-lg border border-slate-200 p-4">
                  <div className="flex items-start justify-between gap-4">
                    <div>
                      <p className="text-sm font-semibold text-slate-500">Question {item.questionNumber}</p>
                      <p className="mt-1 text-lg font-bold text-slate-900">{item.questionText}</p>
                    </div>
                    {item.isCorrect ? <CheckCircle2 className="h-5 w-5 text-emerald-600" /> : <XCircle className="h-5 w-5 text-red-600" />}
                  </div>
                  <div className="mt-3 grid gap-2 text-sm sm:grid-cols-2">
                    <div className="rounded-md bg-slate-50 px-3 py-2">Your answer: <strong>{item.selectedAnswer || "Not answered"}</strong></div>
                    <div className="rounded-md bg-emerald-50 px-3 py-2 text-emerald-800">Correct answer: <strong>{item.correctAnswer}</strong></div>
                  </div>
                </div>
              ))}
            </div>
            <Button className="mt-5 bg-slate-900 hover:bg-slate-800" onClick={() => navigate("/student/online-competition")}>
              <RotateCcw className="mr-2 h-4 w-4" /> Back to Online Competition
            </Button>
          </section>
        ) : (
          <div className="grid gap-5 xl:grid-cols-[1fr_280px]">
            <section className="rounded-xl bg-white p-5 shadow-card">
              {currentQuestion && (
                <>
                  <div className="flex items-center justify-between gap-4">
                    <p className="text-sm font-semibold text-slate-500">Question {currentQuestion.questionNumber}</p>
                    <p className="text-sm text-slate-500">{currentIndex + 1} of {questions.length}</p>
                  </div>
                  <h2 className="mt-4 text-3xl font-bold tracking-normal text-slate-900">{currentQuestion.questionText}</h2>
                  <div className="mt-6 grid gap-3 sm:grid-cols-2">
                    {currentQuestion.options.map((option) => {
                      const selected = answers[currentQuestion.id] === option;
                      return (
                        <button
                          key={option}
                          type="button"
                          onClick={() => handleAnswer(currentQuestion.id, option)}
                          className={`min-h-16 rounded-lg border px-5 py-4 text-left text-xl font-bold transition ${
                            selected ? "border-[#5b21b6] bg-[#5b21b6] text-white" : "border-slate-200 bg-slate-50 text-slate-900 hover:border-[#5b21b6]"
                          }`}
                        >
                          {option}
                        </button>
                      );
                    })}
                  </div>
                  <div className="mt-6 flex items-center justify-between">
                    <Button variant="outline" onClick={() => setCurrentIndex((i) => Math.max(0, i - 1))} disabled={currentIndex === 0}>
                      Previous
                    </Button>
                    <Button className="bg-slate-900 hover:bg-slate-800" onClick={() => setCurrentIndex((i) => Math.min(questions.length - 1, i + 1))} disabled={currentIndex >= questions.length - 1}>
                      Next
                    </Button>
                  </div>
                </>
              )}
            </section>

            <aside className="rounded-xl bg-white p-4 shadow-card">
              <h3 className="font-bold text-slate-900">Question Navigator</h3>
              <div className="mt-4 grid grid-cols-6 gap-2 xl:grid-cols-5">
                {questions.map((q, index) => {
                  const answered = Boolean(answers[q.id]);
                  const active = index === currentIndex;
                  return (
                    <button
                      key={q.id}
                      type="button"
                      onClick={() => setCurrentIndex(index)}
                      className={`aspect-square rounded-md text-sm font-bold ${
                        active ? "bg-orange-500 text-white" : answered ? "bg-emerald-100 text-emerald-700" : "bg-slate-100 text-slate-600"
                      }`}
                    >
                      {q.questionNumber}
                    </button>
                  );
                })}
              </div>
            </aside>
          </div>
        )}
      </div>

      <AlertDialog open={confirmOpen} onOpenChange={setConfirmOpen}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Submit final answers?</AlertDialogTitle>
            <AlertDialogDescription>
              You answered {answeredCount} of {questions.length} questions. After submission, answers cannot be edited.
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel>Continue Practice</AlertDialogCancel>
            <AlertDialogAction onClick={() => void handleSubmit(false)} disabled={submitting}>
              Submit Paper
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </StudentLayout>
  );
};

export default StudentPractice;
