import Navbar from "@/components/layout/Navbar";
import Footer from "@/components/layout/Footer";
import { Link } from "react-router-dom";
import { Button } from "@/components/ui/button";
import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from "@/components/ui/accordion";
import { Brain, Clock, Globe, GraduationCap, Layers, ShieldCheck, Sparkles, Target } from "lucide-react";
import { placeholderImages } from "@/data/placeholderImages";

const heroContent = {
  title: "Online Abacus Classes\nthat build speed, focus,\nand real math confidence",
  subtitle:
    "Live interactive sessions designed for young learners with guided practice, smart drills, and progress tracking.",
  cta: "Enroll Now",
};

const introParagraphs = [
  "Our online abacus program blends live instruction with playful practice so students grow strong number sense and mental math skills from the very beginning.",
  "With structured levels, short daily exercises, and supportive mentors, learners develop focus, memory, and fast calculation habits that benefit school performance.",
];

const benefits = [
  "Brain development through visualization and concentration",
  "Memory improvement with daily recall-based drills",
  "Faster calculations using mental abacus techniques",
];

const helpHighlights = [
  { title: "10 levels program", desc: "A complete, step-by-step pathway from basics to advanced mental math." },
  { title: "3 months per level", desc: "Balanced pace with enough practice time to master each stage." },
  { title: "Certification", desc: "Level-wise exams and achievement certificates for every milestone." },
  { title: "Personal mentorship", desc: "Dedicated guidance with feedback and progress tracking." },
];

const features = [
  { icon: ShieldCheck, title: "Affordable Fees", desc: "Accessible pricing with flexible monthly plans for families." },
  { icon: Clock, title: "24/7 Support", desc: "Quick help for class queries, homework, and technical assistance." },
  { icon: Layers, title: "Structured Levels", desc: "Clear progression so students always know their next goal." },
  { icon: Sparkles, title: "Practice Materials", desc: "Worksheets, timed drills, and smart revisions after class." },
  { icon: Globe, title: "Learn Anywhere", desc: "Join from home with live classes on any device." },
  { icon: Target, title: "Performance Tracking", desc: "Regular assessments with feedback on speed and accuracy." },
];

const syllabusPoints = [
  "Number concepts, place value, and abacus setup",
  "Addition and subtraction with multi-digit operations",
  "Multiplication and division using mental abacus rules",
  "Squares and cubes for faster math fluency",
  "Speed math techniques and timed calculation practice",
];

const faqs = [
  {
    q: "What is abacus training?",
    a: "It is a structured method that builds mental calculation skills using visualized bead movements and focused practice.",
  },
  {
    q: "How long is the course?",
    a: "The program has 10 levels, with each level taking about three months depending on student progress.",
  },
  {
    q: "What age group is suitable?",
    a: "Most learners start between ages 5 and 14, but beginners can join based on readiness.",
  },
  {
    q: "Do students get certificates?",
    a: "Yes, students receive certificates after completing each level’s assessment.",
  },
  {
    q: "Are classes live or recorded?",
    a: "Classes are live and interactive, with practice materials provided for after-class learning.",
  },
  {
    q: "How many classes per week?",
    a: "Typically 2 to 3 sessions per week, with short daily practice for best results.",
  },
  {
    q: "Is parental support required?",
    a: "For younger learners, light supervision helps with routine and device setup.",
  },
];

type SectionProps = {
  id?: string;
  className?: string;
  children: React.ReactNode;
};

const Section = ({ id, className = "", children }: SectionProps) => (
  <section id={id} className={`py-16 md:py-20 ${className}`}>
    <div className="container mx-auto px-4 max-w-7xl">{children}</div>
  </section>
);

type FeatureCardProps = {
  icon: typeof Brain;
  title: string;
  desc: string;
};

const FeatureCard = ({ icon: Icon, title, desc }: FeatureCardProps) => (
  <div className="rounded-2xl border border-border bg-white p-6 shadow-sm transition-transform duration-300 hover:-translate-y-1 hover:shadow-md">
    <div className="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-[#f97316]/10 text-[#f97316]">
      <Icon className="h-6 w-6" />
    </div>
    <h3 className="text-lg font-semibold text-foreground mb-2">{title}</h3>
    <p className="text-sm text-muted-foreground leading-relaxed">{desc}</p>
  </div>
);

const OnlineAbacusClasses = () => (
  <div className="min-h-screen bg-background">
    <Navbar />
    <main className="pt-16">
      {/* Introduction */}
      <Section className="bg-white">
        <div className="max-w-4xl">
          <h2 className="text-3xl md:text-4xl font-heading font-bold text-foreground mb-5">
            Online Abacus Classes designed for young achievers
          </h2>
          <div className="space-y-4 text-base md:text-lg text-muted-foreground leading-relaxed">
            {introParagraphs.map((p) => (
              <p key={p}>{p}</p>
            ))}
          </div>
          <ul className="mt-6 space-y-2 text-foreground">
            {benefits.map((b) => (
              <li key={b} className="flex items-start gap-2">
                <span className="mt-1 h-2 w-2 rounded-full bg-[#f97316]" />
                <span className="text-base">{b}</span>
              </li>
            ))}
          </ul>
        </div>
      </Section>

      {/* How it Helps */}
      <Section className="bg-muted/40">
        <div className="grid lg:grid-cols-12 gap-10 items-start">
          <div className="lg:col-span-5">
            <h2 className="text-3xl md:text-4xl font-heading font-bold text-foreground mb-4">
              How it helps students grow faster
            </h2>
            <p className="text-muted-foreground text-lg leading-relaxed">
              The program balances live classes with guided practice, building speed, accuracy, and confidence at every
              stage.
            </p>
          </div>
          <div className="lg:col-span-7 grid sm:grid-cols-2 gap-6">
            {helpHighlights.map((item) => (
              <div key={item.title} className="rounded-2xl bg-white border border-border p-6 shadow-sm">
                <h3 className="text-lg font-semibold text-foreground mb-2">{item.title}</h3>
                <p className="text-sm text-muted-foreground leading-relaxed">{item.desc}</p>
              </div>
            ))}
          </div>
        </div>
      </Section>

      {/* Features */}
      <Section>
        <div className="text-center max-w-3xl mx-auto mb-12">
          <h2 className="text-3xl md:text-4xl font-heading font-bold text-foreground mb-4">
            Features that make learning easy
          </h2>
          <p className="text-muted-foreground text-lg">
            Everything students need to stay motivated, practice consistently, and progress with confidence.
          </p>
        </div>
        <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
          {features.map((f) => (
            <FeatureCard key={f.title} icon={f.icon} title={f.title} desc={f.desc} />
          ))}
        </div>
      </Section>

      {/* Course Details */}
      <Section className="bg-white">
        <div className="grid md:grid-cols-12 gap-10 items-start">
          <div className="md:col-span-5">
            <div className="flex items-center gap-3 text-[#f97316] mb-3">
              <GraduationCap className="h-6 w-6" />
              <span className="text-sm font-semibold uppercase tracking-widest">Course Details</span>
            </div>
            <h2 className="text-3xl md:text-4xl font-heading font-bold text-foreground mb-4">
              What students will master
            </h2>
            <p className="text-muted-foreground text-lg">
              A complete syllabus that strengthens fundamentals and builds advanced mental math abilities.
            </p>
          </div>
          <div className="md:col-span-7">
            <ul className="space-y-3">
              {syllabusPoints.map((point) => (
                <li key={point} className="flex items-start gap-3 text-base text-foreground">
                  <span className="mt-2 h-2 w-2 rounded-full bg-[#4c1d95]" />
                  <span>{point}</span>
                </li>
              ))}
            </ul>
          </div>
        </div>
      </Section>

      {/* CTA */}
      <section className="py-16 md:py-20 bg-gradient-to-r from-[#f97316] via-[#fb923c] to-[#facc15]">
        <div className="container mx-auto px-4 max-w-6xl text-center">
          <h2 className="text-3xl md:text-4xl font-heading font-bold text-white mb-4">
            Ready to start your child’s abacus journey?
          </h2>
          <p className="text-white/90 text-lg max-w-2xl mx-auto mb-6">
            Book a free demo class and see how online abacus can boost speed, focus, and confidence.
          </p>
          <Button size="lg" className="bg-white text-[#b45309] hover:bg-white/90 font-semibold" asChild>
            <Link to="/book-demo">Book Demo</Link>
          </Button>
        </div>
      </section>

      {/* FAQ */}
      <Section className="bg-white">
        <div className="max-w-4xl mx-auto">
          <div className="text-center mb-10">
            <h2 className="text-3xl md:text-4xl font-heading font-bold text-foreground mb-3">Frequently Asked Questions</h2>
            <p className="text-muted-foreground text-lg">
              Quick answers to help parents understand our online abacus classes.
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

export default OnlineAbacusClasses;
