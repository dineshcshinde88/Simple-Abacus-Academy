import { useState } from "react";
import { Eye, EyeOff, RefreshCcw } from "lucide-react";
import { Link } from "react-router-dom";
import Navbar from "@/components/layout/Navbar";
import Footer from "@/components/layout/Footer";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Checkbox } from "@/components/ui/checkbox";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { register } from "@/lib/auth";
import { useToast } from "@/hooks/use-toast";

const buildCaptcha = () => {
  const chars = "ABCDEFGHJKLMNPQRSTUVWXYZ23456789";
  let result = "";
  for (let i = 0; i < 6; i += 1) {
    result += chars[Math.floor(Math.random() * chars.length)];
  }
  return result;
};

const StudentRegistration = () => {
  const { toast } = useToast();
  const [captcha, setCaptcha] = useState(buildCaptcha());
  const [captchaInput, setCaptchaInput] = useState("");
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [showPassword, setShowPassword] = useState(false);

  const [form, setForm] = useState({
    fullName: "",
    email: "",
    phoneCountry: "+91",
    phone: "",
    gender: "",
    motherTongue: "",
    dob: "",
    password: "",
  });

  const refreshCaptcha = () => {
    setCaptcha(buildCaptcha());
    setCaptchaInput("");
  };

  const updateField = (field: keyof typeof form, value: string) => {
    setForm((prev) => ({ ...prev, [field]: value }));
  };

  const handleSubmit = async (event: React.FormEvent) => {
    event.preventDefault();

    if (!form.fullName.trim() || !form.email.trim() || !form.password.trim()) {
      toast({ title: "Missing details", description: "Name, email and password are required." });
      return;
    }

    if (captchaInput.trim().toLowerCase() !== captcha.toLowerCase()) {
      toast({ title: "Invalid captcha", description: "Please enter the correct captcha code." });
      return;
    }

    try {
      setIsSubmitting(true);
      await register(form.fullName.trim(), form.email.trim(), form.password.trim(), "student");
      toast({ title: "Registration successful", description: "You can now log in using your credentials." });
      setForm({
        fullName: "",
        email: "",
        phoneCountry: "+91",
        phone: "",
        gender: "",
        motherTongue: "",
        dob: "",
        password: "",
      });
      refreshCaptcha();
    } catch (error) {
      const message = error instanceof Error ? error.message : "Registration failed";
      toast({ title: "Registration failed", description: message });
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
            <form
              onSubmit={handleSubmit}
              className="max-w-6xl mx-auto rounded-2xl border border-border bg-white p-6 md:p-8 shadow-card"
            >
              <div className="flex flex-wrap items-center justify-center gap-10 mb-6">
                <div className="flex items-center gap-3">
                  <Checkbox id="student-abacus" defaultChecked />
                  <Label htmlFor="student-abacus" className="font-semibold">Abacus</Label>
                </div>
                <div className="flex items-center gap-3">
                  <Checkbox id="student-vedic" />
                  <Label htmlFor="student-vedic" className="font-semibold">Vedic Maths</Label>
                </div>
              </div>

              <div className="grid gap-6 md:grid-cols-3">
                <div className="space-y-2">
                  <Label htmlFor="full-name">Full Name</Label>
                  <Input
                    id="full-name"
                    placeholder="Full Name"
                    value={form.fullName}
                    onChange={(event) => updateField("fullName", event.target.value)}
                    className="h-12 rounded-full border-[#c7d2fe]"
                  />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="email">Email</Label>
                  <Input
                    id="email"
                    type="email"
                    placeholder="Email"
                    value={form.email}
                    onChange={(event) => updateField("email", event.target.value)}
                    className="h-12 rounded-full border-[#c7d2fe]"
                  />
                </div>
                <div className="space-y-2">
                  <Label>Mobile Number</Label>
                  <div className="flex gap-2">
                    <Select value={form.phoneCountry} onValueChange={(value) => updateField("phoneCountry", value)}>
                      <SelectTrigger className="w-28 rounded-full border-[#c7d2fe]">
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
                      placeholder="Mobile Number"
                      value={form.phone}
                      onChange={(event) => updateField("phone", event.target.value)}
                      className="h-12 rounded-full border-[#c7d2fe]"
                    />
                  </div>
                </div>
                <div className="space-y-2">
                  <Label>Gender</Label>
                  <Select value={form.gender} onValueChange={(value) => updateField("gender", value)}>
                    <SelectTrigger className="rounded-full border-[#c7d2fe]">
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
                  <Label>Mother Tongue</Label>
                  <Input
                    placeholder="Mother Tongue"
                    value={form.motherTongue}
                    onChange={(event) => updateField("motherTongue", event.target.value)}
                    className="h-12 rounded-full border-[#c7d2fe]"
                  />
                </div>
                <div className="space-y-2">
                  <Label>Date Of Birth</Label>
                  <Input
                    type="date"
                    value={form.dob}
                    onChange={(event) => updateField("dob", event.target.value)}
                    className="h-12 rounded-full border-[#c7d2fe]"
                  />
                </div>
                <div className="space-y-2 md:col-span-3">
                  <Label>Password</Label>
                  <div className="relative">
                    <Input
                      type={showPassword ? "text" : "password"}
                      placeholder="Create Password"
                      value={form.password}
                      onChange={(event) => updateField("password", event.target.value)}
                      className="h-12 rounded-full border-[#c7d2fe] pr-12"
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
                </div>
              </div>

              <div className="mt-8 grid gap-4 md:grid-cols-[320px_1fr] items-center">
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
                  className="h-12 rounded-full border-[#c7d2fe]"
                />
              </div>

              <div className="mt-8 flex flex-wrap items-center justify-between gap-4">
                <Button
                  type="submit"
                  className="bg-[#4B1E83] hover:bg-[#3c176a]"
                  disabled={isSubmitting}
                >
                  {isSubmitting ? "Submitting..." : "Registration"}
                </Button>
                <p className="text-sm text-muted-foreground">
                  If you are already registered, please {" "}
                  <Link to="/student-login" className="text-red-600 font-semibold hover:underline">click here</Link>
                  {" "}to login
                </p>
              </div>
            </form>
          </div>
        </section>
      </main>
      <Footer />
    </div>
  );
};

export default StudentRegistration;
