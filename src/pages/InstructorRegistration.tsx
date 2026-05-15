import { useState } from "react";
import { Link, useNavigate } from "react-router-dom";
import Navbar from "@/components/layout/Navbar";
import Footer from "@/components/layout/Footer";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { toast } from "sonner";
import { registerInstructor } from "@/services/instructorAuthApi";

const InstructorRegistration = () => {
  const navigate = useNavigate();
  const [form, setForm] = useState({ fullName: "", mobile: "", email: "", password: "", confirmPassword: "" });
  const [isSubmitting, setIsSubmitting] = useState(false);

  const updateField = (field: keyof typeof form, value: string) => {
    setForm((prev) => ({ ...prev, [field]: value }));
  };

  const handleSubmit = async (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    if (!form.fullName.trim() || !form.mobile.trim() || !form.email.trim() || !form.password || !form.confirmPassword) {
      toast.error("Please fill all fields.");
      return;
    }
    if (form.password !== form.confirmPassword) {
      toast.error("Passwords do not match.");
      return;
    }

    try {
      setIsSubmitting(true);
      const response = await registerInstructor({
        fullName: form.fullName.trim(),
        mobile: form.mobile.trim(),
        email: form.email.trim(),
        password: form.password,
        confirmPassword: form.confirmPassword,
      });
      toast.success(response.message || "Registration submitted successfully. Wait for admin approval.");
      navigate("/instructor-login");
    } catch (error) {
      toast.error(error instanceof Error ? error.message : "Unable to submit registration.");
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
                <h1 className="mt-6 text-3xl font-heading font-bold text-[#4B1E83]">Instructor Registration</h1>
                <p className="mt-2 text-sm text-muted-foreground">Create your instructor account and wait for admin approval.</p>
              </div>

              <form onSubmit={handleSubmit} className="mt-8 space-y-5">
                <div className="space-y-2">
                  <Label htmlFor="fullName">Full Name</Label>
                  <Input
                    id="fullName"
                    placeholder="Enter full name"
                    value={form.fullName}
                    onChange={(event) => updateField("fullName", event.target.value)}
                    required
                  />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="mobile">Mobile Number</Label>
                  <Input
                    id="mobile"
                    placeholder="Enter mobile number"
                    value={form.mobile}
                    onChange={(event) => updateField("mobile", event.target.value)}
                    required
                  />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="email">Email ID</Label>
                  <Input
                    id="email"
                    type="email"
                    placeholder="Enter email"
                    value={form.email}
                    onChange={(event) => updateField("email", event.target.value)}
                    required
                  />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="password">Password</Label>
                  <Input
                    id="password"
                    type="password"
                    placeholder="Create password"
                    value={form.password}
                    onChange={(event) => updateField("password", event.target.value)}
                    required
                  />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="confirmPassword">Confirm Password</Label>
                  <Input
                    id="confirmPassword"
                    type="password"
                    placeholder="Confirm password"
                    value={form.confirmPassword}
                    onChange={(event) => updateField("confirmPassword", event.target.value)}
                    required
                  />
                </div>
                <Button type="submit" className="w-full bg-[#4B1E83] hover:bg-[#3c176a]" disabled={isSubmitting}>
                  {isSubmitting ? "Submitting..." : "Submit Registration"}
                </Button>
              </form>

              <p className="mt-6 text-center text-sm text-muted-foreground">
                Already registered?{" "}
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

export default InstructorRegistration;
