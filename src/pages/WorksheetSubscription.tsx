import Navbar from "@/components/layout/Navbar";
import Footer from "@/components/layout/Footer";
import { Link } from "react-router-dom";
import { Button } from "@/components/ui/button";
import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from "@/components/ui/accordion";
import {
  CheckCircle2,
  ClipboardList,
  Download,
  FileText,
  GraduationCap,
  Layers,
  Laptop,
  Lock,
  ShieldCheck,
  Sparkles,
  Users,
} from "lucide-react";
import { placeholderImages } from "@/data/placeholderImages";

const images = {
  hero: placeholderImages.worksheetPractice,
  dashboard: placeholderImages.aboutHero,
};

const includes = [
  { icon: FileText, title: "Downloadable Worksheets", desc: "Printable PDF worksheets for daily practice." },
  { icon: Layers, title: "Level-wise Practice Sheets", desc: "Structured sets from basics to advanced." },
  { icon: ShieldCheck, title: "Answer Keys Included", desc: "Self-check with clear solutions." },
  { icon: Download, title: "Unlimited Practice Access", desc: "Repeat worksheets anytime for mastery." },
];

const audience = [
  "Students (Age 5–16)",
  "Beginners to advanced learners",
  "Homeschooling students",
  "Parents & teachers",
];

const selfPaced = [
  "No live classes",
  "Access anytime, anywhere",
  "Practice at your own speed",
  "Repeat worksheets anytime",
];

const plans = [
  {
    name: "Monthly Plan",
    price: "?499",
    features: ["30 days access", "Download worksheets", "Dashboard access"],
    highlight: false,
  },
  {
    name: "Yearly Plan",
    price: "?3999",
    features: ["365 days access", "All features included", "Best value"],
    highlight: true,
  },
];

const steps = [
  { title: "Choose your plan", desc: "Pick a plan that fits your routine." },
  { title: "Make payment", desc: "Secure checkout and instant confirmation." },
  { title: "Get login access", desc: "Receive your dashboard credentials." },
  { title: "Download & practice", desc: "Start learning immediately." },
];

const faqs = [
  { q: "Can I download worksheets?", a: "Yes, all worksheets are available as downloadable PDFs." },
  { q: "Is this suitable for beginners?", a: "Absolutely. Levels start from basics and build up gradually." },
  { q: "Do I get lifetime access?", a: "Access is based on your subscription period." },
  { q: "Can I renew my subscription?", a: "Yes, you can renew anytime to continue access." },
  { q: "Do I need internet?", a: "You only need internet to download; printed worksheets work offline." },
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
  icon: typeof FileText;
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
  price,
  features: list,
  highlight,
}: {
  name: string;
  price: string;
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
        Best Value
      </div>
    ) : null}
    <h3 className="text-xl font-semibold text-foreground mb-1">{name}</h3>
    <div className="text-3xl font-bold text-foreground mb-4">{price}</div>
    <ul className="space-y-2 mb-6">
      {list.map((f) => (
        <li key={f} className="flex items-start gap-2 text-sm text-foreground">
          <CheckCircle2 className="mt-0.5 h-4 w-4 text-success" />
          <span>{f}</span>
        </li>
      ))}
    </ul>
    <Button className="w-full bg-[#f97316] hover:bg-[#ea580c] text-white font-semibold" asChild>
      <Link to="/shop">Subscribe Now</Link>
    </Button>
  </div>
);

const WorksheetSubscription = () => (
  <div className="min-h-screen bg-background">
    <Navbar />
    <main className="pt-16">
      {/* Hero */}
      <section className="relative overflow-hidden bg-gradient-to-br from-[#1e3a8a] via-[#4c1d95] to-[#111827]">
        <div className="absolute inset-0 opacity-20 bg-[radial-gradient(circle_at_top,_rgba(255,255,255,0.35),_transparent_60%)]" />
        <div className="container mx-auto px-4 max-w-7xl py-20 md:py-28 relative z-10">
          <div className="grid lg:grid-cols-[1.1fr_0.9fr] gap-10 items-center">
            <div className="text-left text-white">
              <h1 className="text-4xl md:text-5xl lg:text-6xl font-heading font-bold leading-tight">
                Worksheet Subscription
              </h1>
              <p className="mt-4 text-lg md:text-xl text-white/80">
                Downloadable worksheets for self-paced learning.
              </p>
              <p className="mt-4 text-white/75 max-w-xl">
                Get access to high-quality printable worksheets designed to improve speed, accuracy, and confidence in
                maths.
              </p>
              <div className="mt-8">
                <Button size="lg" className="bg-[#f97316] hover:bg-[#ea580c] text-white font-semibold" asChild>
                  <Link to="/shop">Get Started</Link>
                </Button>
              </div>
            </div>
            <div className="relative">
              <div className="absolute inset-0 rounded-3xl bg-white/10 blur-2xl" />
              <img
                src={images.hero}
                alt="Students practicing worksheets"
                className="relative w-full rounded-3xl shadow-2xl object-cover aspect-[4/3]"
                loading="lazy"
              />
            </div>
          </div>
        </div>
      </section>

      {/* What You Get */}
      <Section className="bg-white">
        <div className="text-center max-w-3xl mx-auto mb-12">
          <h2 className="text-3xl md:text-4xl font-heading font-bold text-foreground mb-4">
            What’s Included in Subscription?
          </h2>
          <p className="text-muted-foreground text-lg">
            Everything needed for consistent daily practice at home.
          </p>
        </div>
        <div className="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
          {includes.map((item) => (
            <Card key={item.title} icon={item.icon} title={item.title} desc={item.desc} />
          ))}
        </div>
      </Section>

      {/* Who Is This For */}
      <Section className="bg-muted/40">
        <div className="grid lg:grid-cols-12 gap-10 items-start">
          <div className="lg:col-span-5">
            <h2 className="text-3xl md:text-4xl font-heading font-bold text-foreground mb-4">
              Who Can Use This?
            </h2>
            <p className="text-muted-foreground text-lg">
              Designed for students, parents, and educators who want structured practice.
            </p>
          </div>
          <div className="lg:col-span-7 grid sm:grid-cols-2 gap-6">
            {audience.map((item) => (
              <div key={item} className="rounded-2xl bg-white border border-border p-6 shadow-sm">
                <p className="text-base font-semibold text-foreground">{item}</p>
              </div>
            ))}
          </div>
        </div>
      </Section>

      {/* Self-Paced Learning */}
      <Section className="bg-white">
        <div className="grid md:grid-cols-12 gap-10 items-start">
          <div className="md:col-span-5">
            <div className="flex items-center gap-3 text-[#f97316] mb-3">
              <ClipboardList className="h-6 w-6" />
              <span className="text-sm font-semibold uppercase tracking-widest">Self-Paced Learning</span>
            </div>
            <h2 className="text-3xl md:text-4xl font-heading font-bold text-foreground mb-4">
              Learn at your own pace
            </h2>
            <p className="text-muted-foreground text-lg">
              Practice anytime without live classes and revisit worksheets whenever needed.
            </p>
          </div>
          <div className="md:col-span-7">
            <ul className="space-y-3">
              {selfPaced.map((item) => (
                <li key={item} className="flex items-start gap-3 text-base text-foreground">
                  <span className="mt-2 h-2 w-2 rounded-full bg-[#4c1d95]" />
                  <span>{item}</span>
                </li>
              ))}
            </ul>
          </div>
        </div>
      </Section>

      {/* Dashboard Access */}
      <Section className="bg-muted/40">
        <div className="grid lg:grid-cols-2 gap-10 items-center">
          <div>
            <div className="flex items-center gap-3 text-[#f97316] mb-3">
              <Lock className="h-6 w-6" />
              <span className="text-sm font-semibold uppercase tracking-widest">Student Dashboard</span>
            </div>
            <h2 className="text-3xl md:text-4xl font-heading font-bold text-foreground mb-4">
              Student Dashboard Access
            </h2>
            <p className="text-muted-foreground text-lg mb-4">
              Each student gets a secure login to access worksheets and track progress.
            </p>
            <div className="grid sm:grid-cols-2 gap-4">
              <div className="rounded-xl border border-border bg-white p-4">
                <div className="flex items-center gap-2 text-sm font-semibold">
                  <Laptop className="h-4 w-4 text-secondary" />
                  Access anywhere
                </div>
              </div>
              <div className="rounded-xl border border-border bg-white p-4">
                <div className="flex items-center gap-2 text-sm font-semibold">
                  <Users className="h-4 w-4 text-secondary" />
                  Personal login
                </div>
              </div>
              <div className="rounded-xl border border-border bg-white p-4">
                <div className="flex items-center gap-2 text-sm font-semibold">
                  <GraduationCap className="h-4 w-4 text-secondary" />
                  Progress tracking
                </div>
              </div>
              <div className="rounded-xl border border-border bg-white p-4">
                <div className="flex items-center gap-2 text-sm font-semibold">
                  <Sparkles className="h-4 w-4 text-secondary" />
                  Simple interface
                </div>
              </div>
            </div>
          </div>
          <div className="relative">
            <div className="absolute inset-0 rounded-3xl bg-white/60 blur-2xl" />
            <img
              src={images.dashboard}
              alt="Worksheet dashboard preview"
              className="relative w-full rounded-3xl shadow-2xl object-cover aspect-[4/3]"
              loading="lazy"
            />
          </div>
        </div>
      </Section>

      {/* Pricing */}
      <Section className="bg-white">
        <div className="text-center max-w-3xl mx-auto mb-12">
          <h2 className="text-3xl md:text-4xl font-heading font-bold text-foreground mb-4">
            Choose Your Plan
          </h2>
          <p className="text-muted-foreground text-lg">
            Simple pricing for consistent practice.
          </p>
        </div>
        <div className="grid md:grid-cols-2 gap-6 max-w-4xl mx-auto">
          {plans.map((p) => (
            <PricingCard key={p.name} name={p.name} price={p.price} features={p.features} highlight={p.highlight} />
          ))}
        </div>
      </Section>

      {/* How It Works */}
      <Section className="bg-muted/40">
        <div className="text-center max-w-3xl mx-auto mb-12">
          <h2 className="text-3xl md:text-4xl font-heading font-bold text-foreground mb-4">
            How It Works
          </h2>
          <p className="text-muted-foreground text-lg">
            Get started in just a few steps.
          </p>
        </div>
        <div className="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
          {steps.map((step, index) => (
            <div key={step.title} className="rounded-2xl border border-border bg-white p-6 shadow-sm">
              <div className="text-xs font-bold text-[#f97316] mb-2">Step {index + 1}</div>
              <h3 className="text-base font-semibold text-foreground mb-2">{step.title}</h3>
              <p className="text-sm text-muted-foreground">{step.desc}</p>
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
              Quick answers about worksheet access and usage.
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

      {/* Final CTA */}
      <section className="py-16 md:py-20 bg-gradient-to-r from-[#111827] via-[#1f2937] to-[#4c1d95]">
        <div className="container mx-auto px-4 max-w-6xl text-center text-white">
          <h2 className="text-3xl md:text-4xl font-heading font-bold mb-4">
            Start Practicing Today
          </h2>
          <p className="text-white/85 text-lg max-w-2xl mx-auto mb-6">
            Subscribe now and unlock daily worksheets for consistent improvement.
          </p>
          <Button size="lg" className="bg-[#f97316] hover:bg-[#ea580c] text-white font-semibold" asChild>
            <Link to="/shop">Subscribe Now</Link>
          </Button>
        </div>
      </section>
    </main>
    <Footer />
  </div>
);

export default WorksheetSubscription;
