import { FormEvent, useState } from "react";
import { Eye, EyeOff, KeyRound, Lock } from "lucide-react";
import StudentLayout from "@/layouts/StudentLayout";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { useToast } from "@/hooks/use-toast";
import { changeStudentPassword } from "@/services/studentApi";

const TOKEN_KEY = "abacus_auth_token";

const StudentChangePassword = () => {
  const { toast } = useToast();
  const [form, setForm] = useState({
    currentPassword: "",
    newPassword: "",
    confirmPassword: "",
  });
  const [showPasswords, setShowPasswords] = useState(false);
  const [submitting, setSubmitting] = useState(false);

  const updateField = (field: keyof typeof form, value: string) => {
    setForm((prev) => ({ ...prev, [field]: value }));
  };

  const handleSubmit = async (event: FormEvent) => {
    event.preventDefault();

    if (!form.currentPassword || !form.newPassword || !form.confirmPassword) {
      toast({ title: "Missing details", description: "Please fill all password fields." });
      return;
    }
    if (form.newPassword.length < 6) {
      toast({ title: "Password too short", description: "New password must be at least 6 characters." });
      return;
    }
    if (form.newPassword !== form.confirmPassword) {
      toast({ title: "Password mismatch", description: "New password and confirm password do not match." });
      return;
    }

    const token = localStorage.getItem(TOKEN_KEY);
    if (!token) {
      toast({ title: "Login required", description: "Please login again to change your password." });
      return;
    }

    try {
      setSubmitting(true);
      await changeStudentPassword(token, form);
      setForm({ currentPassword: "", newPassword: "", confirmPassword: "" });
      toast({ title: "Password changed", description: "Your password has been updated successfully." });
    } catch (error) {
      toast({
        title: "Unable to change password",
        description: error instanceof Error ? error.message : "Please try again.",
      });
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <StudentLayout
      header={(
        <div>
          <h1 className="text-2xl md:text-3xl font-heading font-bold text-slate-900">Change Password</h1>
          <p className="text-sm text-slate-500 mt-1">Secure your account</p>
        </div>
      )}
    >
      <div className="grid gap-6 lg:grid-cols-[minmax(0,560px)_1fr]">
        <form onSubmit={handleSubmit} className="rounded-2xl bg-white p-6 shadow-card">
          <div className="flex items-center gap-3">
            <div className="flex h-11 w-11 items-center justify-center rounded-xl bg-[#5b21b6] text-white">
              <KeyRound className="h-5 w-5" />
            </div>
            <div>
              <h2 className="text-lg font-heading font-bold text-slate-900">Update Password</h2>
              <p className="text-sm text-slate-500">Use a strong password for your student account.</p>
            </div>
          </div>

          <div className="mt-6 space-y-5">
            <div className="space-y-2">
              <Label htmlFor="current-password">Current Password</Label>
              <Input
                id="current-password"
                type={showPasswords ? "text" : "password"}
                value={form.currentPassword}
                onChange={(event) => updateField("currentPassword", event.target.value)}
                placeholder="Enter current password"
                autoComplete="current-password"
              />
            </div>

            <div className="space-y-2">
              <Label htmlFor="new-password">New Password</Label>
              <Input
                id="new-password"
                type={showPasswords ? "text" : "password"}
                value={form.newPassword}
                onChange={(event) => updateField("newPassword", event.target.value)}
                placeholder="Enter new password"
                autoComplete="new-password"
              />
            </div>

            <div className="space-y-2">
              <Label htmlFor="confirm-password">Confirm Password</Label>
              <Input
                id="confirm-password"
                type={showPasswords ? "text" : "password"}
                value={form.confirmPassword}
                onChange={(event) => updateField("confirmPassword", event.target.value)}
                placeholder="Confirm new password"
                autoComplete="new-password"
              />
            </div>

            <button
              type="button"
              className="inline-flex items-center gap-2 text-sm font-semibold text-[#5b21b6]"
              onClick={() => setShowPasswords((value) => !value)}
            >
              {showPasswords ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
              {showPasswords ? "Hide passwords" : "Show passwords"}
            </button>

            <Button type="submit" className="w-full bg-orange-500 hover:bg-orange-600" disabled={submitting}>
              {submitting ? "Updating..." : "Change Password"}
            </Button>
          </div>
        </form>

        <section className="rounded-2xl bg-white p-6 shadow-card">
          <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700">
            <Lock className="h-6 w-6" />
          </div>
          <h2 className="mt-4 text-lg font-heading font-bold text-slate-900">Password Tips</h2>
          <div className="mt-4 space-y-3 text-sm text-slate-600">
            <p>Use at least 6 characters.</p>
            <p>Use a mix of letters, numbers, and symbols.</p>
            <p>Do not reuse passwords from other accounts.</p>
          </div>
        </section>
      </div>
    </StudentLayout>
  );
};

export default StudentChangePassword;
