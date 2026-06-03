import { FormEvent, useMemo, useState } from "react";
import { Link, useNavigate, useSearchParams } from "react-router-dom";
import { Eye, EyeOff, Lock, ShieldCheck } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { useToast } from "@/hooks/use-toast";
import { competitionResetPassword } from "@/services/competitionApi";

const CompetitionResetPassword = () => {
  const [searchParams] = useSearchParams();
  const navigate = useNavigate();
  const { toast } = useToast();
  const email = useMemo(() => searchParams.get("email") || "", [searchParams]);
  const token = useMemo(() => searchParams.get("token") || "", [searchParams]);
  const [showPassword, setShowPassword] = useState(false);
  const [submitting, setSubmitting] = useState(false);
  const [form, setForm] = useState({ password: "", confirmPassword: "" });

  const handleSubmit = async (event: FormEvent) => {
    event.preventDefault();
    if (!email || !token) {
      toast({ title: "Invalid link", description: "Please request a new competition password reset link." });
      return;
    }
    if (form.password !== form.confirmPassword) {
      toast({ title: "Password mismatch", description: "Confirm password must match the new password." });
      return;
    }

    try {
      setSubmitting(true);
      const response = await competitionResetPassword(email, token, form.password, form.confirmPassword);
      toast({ title: "Password updated", description: response.message });
      navigate("/online-competition");
    } catch (error) {
      toast({ title: "Reset failed", description: error instanceof Error ? error.message : "Please try again." });
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <div className="min-h-screen bg-[#eef4ff] px-4 py-8 text-slate-950">
      <div className="mx-auto flex min-h-[calc(100vh-4rem)] max-w-md flex-col justify-center">
        <form onSubmit={handleSubmit} className="rounded-2xl bg-white px-6 py-8 shadow-xl shadow-blue-950/10 ring-1 ring-blue-100 sm:px-8">
          <div className="text-center">
            <span className="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-blue-100 text-blue-700">
              <ShieldCheck className="h-7 w-7" />
            </span>
            <h1 className="mt-5 text-2xl font-bold tracking-normal text-slate-950">Create a strong password</h1>
            <p className="mt-3 text-sm leading-6 text-slate-500">Use uppercase, lowercase, and a number. Minimum 8 characters.</p>
          </div>

          <div className="mt-8 space-y-4">
            <label className="block">
              <span className="text-sm font-semibold text-slate-950">New Password</span>
              <div className="relative mt-2">
                <Lock className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" />
                <Input
                  type={showPassword ? "text" : "password"}
                  value={form.password}
                  onChange={(event) => setForm((prev) => ({ ...prev, password: event.target.value }))}
                  className="h-11 rounded-md bg-blue-50 pl-10 pr-10"
                  placeholder="New strong password"
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

            <label className="block">
              <span className="text-sm font-semibold text-slate-950">Confirm Password</span>
              <div className="relative mt-2">
                <Lock className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" />
                <Input
                  type={showPassword ? "text" : "password"}
                  value={form.confirmPassword}
                  onChange={(event) => setForm((prev) => ({ ...prev, confirmPassword: event.target.value }))}
                  className="h-11 rounded-md bg-blue-50 pl-10"
                  placeholder="Confirm new password"
                />
              </div>
            </label>
          </div>

          <Button type="submit" className="mt-6 h-12 w-full rounded-lg bg-blue-600 text-base font-bold hover:bg-blue-700" disabled={submitting}>
            {submitting ? "Updating..." : "Set New Password"}
          </Button>
        </form>

        <p className="mt-7 text-center text-sm text-slate-500">
          Remembered your password?{" "}
          <Link to="/online-competition" className="font-semibold text-blue-600 hover:text-blue-700">
            Back to login
          </Link>
        </p>
      </div>
    </div>
  );
};

export default CompetitionResetPassword;
