import { useMemo, useState } from "react";
import { Link, useNavigate, useSearchParams } from "react-router-dom";
import Navbar from "@/components/layout/Navbar";
import Footer from "@/components/layout/Footer";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { toast } from "sonner";
import { resetInstructorPassword } from "@/services/instructorAuthApi";

const InstructorResetPassword = () => {
  const navigate = useNavigate();
  const [searchParams] = useSearchParams();
  const email = useMemo(() => searchParams.get("email") || "", [searchParams]);
  const token = useMemo(() => searchParams.get("token") || "", [searchParams]);
  const [form, setForm] = useState({ password: "", confirmPassword: "" });
  const [isSubmitting, setIsSubmitting] = useState(false);

  const handleSubmit = async (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    if (!email || !token) {
      toast.error("This reset link is invalid or expired.");
      return;
    }
    if (form.password !== form.confirmPassword) {
      toast.error("Passwords do not match.");
      return;
    }

    try {
      setIsSubmitting(true);
      const response = await resetInstructorPassword({
        email,
        token,
        password: form.password,
        confirmPassword: form.confirmPassword,
      });
      toast.success(response.message || "Password reset successfully.");
      navigate("/instructor-login");
    } catch (error) {
      toast.error(error instanceof Error ? error.message : "Unable to reset password.");
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <div className="min-h-screen bg-background">
      <Navbar />
      <main className="pt-16">
        <section className="py-16">
          <div className="container mx-auto px-4">
            <div className="mx-auto max-w-xl rounded-2xl border border-border bg-white p-8 shadow-card">
              <div className="text-center">
                <img src="/abacus_logo.png" alt="Abacus Trainer" className="mx-auto h-16 w-auto" />
                <h1 className="mt-6 text-3xl font-heading font-bold text-[#4B1E83]">Create New Password</h1>
                <p className="mt-2 text-sm text-muted-foreground">Reset your instructor account password.</p>
              </div>

              <form onSubmit={handleSubmit} className="mt-8 space-y-5">
                <div className="space-y-2">
                  <Label htmlFor="password">Password</Label>
                  <Input
                    id="password"
                    type="password"
                    placeholder="New password"
                    value={form.password}
                    onChange={(event) => setForm((prev) => ({ ...prev, password: event.target.value }))}
                    required
                  />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="confirmPassword">Confirm Password</Label>
                  <Input
                    id="confirmPassword"
                    type="password"
                    placeholder="Confirm new password"
                    value={form.confirmPassword}
                    onChange={(event) => setForm((prev) => ({ ...prev, confirmPassword: event.target.value }))}
                    required
                  />
                </div>
                <Button type="submit" className="w-full bg-[#4B1E83] hover:bg-[#3c176a]" disabled={isSubmitting}>
                  {isSubmitting ? "Saving..." : "Reset Password"}
                </Button>
              </form>

              <p className="mt-6 text-center text-sm text-muted-foreground">
                Remembered it?{" "}
                <Link to="/instructor-login" className="font-semibold text-orange-600 hover:underline">
                  Login
                </Link>
              </p>
            </div>
          </div>
        </section>
      </main>
      <Footer />
    </div>
  );
};

export default InstructorResetPassword;
