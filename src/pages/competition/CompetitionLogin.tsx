import { FormEvent, useState } from "react";
import { Link, useNavigate } from "react-router-dom";
import { Eye, EyeOff, Lock, Mail } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { useToast } from "@/hooks/use-toast";
import { competitionLogin } from "@/services/competitionApi";

const COMPETITION_TOKEN_KEY = "competition_auth_token";
const COMPETITION_USER_KEY = "competition_auth_user";

const CompetitionLogin = () => {
  const navigate = useNavigate();
  const { toast } = useToast();
  const [showPassword, setShowPassword] = useState(false);
  const [submitting, setSubmitting] = useState(false);
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

  return (
    <div className="min-h-screen bg-[#eef4ff] px-4 py-8">
      <div className="mx-auto flex min-h-[calc(100vh-4rem)] max-w-md flex-col justify-center">
        <form onSubmit={handleSubmit} className="rounded-xl bg-white px-6 py-8 shadow-lg sm:px-8">
          <div className="text-center">
            <h1 className="text-2xl font-bold tracking-normal text-slate-950">Welcome Back</h1>
            <p className="mt-3 text-sm text-slate-500">Sign in to your Competition account</p>
          </div>

          <div className="mt-10 space-y-4">
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
              className="text-sm font-medium text-blue-600"
              onClick={() => toast({ title: "Forgot password", description: "Use the main student forgot-password flow or connect competition reset email." })}
            >
              Forgot your password?
            </button>
          </div>

          <Button type="submit" className="mt-5 h-11 w-full rounded-md bg-blue-600 hover:bg-blue-700" disabled={submitting}>
            {submitting ? "Signing in..." : "Sign in"}
          </Button>
        </form>

        <p className="mt-7 text-center text-sm text-slate-500">
          Don&apos;t have an account?{" "}
          <Link to="/competition-register" className="font-semibold text-blue-600">
            Sign up
          </Link>
        </p>
      </div>
    </div>
  );
};

export default CompetitionLogin;
