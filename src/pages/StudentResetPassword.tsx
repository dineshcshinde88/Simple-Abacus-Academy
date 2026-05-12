import { useState } from "react";
import { Eye, EyeOff } from "lucide-react";
import { Link, useNavigate, useSearchParams } from "react-router-dom";
import Navbar from "@/components/layout/Navbar";
import Footer from "@/components/layout/Footer";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { useToast } from "@/hooks/use-toast";
import { resetPassword } from "@/lib/auth";

const StudentResetPassword = () => {
  const { toast } = useToast();
  const navigate = useNavigate();
  const [searchParams] = useSearchParams();
  const email = searchParams.get("email") || "";
  const token = searchParams.get("token") || "";
  const [showPassword, setShowPassword] = useState(false);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [form, setForm] = useState({ password: "", confirmPassword: "" });

  const updateField = (field: keyof typeof form, value: string) => {
    setForm((prev) => ({ ...prev, [field]: value }));
  };

  const handleSubmit = async (event: React.FormEvent) => {
    event.preventDefault();

    if (!email || !token) {
      toast({ title: "Invalid link", description: "Please request a new password reset link." });
      return;
    }
    if (form.password.length < 6 || form.password !== form.confirmPassword) {
      toast({ title: "Password mismatch", description: "Enter matching passwords with at least 6 characters." });
      return;
    }

    try {
      setIsSubmitting(true);
      await resetPassword(email, token, form.password, form.confirmPassword);
      toast({ title: "Password updated", description: "You can now login with your new password." });
      navigate("/student-login");
    } catch (error) {
      const message = error instanceof Error ? error.message : "Unable to reset password";
      toast({ title: "Reset failed", description: message });
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
            <div className="mx-auto max-w-lg rounded-2xl border border-border bg-white p-8 shadow-card">
              <h1 className="text-2xl md:text-3xl font-heading font-bold text-[#4B1E83]">Reset Student Password</h1>
              <p className="mt-2 text-sm text-muted-foreground">Create a new password for {email || "your student account"}.</p>

              <form onSubmit={handleSubmit} className="mt-6 space-y-5">
                <div className="relative">
                  <Input
                    type={showPassword ? "text" : "password"}
                    placeholder="New Password"
                    value={form.password}
                    onChange={(event) => updateField("password", event.target.value)}
                    className="h-12 rounded-full border-[#c7d2fe] pr-12 focus-visible:ring-[#4B1E83]"
                  />
                  <button
                    type="button"
                    className="absolute right-4 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                    onClick={() => setShowPassword((prev) => !prev)}
                    aria-label={showPassword ? "Hide password" : "Show password"}
                  >
                    {showPassword ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                  </button>
                </div>
                <Input
                  type={showPassword ? "text" : "password"}
                  placeholder="Confirm Password"
                  value={form.confirmPassword}
                  onChange={(event) => updateField("confirmPassword", event.target.value)}
                  className="h-12 rounded-full border-[#c7d2fe] focus-visible:ring-[#4B1E83]"
                />
                <div className="flex flex-wrap items-center justify-between gap-3">
                  <Button type="submit" className="rounded-md bg-[#4B1E83] hover:bg-[#3c176a] px-8">
                    {isSubmitting ? "Updating..." : "Update Password"}
                  </Button>
                  <Link to="/student-login" className="text-sm text-[#4B1E83] hover:underline">
                    Back to Login
                  </Link>
                </div>
              </form>
            </div>
          </div>
        </section>
      </main>
      <Footer />
    </div>
  );
};

export default StudentResetPassword;
