import { FormEvent, useState } from "react";
import { Link, useNavigate } from "react-router-dom";
import { BookOpen, Eye, EyeOff, Lock, Mail, Medal, Sparkles, Target, Trophy } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { useToast } from "@/hooks/use-toast";
import { competitionForgotPassword, competitionLogin } from "@/services/competitionApi";

const COMPETITION_TOKEN_KEY = "competition_auth_token";
const COMPETITION_USER_KEY = "competition_auth_user";

const highlights = [
  {
    icon: Target,
    title: "Challenge-ready practice",
    text: "Sharpen speed, accuracy, and focus with competition-style preparation.",
  },
  {
    icon: Trophy,
    title: "Live progress visibility",
    text: "Track attempts, scores, rankings, and published competition results.",
  },
  {
    icon: BookOpen,
    title: "Smart practice kits",
    text: "Access curated worksheets and timed drills for every competition level.",
  },
];

const CompetitionLogin = () => {
  const navigate = useNavigate();
  const { toast } = useToast();
  const [showPassword, setShowPassword] = useState(false);
  const [submitting, setSubmitting] = useState(false);
  const [sendingReset, setSendingReset] = useState(false);
  const [form, setForm] = useState({ email: "", password: "" });

  const handleSubmit = async (event: FormEvent) => {
    event.preventDefault();
    if (!form.email.trim() || !form.password.trim()) {
      toast({ title: "Missing details", description: "Email and password are required." });
      return;
    }

    try {
      setSubmitting(true);
      const response = await competitionLogin(form.email.trim(), form.password);
      localStorage.setItem(COMPETITION_TOKEN_KEY, response.token);
      localStorage.setItem(COMPETITION_USER_KEY, JSON.stringify(response.user));
      toast({ title: "Login successful", description: "Welcome to the competition portal." });
      navigate("/competition/dashboard");
    } catch (error) {
      toast({ title: "Login failed", description: error instanceof Error ? error.message : "Please try again." });
    } finally {
      setSubmitting(false);
    }
  };

  const handleForgotPassword = async () => {
    if (!form.email.trim()) {
      toast({ title: "Email required", description: "Enter your registered competition email first." });
      return;
    }

    try {
      setSendingReset(true);
      const response = await competitionForgotPassword(form.email.trim());
      toast({ title: "Reset link sent", description: response.message });
    } catch (error) {
      toast({ title: "Reset failed", description: error instanceof Error ? error.message : "Please try again." });
    } finally {
      setSendingReset(false);
    }
  };

  return (
    <div className="min-h-screen bg-[#eef4ff] px-4 py-8 text-slate-950">
      <div className="mx-auto grid min-h-[calc(100vh-4rem)] max-w-6xl items-center gap-10 lg:grid-cols-[1.05fr_0.95fr]">
        <section className="mx-auto w-full max-w-xl">
          <div className="flex items-center gap-4">
            <span className="flex h-14 w-14 items-center justify-center rounded-2xl bg-white shadow-sm ring-1 ring-blue-100">
              <img src="/abacus_logo.png" alt="Simple Abacus logo" className="h-11 w-11 object-contain" />
            </span>
            <div>
              <p className="text-2xl font-bold leading-tight">Simple Abacus</p>
              <p className="text-sm font-medium text-slate-500">Online Competition Arena</p>
            </div>
          </div>

          <div className="mt-8">
            <div className="inline-flex items-center gap-2 rounded-full bg-blue-100 px-3 py-1 text-sm font-semibold text-blue-700">
              <Sparkles className="h-4 w-4" />
              Train sharp. Compete calm. Win faster.
            </div>
            <h1 className="mt-5 max-w-lg text-4xl font-extrabold leading-tight tracking-normal text-slate-950 md:text-5xl">
              Welcome back, champion.
            </h1>
            <p className="mt-4 max-w-xl text-lg leading-8 text-slate-600">
              Sign in to continue your abacus competition journey with practice kits, timed exams, leaderboards, and results in one focused portal.
            </p>
          </div>

          <div className="mt-9 space-y-6">
            {highlights.map((item) => (
              <div key={item.title} className="flex gap-4">
                <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white text-blue-600 shadow-sm ring-1 ring-blue-100">
                  <item.icon className="h-5 w-5" />
                </span>
                <div>
                  <p className="font-bold text-slate-950">{item.title}</p>
                  <p className="mt-1 text-sm leading-6 text-slate-600">{item.text}</p>
                </div>
              </div>
            ))}
          </div>

          <div className="mt-10 rounded-2xl border border-white/70 bg-white/80 p-6 shadow-sm backdrop-blur">
            <div className="flex gap-4">
              <span className="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-blue-500 to-orange-400 text-lg font-bold text-white">
                <Medal className="h-6 w-6" />
              </span>
              <div>
                <p className="italic leading-7 text-slate-600">
                  "Every practice round feels clear and motivating. I can see where I improved before competition day."
                </p>
                <p className="mt-3 font-bold text-slate-950">Simple Abacus Student</p>
                <p className="text-sm text-slate-500">Competition Practice Program</p>
              </div>
            </div>
          </div>
        </section>

        <section className="mx-auto w-full max-w-md">
          <form onSubmit={handleSubmit} className="rounded-2xl bg-white px-6 py-8 shadow-xl shadow-blue-950/10 ring-1 ring-blue-100 sm:px-8">
            <div className="text-center">
              <h2 className="text-2xl font-bold tracking-normal text-slate-950">Ready for your next round?</h2>
              <p className="mt-3 text-sm text-slate-500">Sign in to your Simple Abacus competition account</p>
            </div>

            <div className="mt-8 rounded-xl border border-blue-100 bg-blue-50 px-4 py-3 text-center text-sm font-semibold text-blue-700">
              Student Competition Login
            </div>

            <div className="mt-6 flex items-center gap-3">
              <span className="h-px flex-1 bg-slate-200" />
              <span className="text-xs font-semibold uppercase text-slate-500">Enter your details</span>
              <span className="h-px flex-1 bg-slate-200" />
            </div>

            <div className="mt-6 space-y-4">
            <label className="block">
              <span className="text-sm font-semibold text-slate-950">Email or Username</span>
              <div className="relative mt-2">
                <Mail className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" />
                <Input
                  type="email"
                  value={form.email}
                  onChange={(event) => setForm((prev) => ({ ...prev, email: event.target.value }))}
                  className="h-10 rounded-md bg-blue-50 pl-10"
                  placeholder="student@example.com"
                />
              </div>
            </label>

            <label className="block">
              <span className="text-sm font-semibold text-slate-950">Password</span>
              <div className="relative mt-2">
                <Lock className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" />
                <Input
                  type={showPassword ? "text" : "password"}
                  value={form.password}
                  onChange={(event) => setForm((prev) => ({ ...prev, password: event.target.value }))}
                  className="h-10 rounded-md bg-blue-50 pl-10 pr-10"
                  placeholder="Password"
                />
                <button
                  type="button"
                  aria-label={showPassword ? "Hide password" : "Show password"}
                  className="absolute right-3 top-1/2 -translate-y-1/2 text-slate-500"
                  onClick={() => setShowPassword((value) => !value)}
                >
                  {showPassword ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                </button>
              </div>
            </label>
            </div>

            <div className="mt-4 text-right">
              <button
                type="button"
                className="text-sm font-medium text-blue-600 hover:text-blue-700"
                disabled={sendingReset}
                onClick={handleForgotPassword}
              >
                {sendingReset ? "Sending reset link..." : "Forgot your password?"}
              </button>
            </div>

            <Button type="submit" className="mt-5 h-12 w-full rounded-lg bg-blue-600 text-base font-bold hover:bg-blue-700" disabled={submitting}>
              {submitting ? "Signing in..." : "Enter Competition Portal"}
            </Button>
          </form>

          <p className="mt-7 text-center text-sm text-slate-500">
            New competitor?{" "}
            <Link to="/competition-register" className="font-semibold text-blue-600 hover:text-blue-700">
              Create competition account
            </Link>
          </p>
        </section>
      </div>
    </div>
  );
};

export default CompetitionLogin;
