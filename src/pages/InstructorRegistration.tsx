import { useState } from "react";
import { Link, useNavigate } from "react-router-dom";
import Navbar from "@/components/layout/Navbar";
import Footer from "@/components/layout/Footer";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { toast } from "sonner";
import { ApiError, startInstructorRegistration } from "@/services/instructorAuthApi";

const InstructorRegistration = () => {
  const navigate = useNavigate();
  const [form, setForm] = useState({ fullName: "", mobile: "", email: "" });
  const [isSubmitting, setIsSubmitting] = useState(false);

  const updateField = (field: keyof typeof form, value: string) => {
    setForm((prev) => ({ ...prev, [field]: value }));
  };

  const handleSubmit = async (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    if (!form.fullName.trim() || !form.mobile.trim() || !form.email.trim()) {
      toast.error("Please fill all fields.");
      return;
    }

    try {
      setIsSubmitting(true);
      const response = await startInstructorRegistration({
        fullName: form.fullName.trim(),
        mobile: form.mobile.trim(),
        email: form.email.trim(),
      });
      toast.success(response.message || "OTP sent to your email.");
      navigate("/instructor-verify-otp", {
        state: {
          email: response.email,
          fullName: form.fullName.trim(),
          devOtp: response.devOtp,
        },
      });
    } catch (error) {
      if (error instanceof ApiError && error.status === 429) {
        const email = form.email.trim();
        sessionStorage.setItem("instructor_otp_email", email);
        toast.info("OTP already sent. Please enter it to continue.");
        navigate("/instructor-verify-otp", {
          state: {
            email,
            fullName: form.fullName.trim(),
          },
        });
        return;
      }
      toast.error(error instanceof Error ? error.message : "Unable to start registration.");
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
                <p className="mt-2 text-sm text-muted-foreground">Verify your email before creating your password.</p>
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
                <Button type="submit" className="w-full bg-[#4B1E83] hover:bg-[#3c176a]" disabled={isSubmitting}>
                  {isSubmitting ? "Sending OTP..." : "Register & Send OTP"}
                </Button>
              </form>

              <p className="mt-6 text-center text-sm text-muted-foreground">
                Already verified?{" "}
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
