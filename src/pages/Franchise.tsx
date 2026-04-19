import { useState } from "react";
import { Link } from "react-router-dom";
import { toast } from "sonner";
import Navbar from "@/components/layout/Navbar";
import Footer from "@/components/layout/Footer";
import { Button } from "@/components/ui/button";
import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from "@/components/ui/accordion";
import { Award, Briefcase, ChartLine, Globe, Layers, Megaphone, RefreshCcw, ShieldCheck, Users } from "lucide-react";
import { placeholderImages } from "@/data/placeholderImages";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { getApiBase } from "@/lib/apiBase";

const hero = {
  title: "Start Your Own Education Franchise",
  subtitle: "Low investment. High income potential. A proven model you can run from home or a center.",
  cta: "Apply Now",
};

const intro = [
  "Our franchise program is built for teachers, homemakers, and entrepreneurs who want to launch a meaningful education business.",
  "Choose online, offline, or hybrid delivery and tap into a growing market for abacus and Vedic maths learning.",
];

const reasons = [
  "High demand for abacus and Vedic maths",
  "Proven business model with training support",
  "Flexible working model (online/offline/hybrid)",
  "Low investment with scalable growth",
];

const benefits = [
  { icon: Award, title: "Complete Training Support", desc: "Step-by-step training to start confidently." },
  { icon: Megaphone, title: "Marketing Assistance", desc: "Brand assets and campaigns to attract students." },
  { icon: Globe, title: "Digital Platform Access", desc: "Tools to manage classes and track progress." },
  { icon: Users, title: "Student Leads", desc: "Lead support to help you enroll faster." },
  { icon: ShieldCheck, title: "Lifetime Support", desc: "Ongoing help for growth and operations." },
  { icon: Layers, title: "Ready Curriculum", desc: "Structured content and worksheets included." },
];

const plans = [
  {
    name: "Individual Teacher",
    audience: "Home tutors and solo educators",
    investment: "₹50,000",
    income: "30K+/month",
    features: ["Starter training", "Digital materials", "Basic marketing kit", "Student tracking tools"],
    highlight: false,
  },
  {
    name: "Coaching Center",
    audience: "Small centers and local tutors",
    investment: "₹1,00,000",
    income: "60K+/month",
    features: ["Advanced training", "Lead support", "Marketing campaigns", "Monthly progress reports"],
    highlight: true,
  },
  {
    name: "Institutes",
    audience: "Schools and education institutes",
    investment: "₹2,00,000",
    income: "1L+/month",
    features: ["Team onboarding", "Bulk materials", "Co-branding options", "Priority support"],
    highlight: false,
  },
  {
    name: "Global Partner",
    audience: "Large operators and global expansion",
    investment: "₹4,00,000",
    income: "2L+/month",
    features: ["Multi-branch setup", "International support", "Dedicated manager", "Premium marketing"],
    highlight: false,
  },
];

const chooseUs = [
  "Trusted education brand with strong results",
  "Global presence and growing student base",
  "Training, marketing, and operational support",
  "Easy scalability with clear growth paths",
];

const roiPoints = [
  "Affordable startup cost",
  "Fast ROI potential",
  "Long-term business growth",
];

const steps = [
  { title: "Apply", desc: "Share your interest and get a franchise call." },
  { title: "Get Training", desc: "Complete training with curriculum and tools." },
  { title: "Setup Classes", desc: "Launch online, offline, or hybrid batches." },
  { title: "Enroll Students", desc: "Use marketing support to grow enrollments." },
  { title: "Start Earning", desc: "Generate steady revenue as your batches grow." },
];

const inclusions = [
  "Study materials and worksheets",
  "Certification and assessments",
  "Marketing support and creatives",
  "Student management tools",
  "Ongoing updates and guidance",
];

const faqs = [
  { q: "Do I need teaching experience?", a: "No. Complete training is provided so beginners can start confidently." },
  { q: "Can I run classes online?", a: "Yes, you can choose online, offline, or hybrid delivery." },
  { q: "What support is provided?", a: "You get training, marketing help, study materials, and ongoing guidance." },
  { q: "Can I upgrade my plan later?", a: "Yes, you can upgrade as your business grows." },
];

type SectionProps = {
  className?: string;
  children: React.ReactNode;
};

const Section = ({ className = "", children }: SectionProps) => (
  <section className={`py-16 md:py-20 ${className}`}>
    <div className="container mx-auto px-4 max-w-7xl">{children}</div>
  </section>
);

type CardProps = {
  icon: typeof Award;
  title: string;
  desc: string;
};

const Card = ({ icon: Icon, title, desc }: CardProps) => (
  <div className="rounded-2xl border border-border bg-white p-6 shadow-sm transition-transform duration-300 hover:-translate-y-1 hover:shadow-md">
    <div className="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-[#f97316]/10 text-[#f97316]">
      <Icon className="h-6 w-6" />
    </div>
    <h3 className="text-lg font-semibold text-foreground mb-2">{title}</h3>
    <p className="text-sm text-muted-foreground leading-relaxed">{desc}</p>
  </div>
);

const PricingCard = ({
  name,
  audience,
  investment,
  income,
  features,
  highlight,
}: {
  name: string;
  audience: string;
  investment: string;
  income: string;
  features: string[];
  highlight: boolean;
}) => (
  <div
    className={`rounded-2xl border p-6 shadow-sm bg-white transition-transform duration-300 hover:-translate-y-1 hover:shadow-md ${
      highlight ? "border-[#f97316] ring-2 ring-[#f97316]/30 scale-[1.02]" : "border-border"
    }`}
  >
    {highlight ? (
      <div className="mb-3 inline-flex items-center rounded-full bg-[#f97316]/10 px-3 py-1 text-xs font-semibold text-[#f97316]">
        Most Popular
      </div>
    ) : null}
    <h3 className="text-xl font-semibold text-foreground mb-1">{name}</h3>
    <p className="text-sm text-muted-foreground mb-4">{audience}</p>
    <div className="flex items-end gap-2 mb-4">
      <div className="text-2xl font-bold text-foreground">{investment}</div>
      <div className="text-sm text-muted-foreground">investment</div>
    </div>
    <div className="mb-4 text-sm text-muted-foreground">
      Estimated income: <span className="font-semibold text-foreground">{income}</span>
    </div>
    <ul className="space-y-2 mb-6">
      {features.map((f) => (
        <li key={f} className="flex items-start gap-2 text-sm text-foreground">
          <span className="mt-2 h-2 w-2 rounded-full bg-[#4c1d95]" />
          <span>{f}</span>
        </li>
      ))}
    </ul>
    <Button className="w-full bg-[#f97316] hover:bg-[#ea580c] text-white font-semibold" asChild>
      <Link to="/franchise#franchise-registration">Become a Partner</Link>
    </Button>
  </div>
);

const buildCaptcha = () => {
  const chars = "ABCDEFGHJKLMNPQRSTUVWXYZ23456789";
  let result = "";
  for (let i = 0; i < 6; i += 1) {
    result += chars[Math.floor(Math.random() * chars.length)];
  }
  return result;
};

const Franchise = () => {
  const [captcha, setCaptcha] = useState(buildCaptcha());
  const [captchaInput, setCaptchaInput] = useState("");
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [fullName, setFullName] = useState("");
  const [mobile, setMobile] = useState("");
  const [email, setEmail] = useState("");
  const [location, setLocation] = useState("");
  const [qualification, setQualification] = useState("");
  const [languages, setLanguages] = useState("");
  const [plan, setPlan] = useState("");
  const [message, setMessage] = useState("");
  const API_BASE = getApiBase();

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
      const response = await fetch(`${API_BASE}/api/franchise/apply`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          name: fullName,
          email,
          mobile,
          location,
          qualification,
          languages,
          plan,
          message,
        }),
      });

      if (!response.ok) throw new Error("Request failed");

      toast.success("Thanks! Your franchise application has been submitted.");
      setFullName("");
      setMobile("");
      setEmail("");
      setLocation("");
      setQualification("");
      setLanguages("");
      setPlan("");
      setMessage("");
      refreshCaptcha();
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
      {/* Hero */}
      <section className="relative overflow-hidden bg-gradient-to-br from-[#111827] via-[#1f2937] to-[#4c1d95]">
        <div className="absolute inset-0 opacity-20 bg-[radial-gradient(circle_at_top,_rgba(255,255,255,0.35),_transparent_60%)]" />
        <div className="container mx-auto px-4 max-w-7xl py-20 md:py-28 relative z-10">
          <div className="grid lg:grid-cols-[1.1fr_0.9fr] gap-10 items-center">
            <div className="text-left">
              <h1 className="text-4xl md:text-5xl lg:text-6xl font-heading font-bold text-white leading-tight">
                {hero.title}
              </h1>
              <p className="mt-5 text-lg md:text-xl text-white/80">{hero.subtitle}</p>
              <div className="mt-8">
                <Button size="lg" className="bg-[#f97316] hover:bg-[#ea580c] text-white font-semibold" asChild>
                  <Link to="/franchise#franchise-registration">{hero.cta}</Link>
                </Button>
              </div>
            </div>
            <div className="relative">
              <div className="absolute inset-0 rounded-3xl bg-white/10 blur-2xl" />
              <img
                src={placeholderImages.franchiseHero}
                alt="Business partners discussing franchise"
                className="relative w-full rounded-3xl shadow-2xl object-cover aspect-[4/3]"
                loading="lazy"
              />
            </div>
          </div>
        </div>
      </section>

      {/* Franchise Registration */}
      <Section className="bg-white">
            <div id="franchise-registration" className="max-w-5xl mx-auto">
          <div className="overflow-hidden rounded-2xl border border-border bg-white shadow-card">
            <div className="bg-[#4B1E83] px-6 py-4 text-center text-white text-2xl md:text-3xl font-heading font-bold">
              Become a Franchise Partner
            </div>
            <div className="p-6 md:p-8">
              <form onSubmit={handleSubmit} className="grid gap-6 md:grid-cols-2">
                <div className="space-y-2">
                  <Label htmlFor="franchise-name">Full Name</Label>
                  <Input
                    id="franchise-name"
                    placeholder="Enter your full name"
                    value={fullName}
                    onChange={(event) => setFullName(event.target.value)}
                    required
                  />
                </div>
                <div className="space-y-2">
                  <Label>Mobile Number</Label>
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
                      placeholder="Mobile Number"
                      value={mobile}
                      onChange={(event) => setMobile(event.target.value)}
                      required
                    />
                  </div>
                </div>
                <div className="space-y-2">
                  <Label htmlFor="franchise-email">Email Address</Label>
                  <Input
                    id="franchise-email"
                    type="email"
                    placeholder="Enter email address"
                    value={email}
                    onChange={(event) => setEmail(event.target.value)}
                    required
                  />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="franchise-location">Location</Label>
                  <Input
                    id="franchise-location"
                    placeholder="Enter your city or area"
                    value={location}
                    onChange={(event) => setLocation(event.target.value)}
                    required
                  />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="franchise-qualification">Educational Qualification</Label>
                  <Input
                    id="franchise-qualification"
                    placeholder="Enter highest qualification"
                    value={qualification}
                    onChange={(event) => setQualification(event.target.value)}
                    required
                  />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="franchise-languages">Spoken Languages</Label>
                  <Input
                    id="franchise-languages"
                    placeholder="Languages you can teach in"
                    value={languages}
                    onChange={(event) => setLanguages(event.target.value)}
                    required
                  />
                </div>
                <div className="space-y-2 md:col-span-2">
                  <Label>Select Franchise Plan</Label>
                  <Select value={plan} onValueChange={setPlan}>
                    <SelectTrigger>
                      <SelectValue placeholder="Select Plan" />
                    </SelectTrigger>
                    <SelectContent>
                      {plans.map((plan) => (
                        <SelectItem key={plan.name} value={plan.name}>{plan.name}</SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                </div>
                <div className="space-y-2 md:col-span-2">
                  <Label htmlFor="franchise-message">Message</Label>
                  <Textarea
                    id="franchise-message"
                    placeholder="Tell us about your background and goals"
                    rows={3}
                    value={message}
                    onChange={(event) => setMessage(event.target.value)}
                  />
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
              <div className="mt-6 md:col-span-2">
                <Button size="lg" type="submit" disabled={isSubmitting} className="bg-[#4B1E83] hover:bg-[#3c176a] text-white font-semibold">
                  Submit Application
                </Button>
              </div>
              </form>
            </div>
          </div>
        </div>
      </Section>

      {/* Introduction */}
      <Section className="bg-white">
        <div className="max-w-4xl">
          <h2 className="text-3xl md:text-4xl font-heading font-bold text-foreground mb-5">
            A franchise built for today’s education market
          </h2>
          <div className="space-y-4 text-base md:text-lg text-muted-foreground leading-relaxed">
            {intro.map((p) => (
              <p key={p}>{p}</p>
            ))}
          </div>
        </div>
      </Section>

      {/* Why Start */}
      <Section className="bg-muted/40">
        <div className="grid lg:grid-cols-12 gap-10 items-start">
          <div className="lg:col-span-5">
            <h2 className="text-3xl md:text-4xl font-heading font-bold text-foreground mb-4">
              Why start this franchise
            </h2>
            <p className="text-muted-foreground text-lg leading-relaxed">
              A practical business with proven demand and flexible operations.
            </p>
          </div>
          <div className="lg:col-span-7 grid sm:grid-cols-2 gap-6">
            {reasons.map((r) => (
              <div key={r} className="rounded-2xl bg-white border border-border p-6 shadow-sm">
                <p className="text-base font-semibold text-foreground">{r}</p>
              </div>
            ))}
          </div>
        </div>
      </Section>

      {/* Benefits */}
      <Section>
        <div className="text-center max-w-3xl mx-auto mb-12">
          <h2 className="text-3xl md:text-4xl font-heading font-bold text-foreground mb-4">
            Features and benefits
          </h2>
          <p className="text-muted-foreground text-lg">Everything you need to launch and grow.</p>
        </div>
        <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
          {benefits.map((b) => (
            <Card key={b.title} icon={b.icon} title={b.title} desc={b.desc} />
          ))}
        </div>
      </Section>

      {/* Why Choose Us */}
      <Section className="bg-muted/40">
        <div className="grid md:grid-cols-12 gap-10 items-start">
          <div className="md:col-span-5">
            <h2 className="text-3xl md:text-4xl font-heading font-bold text-foreground mb-4">
              Why choose us
            </h2>
            <p className="text-muted-foreground text-lg">
              A trusted partner with the tools to help you scale.
            </p>
          </div>
          <div className="md:col-span-7">
            <ul className="space-y-3">
              {chooseUs.map((c) => (
                <li key={c} className="flex items-start gap-3 text-base text-foreground">
                  <span className="mt-2 h-2 w-2 rounded-full bg-[#4c1d95]" />
                  <span>{c}</span>
                </li>
              ))}
            </ul>
          </div>
        </div>
      </Section>

      {/* Low investment */}
      <section className="py-16 md:py-20 bg-gradient-to-r from-[#4c1d95] via-[#7c3aed] to-[#f97316]">
        <div className="container mx-auto px-4 max-w-6xl text-white">
          <div className="grid md:grid-cols-2 gap-10 items-center">
            <div>
              <h2 className="text-3xl md:text-4xl font-heading font-bold mb-4">
                Low investment. High return.
              </h2>
              <p className="text-white/90 text-lg">
                Start with an affordable plan and grow into larger models as your batches expand.
              </p>
            </div>
            <ul className="space-y-3">
              {roiPoints.map((e) => (
                <li key={e} className="flex items-start gap-3">
                  <span className="mt-2 h-2 w-2 rounded-full bg-white" />
                  <span className="text-base">{e}</span>
                </li>
              ))}
            </ul>
          </div>
        </div>
      </section>

      {/* How it works */}
      <Section className="bg-white">
        <div className="text-center max-w-3xl mx-auto mb-12">
          <h2 className="text-3xl md:text-4xl font-heading font-bold text-foreground mb-4">
            How it works
          </h2>
          <p className="text-muted-foreground text-lg">A clear path from application to earnings.</p>
        </div>
        <div className="grid sm:grid-cols-2 lg:grid-cols-5 gap-6">
          {steps.map((s, i) => (
            <div key={s.title} className="rounded-2xl border border-border bg-white p-6 shadow-sm">
              <div className="text-xs font-bold text-[#f97316] mb-2">Step {i + 1}</div>
              <h3 className="text-base font-semibold text-foreground mb-2">{s.title}</h3>
              <p className="text-sm text-muted-foreground">{s.desc}</p>
            </div>
          ))}
        </div>
      </Section>

      {/* FAQ */}
      <Section className="bg-white">
        <div className="max-w-4xl mx-auto">
          <div className="text-center mb-10">
            <h2 className="text-3xl md:text-4xl font-heading font-bold text-foreground mb-3">
              Frequently Asked Questions
            </h2>
            <p className="text-muted-foreground text-lg">
              Quick answers for common franchise questions.
            </p>
          </div>
          <Accordion type="single" collapsible className="w-full">
            {faqs.map((item, idx) => (
              <AccordionItem key={item.q} value={`item-${idx}`}>
                <AccordionTrigger className="text-left text-base md:text-lg font-semibold text-foreground">
                  {item.q}
                </AccordionTrigger>
                <AccordionContent className="text-muted-foreground text-base leading-relaxed">
                  {item.a}
                </AccordionContent>
              </AccordionItem>
            ))}
          </Accordion>
        </div>
      </Section>

    </main>
    <Footer />
    </div>
  );
};

export default Franchise;
