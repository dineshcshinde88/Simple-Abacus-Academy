import { useState } from "react";
import { Dialog, DialogContent, DialogDescription, DialogTitle } from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { GraduationCap, Eye, EyeOff, Mail, Lock, User } from "lucide-react";
import { toast } from "sonner";
import { useAuth } from "@/context/AuthContext";
import { AuthApiError } from "@/lib/auth";
import { useNavigate } from "react-router-dom";

interface LoginDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
}

const LoginDialog = ({ open, onOpenChange }: LoginDialogProps) => {
  const navigate = useNavigate();
  const { login, register, forgotPassword } = useAuth();
  const [role, setRole] = useState<"student" | "tutor">("student");
  const [name, setName] = useState("");
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [showPassword, setShowPassword] = useState(false);
  const [isLoading, setIsLoading] = useState(false);
  const [mode, setMode] = useState<"login" | "signup" | "forgot">("login");

  const isValidEmail = (value: string) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();

    if (!isValidEmail(email)) {
      toast.error("Enter a valid email address.");
      return;
    }

    if ((mode === "login" || mode === "signup") && password.trim().length < 6) {
      toast.error("Password must be at least 6 characters.");
      return;
    }

    setIsLoading(true);

    try {
      if (mode === "forgot") {
        await forgotPassword(email);
        toast.success("Password reset request submitted.");
        setMode("login");
      } else if (mode === "signup") {
        await register(name, email, password, role);
        toast.success("Account created successfully.");
        onOpenChange(false);
        navigate(role === "tutor" ? "/teacher-dashboard" : "/student-dashboard");
      } else {
        await login(email, password, role);
        toast.success("Logged in successfully.");
        onOpenChange(false);
        navigate(role === "tutor" ? "/teacher-dashboard" : "/student-dashboard");
      }
    } catch (error) {
      let message = error instanceof Error ? error.message : "Authentication failed";
      if (mode === "login" && error instanceof AuthApiError && error.status === 401) {
        message = "Invalid email or password";
      }
      toast.error(message);
    } finally {
      setIsLoading(false);
    }
  };

  const title = mode === "login" ? "Login" : mode === "signup" ? "Register" : "Reset Password";
  const description =
    mode === "login"
      ? "Login to access your dashboard"
    : mode === "signup"
        ? "Create a new account"
        : "Enter your email to receive a reset link";

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-[420px] p-0 overflow-hidden border-border bg-card">
        <div className="gradient-accent px-6 py-5 flex items-center gap-3">
          <div className="w-10 h-10 rounded-lg bg-accent-foreground/20 flex items-center justify-center">
            <GraduationCap className="w-6 h-6 text-accent-foreground" />
          </div>
          <div>
            <DialogTitle className="text-accent-foreground text-lg">{title}</DialogTitle>
            <DialogDescription className="text-accent-foreground/70 text-sm">{description}</DialogDescription>
          </div>
        </div>

        <form onSubmit={handleSubmit} className="px-6 pb-6 pt-2 space-y-4">
          {mode !== "forgot" && (
            <div className="space-y-2">
              <Label>I am a</Label>
              <div className="grid grid-cols-2 gap-2">
                <button
                  type="button"
                  className={`rounded-md border px-3 py-2 text-sm font-medium transition ${role === "student" ? "border-secondary bg-secondary/10 text-foreground" : "border-border bg-background text-muted-foreground hover:text-foreground"}`}
                  onClick={() => setRole("student")}
                >
                  Student
                </button>
                <button
                  type="button"
                  className={`rounded-md border px-3 py-2 text-sm font-medium transition ${role === "tutor" ? "border-secondary bg-secondary/10 text-foreground" : "border-border bg-background text-muted-foreground hover:text-foreground"}`}
                  onClick={() => setRole("tutor")}
                >
                  Teacher
                </button>
              </div>
            </div>
          )}

          {mode === "signup" && (
            <div className="space-y-2">
              <Label htmlFor="signup-name">Full Name</Label>
              <div className="relative">
                <User className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
                <Input
                  id="signup-name"
                  type="text"
                  placeholder="Student or parent name"
                  className="pl-10"
                  value={name}
                  onChange={(e) => setName(e.target.value)}
                  required
                />
              </div>
            </div>
          )}

          <div className="space-y-2">
            <Label htmlFor="login-email">Email</Label>
            <div className="relative">
              <Mail className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
              <Input
                id="login-email"
                type="email"
                placeholder="you@example.com"
                className="pl-10"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                required
              />
            </div>
          </div>

          {mode !== "forgot" && (
            <div className="space-y-2">
              <Label htmlFor="login-password">Password</Label>
              <div className="relative">
                <Lock className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
                <Input
                  id="login-password"
                  type={showPassword ? "text" : "password"}
                  placeholder="********"
                  className="pl-10 pr-10"
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  required={mode !== "forgot"}
                />
                <button
                  type="button"
                  className="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                  onClick={() => setShowPassword(!showPassword)}
                >
                  {showPassword ? <EyeOff className="w-4 h-4" /> : <Eye className="w-4 h-4" />}
                </button>
              </div>
            </div>
          )}

          {mode === "login" && (
            <button type="button" className="text-sm text-primary hover:underline" onClick={() => setMode("forgot")}>
              Forgot password?
            </button>
          )}

          <Button type="submit" className="w-full gradient-accent text-accent-foreground font-semibold shadow-glow" disabled={isLoading}>
            {isLoading
              ? "Please wait..."
              : mode === "login"
                ? "Sign In"
                : mode === "signup"
                  ? "Create Account"
                  : "Send Reset Link"}
          </Button>

          <p className="text-center text-sm text-muted-foreground">
            {mode === "login" ? (
              <>
                Don't have an account?{" "}
                <button type="button" className="text-primary font-medium hover:underline" onClick={() => setMode("signup")}>
                  Sign up
                </button>
              </>
            ) : mode === "forgot" ? (
              <>
                Back to{" "}
                <button type="button" className="text-primary font-medium hover:underline" onClick={() => setMode("login")}>
                  Sign in
                </button>
              </>
            ) : (
              <>
                Already have an account?{" "}
                <button type="button" className="text-primary font-medium hover:underline" onClick={() => setMode("login")}>
                  Sign in
                </button>
              </>
            )}
          </p>
        </form>
      </DialogContent>
    </Dialog>
  );
};

export default LoginDialog;
