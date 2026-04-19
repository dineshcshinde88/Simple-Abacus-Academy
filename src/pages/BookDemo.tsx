import { useMemo, useState } from "react";
import { motion } from "framer-motion";
import { RefreshCcw } from "lucide-react";
import { toast } from "sonner";
import Navbar from "@/components/layout/Navbar";
import Footer from "@/components/layout/Footer";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Checkbox } from "@/components/ui/checkbox";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import LoginDialog from "@/components/auth/LoginDialog";
import { getApiBase } from "@/lib/apiBase";

const fadeUp = { initial: { opacity: 0, y: 20 }, whileInView: { opacity: 1, y: 0 }, viewport: { once: true } };

const buildCaptcha = () => {
  const chars = "ABCDEFGHJKLMNPQRSTUVWXYZ23456789";
  let result = "";
  for (let i = 0; i < 6; i += 1) {
    result += chars[Math.floor(Math.random() * chars.length)];
  }
  return result;
};

const BookDemo = () => {
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [captcha, setCaptcha] = useState(buildCaptcha());
  const [captchaInput, setCaptchaInput] = useState("");
  const [loginOpen, setLoginOpen] = useState(false);
  const [selectedPrograms, setSelectedPrograms] = useState({ abacus: true, vedic: false });
  const [fullName, setFullName] = useState("");
  const [email, setEmail] = useState("");
  const [mobile, setMobile] = useState("");
  const [gender, setGender] = useState("");
  const [motherTongue, setMotherTongue] = useState("");
  const [dob, setDob] = useState("");

  const API_BASE = getApiBase();

  const programSummary = useMemo(() => {
    const chosen = [] as string[];
    if (selectedPrograms.abacus) chosen.push("Abacus");
    if (selectedPrograms.vedic) chosen.push("Vedic Maths");
    return chosen.length ? chosen.join(" & ") : "Select program";
  }, [selectedPrograms]);

  const refreshCaptcha = () => {
    setCaptcha(buildCaptcha());
    setCaptchaInput("");
  };

  const handleSubmit = async (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault();
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
      const programs = [
        selectedPrograms.abacus ? "Abacus" : null,
        selectedPrograms.vedic ? "Vedic Maths" : null,
      ].filter(Boolean);

      const response = await fetch(`${API_BASE}/api/demo/book`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          name: fullName,
          email,
          mobile,
          gender,
          motherTongue,
          dob,
          programs,
        }),
      });

      if (!response.ok) {
        throw new Error("Request failed");
      }

      toast.success("Thanks! We'll reach out to confirm your free demo session.");
      setFullName("");
      setEmail("");
      setMobile("");
      setGender("");
      setMotherTongue("");
      setDob("");
      refreshCaptcha();
      setSelectedPrograms({ abacus: true, vedic: false });
    } catch {
      toast.error("Unable to submit. Please try again.");
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
            <motion.div {...fadeUp} className="max-w-5xl mx-auto">
              <div className="bg-card border border-border shadow-card rounded-3xl p-6 md:p-10">
                <form onSubmit={handleSubmit} className="space-y-8">
                  <div className="flex flex-wrap items-center justify-center gap-6">
                    <div className="flex items-center gap-3">
                      <Checkbox
                        id="program-abacus"
                        checked={selectedPrograms.abacus}
                        onCheckedChange={(value) => setSelectedPrograms((prev) => ({ ...prev, abacus: Boolean(value) }))}
                      />
                      <Label htmlFor="program-abacus" className="text-sm font-medium">Abacus</Label>
                    </div>
                    <div className="flex items-center gap-3">
                      <Checkbox
                        id="program-vedic"
                        checked={selectedPrograms.vedic}
                        onCheckedChange={(value) => setSelectedPrograms((prev) => ({ ...prev, vedic: Boolean(value) }))}
                      />
                      <Label htmlFor="program-vedic" className="text-sm font-medium">Vedic Maths</Label>
                    </div>
                    <span className="text-xs text-muted-foreground">Selected: {programSummary}</span>
                  </div>

                  <div className="grid md:grid-cols-3 gap-6">
                    <div className="space-y-2">
                      <Label htmlFor="full-name">Full Name</Label>
                      <Input
                        id="full-name"
                        placeholder="Full name"
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
                      <Label htmlFor="mobile">Mobile Number</Label>
                      <div className="flex gap-2">
                        <Select defaultValue="+91">
                          <SelectTrigger className="w-28">
                            <SelectValue placeholder="Code" />
                          </SelectTrigger>
                          <SelectContent>
                            <SelectItem value="+91">IN +91</SelectItem>
                            <SelectItem value="+1">US +1</SelectItem>
                            <SelectItem value="+44">UK +44</SelectItem>
                            <SelectItem value="+971">UAE +971</SelectItem>
                          </SelectContent>
                        </Select>
                        <Input
                          id="mobile"
                          type="tel"
                          placeholder="Mobile Number"
                          value={mobile}
                          onChange={(event) => setMobile(event.target.value)}
                          required
                        />
                      </div>
                    </div>
                  </div>

                  <div className="grid md:grid-cols-3 gap-6">
                    <div className="space-y-2">
                      <Label htmlFor="gender">Gender</Label>
                      <Select value={gender} onValueChange={setGender}>
                        <SelectTrigger id="gender">
                          <SelectValue placeholder="Select Gender" />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectItem value="female">Female</SelectItem>
                          <SelectItem value="male">Male</SelectItem>
                          <SelectItem value="other">Other</SelectItem>
                          <SelectItem value="prefer-not">Prefer not to say</SelectItem>
                        </SelectContent>
                      </Select>
                    </div>
                    <div className="space-y-2">
                      <Label htmlFor="mother-tongue">Mother Tongue</Label>
                      <Input
                        id="mother-tongue"
                        placeholder="Mother Tongue"
                        value={motherTongue}
                        onChange={(event) => setMotherTongue(event.target.value)}
                      />
                    </div>
                    <div className="space-y-2">
                      <Label htmlFor="dob">Date Of Birth</Label>
                      <Input id="dob" type="date" value={dob} onChange={(event) => setDob(event.target.value)} />
                    </div>
                  </div>

                  <div className="grid lg:grid-cols-[280px_1fr] gap-4 items-center">
                    <div className="flex items-center gap-3">
                      <div className="flex-1 rounded-full border border-border bg-muted px-4 py-3 text-center text-xl font-semibold tracking-widest text-primary">
                        {captcha}
                      </div>
                      <Button
                        type="button"
                        variant="outline"
                        className="rounded-full h-11 w-11 p-0"
                        onClick={refreshCaptcha}
                        aria-label="Refresh captcha"
                      >
                        <RefreshCcw className="w-5 h-5" />
                      </Button>
                    </div>
                    <div className="space-y-2">
                      <Label htmlFor="captcha">Enter Captcha Code</Label>
                      <Input
                        id="captcha"
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

                  <div className="flex flex-col sm:flex-row items-center justify-between gap-4">
                    <Button type="submit" variant="hero" size="lg" disabled={isSubmitting}>
                      {isSubmitting ? "Submitting..." : "Book Free Demo"}
                    </Button>
                  </div>
                </form>
              </div>
            </motion.div>
          </div>
        </section>
      </main>
      <Footer />
      <LoginDialog open={loginOpen} onOpenChange={setLoginOpen} />
    </div>
  );
};

export default BookDemo;
