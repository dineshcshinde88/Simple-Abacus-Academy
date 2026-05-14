import { useEffect, useMemo, useState } from "react";
import { Link, useLocation, useNavigate } from "react-router-dom";
import Navbar from "@/components/layout/Navbar";
import Footer from "@/components/layout/Footer";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { toast } from "sonner";
import { ApiError, resendInstructorOtp, setInstructorPassword, verifyInstructorOtp } from "@/services/instructorAuthApi";

type LocationState = {
  email?: string;
  fullName?: string;
  devOtp?: string;
};

const InstructorOtpVerification = () => {
  const navigate = useNavigate();
  const location = useLocation();
  const state = (location.state || {}) as LocationState;
  const email = useMemo(() => state.email || sessionStorage.getItem("instructor_otp_email") || "", [state.email]);
  const [devOtp, setDevOtp] = useState(() => state.devOtp || sessionStorage.getItem("instructor_dev_otp") || "");
  const [otp, setOtp] = useState("");
  const [isVerified, setIsVerified] = useState(false);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [passwordForm, setPasswordForm] = useState({ password: "", confirmPassword: "" });

  useEffect(() => {
    if (state.email) sessionStorage.setItem("instructor_otp_email", state.email);
    if (state.devOtp) {
      sessionStorage.setItem("instructor_dev_otp", state.devOtp);
      setDevOtp(state.devOtp);
      setOtp(state.devOtp);
    }
  }, [state.email, state.devOtp]);

  const handleVerify = async (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    if (!email) {
      toast.error("Registration email not found. Please register again.");
      navigate("/instructor-registration");
      return;
    }
    if (!/^[0-9]{6}$/.test(otp.trim())) {
      toast.error("Please enter the 6-digit OTP.");
      return;
    }

    try {
      setIsSubmitting(true);
      const response = await verifyInstructorOtp({ email, otp: otp.trim() });
      toast.success(response.message || "Email verified.");
      setIsVerified(true);
    } catch (error) {
      if (error instanceof ApiError && error.status === 404) {
        try {
          await resendInstructorOtp({ email });
          setOtp("");
          toast.info("OTP was not found, so a fresh OTP has been sent to your email.");
        } catch (resendError) {
          toast.error(resendError instanceof Error ? resendError.message : "Please register again to request a new OTP.");
        }
        return;
      }
      toast.error(error instanceof Error ? error.message : "OTP verification failed.");
    } finally {
      setIsSubmitting(false);
    }
  };

  const handleResend = async () => {
    if (!email) {
      toast.error("Registration email not found. Please register again.");
      return;
    }
    try {
      setIsSubmitting(true);
      const response = await resendInstructorOtp({ email });
      toast.success(response.message || "OTP resent.");
      if (response.devOtp) {
        sessionStorage.setItem("instructor_dev_otp", response.devOtp);
        setDevOtp(response.devOtp);
        setOtp(response.devOtp);
      } else {
        sessionStorage.removeItem("instructor_dev_otp");
        setDevOtp("");
        setOtp("");
      }
    } catch (error) {
      toast.error(error instanceof Error ? error.message : "Unable to resend OTP.");
    } finally {
      setIsSubmitting(false);
    }
  };

  const handleSetPassword = async (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    if (passwordForm.password.length < 6) {
      toast.error("Password must be at least 6 characters.");
      return;
    }
    if (passwordForm.password !== passwordForm.confirmPassword) {
      toast.error("Passwords do not match.");
      return;
    }

    try {
      setIsSubmitting(true);
      const response = await setInstructorPassword({
        email,
        password: passwordForm.password,
        confirmPassword: passwordForm.confirmPassword,
      });
      toast.success(response.message || "Password set successfully.");
      sessionStorage.removeItem("instructor_otp_email");
      sessionStorage.removeItem("instructor_dev_otp");
      navigate("/instructor-login");
    } catch (error) {
      toast.error(error instanceof Error ? error.message : "Unable to set password.");
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
                <h1 className="mt-6 text-3xl font-heading font-bold text-[#4B1E83]">
                  {isVerified ? "Set Password" : "Verify Email OTP"}
                </h1>
                <p className="mt-2 text-sm text-muted-foreground">
                  {isVerified ? "Create your instructor login password." : `OTP sent to ${email || "your email"}. It expires in 5 minutes.`}
                </p>
                {!isVerified && devOtp ? (
                  <p className="mt-3 rounded-md bg-orange-50 px-3 py-2 text-sm font-semibold text-orange-700">
                    Development OTP: {devOtp}
                  </p>
                ) : null}
              </div>

              {!isVerified ? (
                <form onSubmit={handleVerify} className="mt-8 space-y-5">
                  <div className="space-y-2">
                    <Label htmlFor="otp">6-digit OTP</Label>
                    <Input
                      id="otp"
                      inputMode="numeric"
                      maxLength={6}
                      placeholder="Enter OTP"
                      value={otp}
                      onChange={(event) => setOtp(event.target.value.replace(/\D/g, "").slice(0, 6))}
                      className="text-center text-2xl font-semibold tracking-[0.5em]"
                      required
                    />
                  </div>
                  <Button type="submit" className="w-full bg-[#4B1E83] hover:bg-[#3c176a]" disabled={isSubmitting}>
                    {isSubmitting ? "Verifying..." : "Verify OTP"}
                  </Button>
                  <Button type="button" variant="outline" className="w-full" onClick={handleResend} disabled={isSubmitting}>
                    Resend OTP
                  </Button>
                </form>
              ) : (
                <form onSubmit={handleSetPassword} className="mt-8 space-y-5">
                  <div className="space-y-2">
                    <Label htmlFor="password">Password</Label>
                    <Input
                      id="password"
                      type="password"
                      placeholder="Create password"
                      value={passwordForm.password}
                      onChange={(event) => setPasswordForm((prev) => ({ ...prev, password: event.target.value }))}
                      required
                    />
                  </div>
                  <div className="space-y-2">
                    <Label htmlFor="confirmPassword">Confirm Password</Label>
                    <Input
                      id="confirmPassword"
                      type="password"
                      placeholder="Confirm password"
                      value={passwordForm.confirmPassword}
                      onChange={(event) => setPasswordForm((prev) => ({ ...prev, confirmPassword: event.target.value }))}
                      required
                    />
                  </div>
                  <Button type="submit" className="w-full bg-[#4B1E83] hover:bg-[#3c176a]" disabled={isSubmitting}>
                    {isSubmitting ? "Saving..." : "Set Password"}
                  </Button>
                </form>
              )}

              <p className="mt-6 text-center text-sm text-muted-foreground">
                Wrong email?{" "}
                <Link to="/instructor-registration" className="font-semibold text-orange-600 hover:underline">
                  Register again
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

export default InstructorOtpVerification;
