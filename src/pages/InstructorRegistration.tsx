import { useState } from "react";
import Navbar from "@/components/layout/Navbar";
import Footer from "@/components/layout/Footer";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Checkbox } from "@/components/ui/checkbox";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { RefreshCcw } from "lucide-react";
import { Link, useNavigate } from "react-router-dom";
import { toast } from "sonner";
import { register } from "@/lib/auth";
import { getApiBase } from "@/lib/apiBase";

const buildCaptcha = () => {
  const chars = "ABCDEFGHJKLMNPQRSTUVWXYZ23456789";
  let result = "";
  for (let i = 0; i < 6; i += 1) {
    result += chars[Math.floor(Math.random() * chars.length)];
  }
  return result;
};

const InstructorRegistration = () => {
  const [captcha, setCaptcha] = useState(buildCaptcha());
  const [captchaInput, setCaptchaInput] = useState("");
  const [address, setAddress] = useState("Gachibowli, Hyderabad");
  const [fullName, setFullName] = useState("");
  const [email, setEmail] = useState("");
  const [mobile, setMobile] = useState("");
  const [gender, setGender] = useState("");
  const [dob, setDob] = useState("");
  const [qualification, setQualification] = useState("");
  const [programs, setPrograms] = useState({ abacus: true, vedic: false });
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [password, setPassword] = useState("");
  const [confirmPassword, setConfirmPassword] = useState("");
  const API_BASE = getApiBase();
  const navigate = useNavigate();

  const refreshCaptcha = () => {
    setCaptcha(buildCaptcha());
    setCaptchaInput("");
  };

  const handleSubmit = async (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    if (!password.trim() || password.length < 6) {
      toast.error("Please enter a password with at least 6 characters.");
      return;
    }
    if (password !== confirmPassword) {
      toast.error("Passwords do not match.");
      return;
    }
    if (!captchaInput.trim()) {
      toast.error("Please enter the captcha code.");
      return;
    }
    if (captchaInput.trim().toUpperCase() !== captcha) {
      toast.error("Captcha code does not match. Please try again.");
      refreshCaptcha();
      return;
    }

    setIsSubmitting(true);
    try {
      const selectedPrograms = [
        programs.abacus ? "Abacus" : null,
        programs.vedic ? "Vedic Maths" : null,
      ].filter(Boolean);

      await register(fullName.trim(), email.trim(), password, "tutor");

      const response = await fetch(`${API_BASE}/api/instructor/apply`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          name: fullName,
          email,
          mobile,
          gender,
          dob,
          qualification,
          address,
          programs: selectedPrograms,
        }),
      });

      if (!response.ok) {
        const data = await response.json().catch(() => ({}));
        throw new Error(data?.message || "Request failed");
      }

      toast.success("Registration submitted. Please login.");
      setFullName("");
      setEmail("");
      setMobile("");
      setGender("");
      setDob("");
      setQualification("");
      setPrograms({ abacus: true, vedic: false });
      setPassword("");
      setConfirmPassword("");
      refreshCaptcha();
      navigate("/instructor-login");
    } catch (error) {
      const message = error instanceof Error ? error.message : "Unable to submit. Please try again.";
      toast.error(message);
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
            <div className="mb-8">
              <h1 className="text-3xl md:text-4xl font-heading font-bold text-foreground">Instructor Registration</h1>
              <p className="mt-2 text-muted-foreground">ABACUS Register As Instructor</p>
            </div>

            <div className="max-w-4xl rounded-2xl border border-border bg-white shadow-card overflow-hidden">
              <div className="p-6 md:p-8">
                <form onSubmit={handleSubmit}>
                <div className="flex flex-wrap items-center gap-6 mb-6">
                  <div className="flex items-center gap-3">
                    <Checkbox
                      id="program-abacus"
                      checked={programs.abacus}
                      onCheckedChange={(value) => setPrograms((prev) => ({ ...prev, abacus: Boolean(value) }))}
                    />
                    <Label htmlFor="program-abacus" className="font-semibold">Abacus</Label>
                  </div>
                  <div className="flex items-center gap-3">
                    <Checkbox
                      id="program-vedic"
                      checked={programs.vedic}
                      onCheckedChange={(value) => setPrograms((prev) => ({ ...prev, vedic: Boolean(value) }))}
                    />
                    <Label htmlFor="program-vedic" className="font-semibold">Vedic Maths</Label>
                  </div>
                </div>

                <div className="grid gap-6 md:grid-cols-2">
                  <div className="space-y-2">
                    <Label htmlFor="full-name">Full Name</Label>
                    <Input
                      id="full-name"
                      placeholder="Full Name"
                      value={fullName}
                      onChange={(event) => setFullName(event.target.value)}
                      required
                    />
                  </div>
                  <div className="space-y-2">
                    <Label htmlFor="email">Email</Label>
                    <Input
                      id="email"
                      type="email"
                      placeholder="Email"
                      value={email}
                      onChange={(event) => setEmail(event.target.value)}
                      required
                    />
                  </div>
                  <div className="space-y-2">
                    <Label>Mobile</Label>
                    <div className="flex gap-2">
                      <Select defaultValue="+91">
                        <SelectTrigger className="w-28">
                          <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectItem value="+91">IN +91</SelectItem>
                          <SelectItem value="+1">US +1</SelectItem>
                          <SelectItem value="+44">UK +44</SelectItem>
                          <SelectItem value="+971">UAE +971</SelectItem>
                        </SelectContent>
                      </Select>
                      <Input
                        placeholder="Mobile"
                        value={mobile}
                        onChange={(event) => setMobile(event.target.value)}
                        required
                      />
                    </div>
                  </div>
                  <div className="space-y-2">
                    <Label>Gender</Label>
                    <Select value={gender} onValueChange={setGender}>
                      <SelectTrigger>
                        <SelectValue placeholder="Select Gender" />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="male">Male</SelectItem>
                        <SelectItem value="female">Female</SelectItem>
                        <SelectItem value="other">Other</SelectItem>
                      </SelectContent>
                    </Select>
                  </div>
                  <div className="space-y-2">
                    <Label>Date of Birth</Label>
                    <Input type="date" value={dob} onChange={(event) => setDob(event.target.value)} />
                  </div>
                  <div className="space-y-2">
                    <Label htmlFor="qualification">Qualification</Label>
                    <Input
                      id="qualification"
                      placeholder="Qualification"
                      value={qualification}
                      onChange={(event) => setQualification(event.target.value)}
                    />
                  </div>
                  <div className="space-y-2">
                    <Label htmlFor="password">Password</Label>
                    <Input
                      id="password"
                      type="password"
                      placeholder="Create Password"
                      value={password}
                      onChange={(event) => setPassword(event.target.value)}
                      required
                    />
                  </div>
                  <div className="space-y-2">
                    <Label htmlFor="confirm-password">Confirm Password</Label>
                    <Input
                      id="confirm-password"
                      type="password"
                      placeholder="Confirm Password"
                      value={confirmPassword}
                      onChange={(event) => setConfirmPassword(event.target.value)}
                      required
                    />
                  </div>
                  <div className="space-y-2 md:col-span-2">
                    <Label htmlFor="address">Address</Label>
                    <Input
                      id="address"
                      placeholder="Enter your full address"
                      value={address}
                      onChange={(event) => setAddress(event.target.value)}
                      required
                    />
                    <div className="mt-3 overflow-hidden rounded-xl border border-border">
                      <iframe
                        title="Location Map"
                        src={`https://www.google.com/maps?q=${encodeURIComponent(address || "Gachibowli, Hyderabad")}&output=embed`}
                        className="h-64 w-full"
                        loading="lazy"
                        referrerPolicy="no-referrer-when-downgrade"
                      />
                    </div>
                  </div>

                  <div className="md:col-span-2 grid gap-4 md:grid-cols-[240px_1fr] items-center">
                    <div className="flex items-center gap-3">
                      <div className="flex-1 rounded-full border border-border bg-muted px-4 py-2 text-center text-sm font-semibold text-primary">
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
                      onPaste={(event) => event.preventDefault()}
                      onDrop={(event) => event.preventDefault()}
                      autoComplete="off"
                      required
                    />
                  </div>
                </div>

                <div className="mt-6 flex flex-wrap items-center justify-between gap-4">
                  <p className="text-sm text-muted-foreground">
                    If you are already registered, please {" "}
                    <Link to="/instructor-login" className="text-primary font-semibold hover:underline">
                      click here
                    </Link>
                    {" "}to login
                  </p>
                  <Button variant="hero" type="submit" disabled={isSubmitting}>Registration</Button>
                </div>
                </form>
              </div>
            </div>
          </div>
        </section>
      </main>
      <Footer />
    </div>
  );
};

export default InstructorRegistration;
