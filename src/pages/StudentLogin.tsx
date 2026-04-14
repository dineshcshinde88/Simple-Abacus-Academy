import { useState } from "react";
import { Eye, EyeOff, RefreshCcw } from "lucide-react";
import { Link, useNavigate } from "react-router-dom";
import Navbar from "@/components/layout/Navbar";
import Footer from "@/components/layout/Footer";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { login } from "@/lib/auth";
import { useToast } from "@/hooks/use-toast";

const buildCaptcha = () => {
  const chars = "ABCDEFGHJKLMNPQRSTUVWXYZ23456789";
  let result = "";
  for (let i = 0; i < 6; i += 1) {
    result += chars[Math.floor(Math.random() * chars.length)];
  }
  return result;
};

const StudentLogin = () => {
  const { toast } = useToast();
  const navigate = useNavigate();
  const [showPassword, setShowPassword] = useState(false);
  const [captcha, setCaptcha] = useState(buildCaptcha());
  const [captchaInput, setCaptchaInput] = useState("");
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [form, setForm] = useState({ email: "", password: "" });

  const refreshCaptcha = () => {
    setCaptcha(buildCaptcha());
    setCaptchaInput("");
  };

  const updateField = (field: keyof typeof form, value: string) => {
    setForm((prev) => ({ ...prev, [field]: value }));
  };

  const handleSubmit = async (event: React.FormEvent) => {
    event.preventDefault();

    if (!form.email.trim() || !form.password.trim()) {
      toast({ title: "Missing details", description: "Email and password are required." });
      return;
    }
    if (captchaInput.trim().toLowerCase() !== captcha.toLowerCase()) {
      toast({ title: "Invalid captcha", description: "Please enter the correct captcha code." });
      return;
    }

    try {
      setIsSubmitting(true);
      const response = await login(form.email.trim(), form.password, "student");
      localStorage.setItem("abacus_auth_token", response.token);
      toast({ title: "Login successful", description: "Welcome back!" });
      navigate("/student/dashboard");
    } catch (error) {
      const message = error instanceof Error ? error.message : "Login failed";
      toast({ title: "Login failed", description: message });
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
            <div className="grid gap-10 lg:grid-cols-[1.1fr_0.9fr] items-center">
              <form
                onSubmit={handleSubmit}
                className="rounded-2xl border border-border bg-white p-8 shadow-card"
              >
                <h1 className="text-2xl md:text-3xl font-heading font-bold text-[#4B1E83]">Student Login</h1>
                <div className="mt-6 space-y-5">
                  <div>
                    <Input
                      type="email"
                      placeholder="Email"
                      value={form.email}
                      onChange={(event) => updateField("email", event.target.value)}
                      className="h-12 rounded-full border-[#c7d2fe] focus-visible:ring-[#4B1E83]"
                    />
                  </div>
                  <div className="relative">
                    <Input
                      type={showPassword ? "text" : "password"}
                      placeholder="Password"
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
                  <div className="grid gap-3 md:grid-cols-[1fr_1fr] items-center">
                    <div className="flex items-center gap-3">
                      <div className="flex-1 rounded-full border border-border bg-muted px-4 py-2 text-center text-sm font-semibold text-foreground">
                        {captcha}
                      </div>
                      <button
                        type="button"
                        className="inline-flex h-10 w-10 items-center justify-center rounded-full border border-border text-muted-foreground hover:text-foreground"
                        onClick={refreshCaptcha}
                        aria-label="Refresh captcha"
                      >
                        <RefreshCcw className="h-4 w-4" />
                      </button>
                    </div>
                    <Input
                      placeholder="Enter Captcha Code"
                      value={captchaInput}
                      onChange={(event) => setCaptchaInput(event.target.value)}
                      className="h-12 rounded-full border-[#c7d2fe] focus-visible:ring-[#4B1E83]"
                    />
                  </div>
                  <div className="flex flex-wrap items-center justify-between gap-3 text-sm">
                    <button type="button" className="text-[#4B1E83] hover:underline">
                      Forgot Password
                    </button>
                    <div className="text-muted-foreground">
                      New to ABACUS {" "}
                      <Link to="/programs" className="text-red-600 font-semibold hover:underline">
                        Sign Up
                      </Link>
                    </div>
                  </div>
                  <Button className="rounded-md bg-[#4B1E83] hover:bg-[#3c176a] px-8">
                    {isSubmitting ? "Signing In..." : "Sign In"}
                  </Button>
                </div>
              </form>

              <div className="hidden lg:block">
                <img
                  src="/assets/student-login.png"
                  alt="Student login illustration"
                  className="w-full max-w-xl mx-auto"
                />
              </div>
            </div>
          </div>
        </section>
      </main>
      <Footer />
    </div>
  );
};

export default StudentLogin;
