import Navbar from "@/components/layout/Navbar";
import Footer from "@/components/layout/Footer";
import { Link } from "react-router-dom";
import { Button } from "@/components/ui/button";
import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from "@/components/ui/accordion";
import { BookOpen, Brain, ChartLine, Clock, GraduationCap, Layers, Sparkles, Target } from "lucide-react";
import { placeholderImages } from "@/data/placeholderImages";

const hero = {
  title: "Online Vedic Maths Classes\nfor fast, accurate calculations",
  subtitle: "Boost confidence, sharpen focus, and build math speed with simple techniques.",
  cta: "Book Free Demo",
};

const intro = [
  "Vedic Maths is based on ancient Indian sutras that simplify complex calculations using quick, logical methods.",
  "Students learn easy tricks that make arithmetic faster and more accurate while keeping learning fun and stress-free.",
];

const benefits = [
  "Faster calculations with smart shortcuts",
  "Improved concentration and focus",
  "Better memory through consistent practice",
  "Increased confidence in math",
  "Stronger exam performance",
];

const highlights = [
  { icon: Sparkles, title: "Easy Tricks & Shortcuts", desc: "Learn simple methods to solve problems in seconds." },
  { icon: Clock, title: "Weekly Live Classes", desc: "Interactive sessions with guided practice every week." },
  { icon: BookOpen, title: "Practice Assignments", desc: "Worksheets and drills to reinforce every concept." },
  { icon: ChartLine, title: "Progress Tracking", desc: "Track speed and accuracy improvements over time." },
  { icon: Brain, title: "Mental Math Focus", desc: "Train the brain to calculate without dependency on tools." },
  { icon: Target, title: "Exam-Ready Skills", desc: "Build speed techniques that support school exams." },
];

const learnTopics = [
  "Addition and subtraction tricks",
  "Multiplication shortcuts",
  "Division methods",
  "Square and cube roots",
  "Mental calculation techniques",
];

const structure = [
  { label: "Levels", value: "4 Levels" },
  { label: "Duration", value: "3 months per level" },
  { label: "Mode", value: "Online" },
  { label: "Practice Materials", value: "Worksheets + drills" },
];

const steps = [
  { title: "Register", desc: "Submit your details and choose a batch." },
  { title: "Join Classes", desc: "Attend live sessions with step-by-step guidance." },
  { title: "Practice Regularly", desc: "Use worksheets and drills after class." },
  { title: "Improve Speed", desc: "Build faster calculation habits." },
  { title: "Get Results", desc: "See confidence and accuracy grow." },
];

const faqs = [
  { q: "What is Vedic Maths?", a: "It is a system of quick calculation methods based on ancient Indian sutras." },
  { q: "What age group can join?", a: "Generally suitable for students aged 8 and above." },
  { q: "How long is the course?", a: "The program runs in levels, typically three months each." },
  { q: "Is the course fully online?", a: "Yes, classes are conducted online with live instruction." },
  { q: "Will it help in exams?", a: "Yes, it improves speed and accuracy which benefits exam performance." },
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
  icon: typeof Sparkles;
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

const VedicMathsClasses = () => (
  <div className="min-h-screen bg-background">
    <Navbar />
    <main className="pt-16">
      {/* Hero */}
      <section className="relative overflow-hidden bg-gradient-to-br from-[#0f172a] via-[#1e3a8a] to-[#4c1d95]">
        <div className="absolute inset-0 opacity-20 bg-[radial-gradient(circle_at_top,_rgba(255,255,255,0.35),_transparent_60%)]" />
        <div className="container mx-auto px-4 max-w-7xl py-20 md:py-28 relative z-10">
          <div className="grid lg:grid-cols-[1.1fr_0.9fr] gap-10 items-center">
            <div className="text-left">
              <h1 className="text-4xl md:text-5xl lg:text-6xl font-heading font-bold text-white leading-tight whitespace-pre-line">
                {hero.title}
              </h1>
              <p className="mt-5 text-lg md:text-xl text-white/80">{hero.subtitle}</p>
              <div className="mt-8">
                <Button size="lg" className="bg-[#f97316] hover:bg-[#ea580c] text-white font-semibold" asChild>
                  <Link to="/book-demo">{hero.cta}</Link>
                </Button>
              </div>
            </div>
            <div className="relative">
              <div className="absolute inset-0 rounded-3xl bg-white/10 blur-2xl" />
              <img
                src={placeholderImages.vedicMathsHero}
                alt="Student practicing Vedic Maths"
                className="relative w-full rounded-3xl shadow-2xl object-cover aspect-[4/3]"
                loading="lazy"
              />
            </div>
          </div>
        </div>
      </section>

      {/* Introduction */}
      <Section className="bg-white">
        <div className="max-w-4xl">
          <h2 className="text-3xl md:text-4xl font-heading font-bold text-foreground mb-5">
            Learn Vedic Maths the smart way
          </h2>
          <div className="space-y-4 text-base md:text-lg text-muted-foreground leading-relaxed">
            {intro.map((p) => (
              <p key={p}>{p}</p>
            ))}
          </div>
        </div>
      </Section>

      {/* Benefits */}
      <Section className="bg-muted/40">
        <div className="grid lg:grid-cols-12 gap-10 items-start">
          <div className="lg:col-span-5">
            <h2 className="text-3xl md:text-4xl font-heading font-bold text-foreground mb-4">
              Benefits for students
            </h2>
            <p className="text-muted-foreground text-lg leading-relaxed">
              Build speed, accuracy, and confidence with a structured learning approach.
            </p>
          </div>
          <div className="lg:col-span-7 grid sm:grid-cols-2 gap-6">
            {benefits.map((b) => (
              <div key={b} className="rounded-2xl bg-white border border-border p-6 shadow-sm">
                <p className="text-base font-semibold text-foreground">{b}</p>
              </div>
            ))}
          </div>
        </div>
      </Section>

      {/* Course Highlights */}
      <Section>
        <div className="text-center max-w-3xl mx-auto mb-12">
          <h2 className="text-3xl md:text-4xl font-heading font-bold text-foreground mb-4">
            Course highlights
          </h2>
          <p className="text-muted-foreground text-lg">
            Interactive lessons, guided practice, and measurable progress.
          </p>
        </div>
        <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
          {highlights.map((h) => (
            <Card key={h.title} icon={h.icon} title={h.title} desc={h.desc} />
          ))}
        </div>
      </Section>

      {/* What Students Will Learn */}
      <Section className="bg-white">
        <div className="grid md:grid-cols-12 gap-10 items-start">
          <div className="md:col-span-5">
            <div className="flex items-center gap-3 text-[#f97316] mb-3">
              <GraduationCap className="h-6 w-6" />
              <span className="text-sm font-semibold uppercase tracking-widest">Learning Outcomes</span>
            </div>
            <h2 className="text-3xl md:text-4xl font-heading font-bold text-foreground mb-4">
              What students will learn
            </h2>
            <p className="text-muted-foreground text-lg">
              Core techniques that cover essential operations and mental math skills.
            </p>
          </div>
          <div className="md:col-span-7">
            <ul className="space-y-3">
              {learnTopics.map((t) => (
                <li key={t} className="flex items-start gap-3 text-base text-foreground">
                  <span className="mt-2 h-2 w-2 rounded-full bg-[#4c1d95]" />
                  <span>{t}</span>
                </li>
              ))}
            </ul>
          </div>
        </div>
      </Section>

      {/* Course Structure */}
      <Section className="bg-muted/40">
        <div className="text-center max-w-3xl mx-auto mb-12">
          <h2 className="text-3xl md:text-4xl font-heading font-bold text-foreground mb-4">
            Course structure
          </h2>
          <p className="text-muted-foreground text-lg">A clear format for steady learning progress.</p>
        </div>
        <div className="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
          {structure.map((s) => (
            <div key={s.label} className="rounded-2xl border border-border bg-white p-6 shadow-sm text-center">
              <div className="text-sm uppercase tracking-wider text-muted-foreground">{s.label}</div>
              <div className="text-lg font-semibold text-foreground mt-2">{s.value}</div>
            </div>
          ))}
        </div>
      </Section>

      {/* How it Works */}
      <Section>
        <div className="text-center max-w-3xl mx-auto mb-12">
          <h2 className="text-3xl md:text-4xl font-heading font-bold text-foreground mb-4">
            How it works
          </h2>
          <p className="text-muted-foreground text-lg">A simple journey from registration to results.</p>
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

      {/* CTA */}
      <section className="py-16 md:py-20 bg-gradient-to-r from-[#f97316] via-[#fb923c] to-[#facc15]">
        <div className="container mx-auto px-4 max-w-6xl text-center">
          <h2 className="text-3xl md:text-4xl font-heading font-bold text-white mb-4">
            Boost your child’s math skills today
          </h2>
          <p className="text-white/90 text-lg max-w-2xl mx-auto mb-6">
            Start with a free demo and see the improvement in speed and confidence.
          </p>
          <Button size="lg" className="bg-white text-[#b45309] hover:bg-white/90 font-semibold" asChild>
            <Link to="/book-demo">Enroll Now</Link>
          </Button>
        </div>
      </section>

      {/* FAQ */}
      <Section className="bg-white">
        <div className="max-w-4xl mx-auto">
          <div className="text-center mb-10">
            <h2 className="text-3xl md:text-4xl font-heading font-bold text-foreground mb-3">
              Frequently Asked Questions
            </h2>
            <p className="text-muted-foreground text-lg">
              Answers to common questions about Vedic Maths classes.
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

export default VedicMathsClasses;
