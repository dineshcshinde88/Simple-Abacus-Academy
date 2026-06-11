import Navbar from "@/components/layout/Navbar";
import Footer from "@/components/layout/Footer";
import { useState } from "react";
import { Link } from "react-router-dom";
import { toast } from "sonner";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import teacherTrainingImage from "@/assets/pages/teacher-training.png";
import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from "@/components/ui/accordion";
import { getApiBase } from "@/lib/apiBase";
import {
  Award,
  BookOpen,
  Briefcase,
  GraduationCap,
  Laptop,
  Layers,
  LineChart,
  Mail,
  MessageCircle,
  Phone,
  ShieldCheck,
  Sparkles,
  Users,
} from "lucide-react";

const intro = [
  "Build the skills and confidence needed to become a successful Online Abacus Instructor with our complete certification program—no prior teaching experience required.",
  "This course is specially created for beginners, homemakers, working professionals, graduates, and anyone interested in teaching and working with children.",
  "Our Online Abacus Teacher Training Course combines strong foundational knowledge with modern teaching techniques and practical training.",
  "You will begin with the basics and gradually progress to advanced mental math methods.",
  "Along the way, you’ll understand how abacus learning improves children’s concentration, memory, visualization, and logical thinking skills.",
];

const whyCourse = [
  "High-demand skill with growing opportunities",
  "Work from home with flexible schedules",
  "Create a steady, scalable income",
  "Make a meaningful impact on children",
];

const features = [
  { icon: Users, title: "Live Training", desc: "Interactive sessions with expert mentors and live practice." },
  { icon: Laptop, title: "Digital Tools", desc: "Access teaching dashboards, digital abacus, and smart resources." },
  { icon: GraduationCap, title: "Teaching Practicum", desc: "Hands-on practice with feedback to build classroom confidence." },
  { icon: Award, title: "Certification", desc: "Get recognized credentials after completing each stage." },
  { icon: ShieldCheck, title: "Community Support", desc: "Connect with trainers and peers for ongoing guidance." },
  { icon: Sparkles, title: "Career Guidance", desc: "Learn how to launch classes and attract students." },
];

const curriculum = [
  {
    icon: GraduationCap,
    title: "Abacus Basics",
    points: [
      "Number concepts and abacus setup",
      "Bead movement techniques",
      "Foundational calculation drills",
    ],
  },
  {
    icon: Users,
    title: "Teaching Methods",
    points: [
      "Child-friendly instruction strategies",
      "Lesson planning and pacing",
      "Assessment and feedback methods",
    ],
  },
  {
    icon: Sparkles,
    title: "Advanced Techniques",
    points: [
      "Mental abacus visualization",
      "Speed math and accuracy drills",
      "Level-wise exam preparation",
    ],
  },
  {
    icon: Laptop,
    title: "Digital Teaching Tools",
    points: [
      "Instructor dashboard basics",
      "Digital worksheets and practice plans",
      "Progress tracking and reports",
    ],
  },
  {
    title: "Practicum Project",
    points: [
      "Mock class delivery",
      "Recorded demo evaluations",
      "Certification readiness checklist",
    ],
  },
];

const tools = [
  { icon: LineChart, title: "Instructor Dashboard", desc: "Track attendance, progress, and performance in one place." },
  { icon: Layers, title: "Digital Abacus", desc: "Use interactive tools for teaching and student practice." },
  { icon: BookOpen, title: "Practice Materials", desc: "Worksheets, drills, and lesson plans ready to use." },
  { icon: Briefcase, title: "Student Tracking", desc: "Monitor learning pace with smart reports and insights." },
];

const earning = [
  "Earn from home with flexible class schedules",
  "Get student leads and enrollment support",
  "Start your own classes with guidance",
  "Build a long-term teaching career",
];

const courseDetails = [
  { label: "Levels", value: "10" },
  { label: "Duration", value: "Flexible / 3 months per level" },
  { label: "Lecture Hours", value: "60+ hours (live + practice)" },
  { label: "Mode", value: "Online" },
  { label: "Practice Materials", value: "Digital worksheets & drills" },
  { label: "Certification", value: "Yes, level-wise" },
];

const steps = [
  { title: "Apply", desc: "Submit your interest and counselor connects with you." },
  { title: "Enroll", desc: "Pick a batch and start your training journey." },
  { title: "Complete Training", desc: "Attend live classes and finish module tasks." },
  { title: "Practicum", desc: "Teach demo classes and receive feedback." },
  { title: "Get Certified", desc: "Clear assessment and earn credentials." },
  { title: "Start Earning", desc: "Launch your classes with support." },
];

const eligibility = [
  "Age 18+",
  "Basic education and language comfort",
  "Interest in teaching and working with children",
];

const faqs = [
  {
    q: "What is the course validity?",
    a: "The course validity is 3 months from the date of purchase.",
  },
  {
    q: "How many levels do I get access to?",
    a: "You get access to 8 levels, designed to build skills step by step.",
  },
  {
    q: "Do I need to pay extra to join as a teacher?",
    a: "No extra payment is required to join as a teacher after training.",
  },
  {
    q: "Is an assessment test required?",
    a: "Yes. An assessment test is required to confirm your seat as a teacher.",
  },
  {
    q: "Will I receive a certificate?",
    a: "Yes. A certificate is provided after successful completion of the course.",
  },
  {
    q: "Can I start earning after training?",
    a: "Yes. After training, you can start teaching and begin earning.",
  },
  {
    q: "What learning materials are included?",
    a: "You get access to a large library of practice sheets and mock tests, with updates added regularly.",
  },
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

const TeacherTrainingEnquiryForm = () => {
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [programs, setPrograms] = useState({ abacus: true, vedic: true });
  const [form, setForm] = useState({
    name: "",
    email: "",
    mobile: "",
    location: "",
    message: "",
  });

  const selectedPrograms = [
    programs.abacus ? "Abacus Teacher Training" : null,
    programs.vedic ? "Vedic Maths Teacher Training" : null,
  ].filter(Boolean) as string[];

  const updateField = (field: keyof typeof form, value: string) => {
    setForm((current) => ({ ...current, [field]: value }));
  };

  const handleSubmit = async (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault();

    if (selectedPrograms.length === 0) {
      toast.error("Please select at least one teacher training program.");
      return;
    }

    setIsSubmitting(true);
    try {
      const response = await fetch(`${getApiBase()}/api/instructor/apply`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          name: form.name,
          email: form.email,
          mobile: form.mobile,
          address: form.location || "Teacher training enquiry",
          qualification: form.message,
          programs: selectedPrograms,
        }),
      });

      if (!response.ok) {
        const data = await response.json().catch(() => ({}));
        throw new Error((data as { message?: string }).message || "Unable to submit enquiry.");
      }

      toast.success("Thank you! Your teacher training enquiry has been submitted.");
      setForm({ name: "", email: "", mobile: "", location: "", message: "" });
      setPrograms({ abacus: true, vedic: true });
    } catch (error) {
      toast.error(error instanceof Error ? error.message : "Unable to submit enquiry. Please try again.");
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <Section className="bg-muted/40">
      <div className="grid gap-8 lg:grid-cols-[0.85fr_1.15fr] items-start">
        <div>
          <span className="inline-flex rounded-full bg-[#f97316]/10 px-4 py-1.5 text-sm font-semibold text-[#f97316]">
            Teacher Training Enquiry
          </span>
          <h2 className="mt-4 text-3xl md:text-4xl font-heading font-bold text-foreground">
            Ask about Abacus and Vedic Maths teacher training
          </h2>
          <p className="mt-4 text-muted-foreground text-lg leading-relaxed">
            Share your details and our team will guide you on batches, fees, course structure, eligibility, and
            certification.
          </p>
          <div className="mt-6 flex flex-wrap gap-3">
            <Button variant="outline" asChild>
              <a href="tel:+918999164139">
                <Phone className="h-4 w-4" /> Call
              </a>
            </Button>
            <Button variant="outline" asChild>
              <a href="https://wa.me/918999164139?text=Hi%20Simple%20Abacus%2C%20I%20want%20to%20enquire%20about%20Abacus%20and%20Vedic%20Maths%20teacher%20training." target="_blank" rel="noopener noreferrer">
                <MessageCircle className="h-4 w-4" /> WhatsApp
              </a>
            </Button>
            <Button variant="outline" asChild>
              <a href="mailto:simpleabacuspune@gmail.com?subject=Teacher%20Training%20Enquiry">
                <Mail className="h-4 w-4" /> Email
              </a>
            </Button>
          </div>
        </div>

        <form onSubmit={handleSubmit} className="rounded-2xl border border-border bg-white p-6 shadow-card">
          <div className="grid gap-5 sm:grid-cols-2">
            <div className="space-y-2">
              <Label htmlFor="teacher-enquiry-name">Full Name</Label>
              <Input
                id="teacher-enquiry-name"
                value={form.name}
                onChange={(event) => updateField("name", event.target.value)}
                placeholder="Your name"
                required
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor="teacher-enquiry-email">Email</Label>
              <Input
                id="teacher-enquiry-email"
                type="email"
                value={form.email}
                onChange={(event) => updateField("email", event.target.value)}
                placeholder="you@example.com"
                required
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor="teacher-enquiry-mobile">Mobile Number</Label>
              <Input
                id="teacher-enquiry-mobile"
                type="tel"
                value={form.mobile}
                onChange={(event) => updateField("mobile", event.target.value)}
                placeholder="+91 XXXXX XXXXX"
                required
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor="teacher-enquiry-location">City / Location</Label>
              <Input
                id="teacher-enquiry-location"
                value={form.location}
                onChange={(event) => updateField("location", event.target.value)}
                placeholder="City or area"
              />
            </div>
          </div>

          <div className="mt-5 space-y-3">
            <Label>Interested Program</Label>
            <div className="flex flex-wrap gap-4 text-sm font-medium text-foreground">
              <label className="inline-flex items-center gap-2">
                <input
                  type="checkbox"
                  checked={programs.abacus}
                  onChange={(event) => setPrograms((current) => ({ ...current, abacus: event.target.checked }))}
                  className="h-4 w-4 accent-[#4c1d95]"
                />
                Abacus Teacher Training
              </label>
              <label className="inline-flex items-center gap-2">
                <input
                  type="checkbox"
                  checked={programs.vedic}
                  onChange={(event) => setPrograms((current) => ({ ...current, vedic: event.target.checked }))}
                  className="h-4 w-4 accent-[#4c1d95]"
                />
                Vedic Maths Teacher Training
              </label>
            </div>
          </div>

          <div className="mt-5 space-y-2">
            <Label htmlFor="teacher-enquiry-message">Message</Label>
            <Textarea
              id="teacher-enquiry-message"
              value={form.message}
              onChange={(event) => updateField("message", event.target.value)}
              placeholder="Tell us what you want to know about the training program"
              rows={4}
            />
          </div>

          <Button type="submit" className="mt-6 bg-[#f97316] hover:bg-[#ea580c] text-white" disabled={isSubmitting}>
            {isSubmitting ? "Submitting..." : "Submit Enquiry"}
          </Button>
        </form>
      </div>
    </Section>
  );
};

const TeacherTraining = () => (
  <div className="min-h-screen bg-background">
    <Navbar />
    <main className="pt-16">
      {/* Introduction */}
      <Section className="bg-white">
        <div className="grid gap-10 lg:grid-cols-[1.1fr_0.9fr] items-start">
          <div>
          <h2 className="text-3xl md:text-4xl font-heading font-bold text-foreground mb-5">
            Online Abacus Teacher Training program
          </h2>
          <div className="space-y-4 text-base md:text-lg text-muted-foreground leading-relaxed">
            {intro.map((p) => (
              <p key={p}>{p}</p>
            ))}
          </div>
          </div>
          <div className="rounded-2xl border border-border bg-slate-50 p-4 shadow-card">
            <img
              src={teacherTrainingImage}
              alt="Teacher training"
              className="w-full rounded-xl object-contain"
              loading="lazy"
            />
          </div>
        </div>
      </Section>

      <TeacherTrainingEnquiryForm />

      {/* Why this course */}
      <Section className="bg-muted/40">
        <div className="grid lg:grid-cols-12 gap-10 items-start">
          <div className="lg:col-span-5">
            <h2 className="text-3xl md:text-4xl font-heading font-bold text-foreground mb-4">
              Why choose this course
            </h2>
            <p className="text-muted-foreground text-lg leading-relaxed">
              Build a skill that is in demand, flexible, and meaningful for your future.
            </p>
          </div>
          <div className="lg:col-span-7 grid sm:grid-cols-2 gap-6">
            {whyCourse.map((item) => (
              <div key={item} className="rounded-2xl bg-white border border-border p-6 shadow-sm">
                <p className="text-base font-semibold text-foreground">{item}</p>
              </div>
            ))}
          </div>
        </div>
      </Section>

      {/* Features */}
      <Section>
        <div className="text-center max-w-3xl mx-auto mb-12">
          <h2 className="text-3xl md:text-4xl font-heading font-bold text-foreground mb-4">
            What you get in the training
          </h2>
          <p className="text-muted-foreground text-lg">
            A complete system to help you learn, practice, and start teaching with confidence.
          </p>
        </div>
        <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
          {features.map((f) => (
            <Card key={f.title} icon={f.icon} title={f.title} desc={f.desc} />
          ))}
        </div>
      </Section>

      {/* Curriculum */}
      <Section className="bg-white">
        <div className="text-center max-w-3xl mx-auto mb-12">
          <div className="flex items-center justify-center gap-3 text-[#f97316] mb-3">
            <GraduationCap className="h-6 w-6" />
            <span className="text-sm font-semibold uppercase tracking-widest">Curriculum</span>
          </div>
          <h2 className="text-3xl md:text-4xl font-heading font-bold text-foreground mb-4">
            Structured modules with clear outcomes
          </h2>
          <p className="text-muted-foreground text-lg">
            Each module builds teaching confidence with practice, tools, and assessments.
          </p>
        </div>
        <div className="grid gap-6 lg:grid-cols-4">
          {curriculum.slice(0, 4).map((m) => (
            <div key={m.title} className="rounded-2xl border border-border bg-white p-6 shadow-sm">
              <div className="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-[#f97316]/10 text-[#f97316]">
                <m.icon className="h-6 w-6" />
              </div>
              <h3 className="text-lg font-semibold text-foreground mb-3">{m.title}</h3>
              <ul className="space-y-2">
                {m.points.map((p) => (
                  <li key={p} className="flex items-start gap-3 text-sm text-foreground">
                    <span className="mt-2 h-2 w-2 rounded-full bg-[#4c1d95]" />
                    <span>{p}</span>
                  </li>
                ))}
              </ul>
            </div>
          ))}
        </div>
      </Section>

      {/* Digital Tools */}
      <Section className="bg-muted/40">
        <div className="text-center max-w-3xl mx-auto mb-12">
          <h2 className="text-3xl md:text-4xl font-heading font-bold text-foreground mb-4">
            Digital tools and platform access
          </h2>
          <p className="text-muted-foreground text-lg">
            Teach smarter with tools built for online abacus instruction.
          </p>
        </div>
        <div className="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
          {tools.map((t) => (
            <Card key={t.title} icon={t.icon} title={t.title} desc={t.desc} />
          ))}
        </div>
      </Section>

      {/* Benefits / Earning */}
      <section className="py-16 md:py-20 bg-gradient-to-r from-[#4c1d95] via-[#7c3aed] to-[#f97316]">
        <div className="container mx-auto px-4 max-w-6xl text-white">
          <div className="grid md:grid-cols-2 gap-10 items-center">
            <div>
              <h2 className="text-3xl md:text-4xl font-heading font-bold mb-4">
                Turn your training into a real earning opportunity
              </h2>
              <p className="text-white/90 text-lg">
                Start teaching from home with support, tools, and guidance to build your student base.
              </p>
            </div>
            <ul className="space-y-3">
              {earning.map((e) => (
                <li key={e} className="flex items-start gap-3">
                  <span className="mt-2 h-2 w-2 rounded-full bg-white" />
                  <span className="text-base">{e}</span>
                </li>
              ))}
            </ul>
          </div>
        </div>
      </section>

      {/* How it Works */}
      <Section className="bg-muted/40">
        <div className="text-center max-w-3xl mx-auto mb-12">
          <h2 className="text-3xl md:text-4xl font-heading font-bold text-foreground mb-4">
            How it works
          </h2>
          <p className="text-muted-foreground text-lg">A simple step-by-step path from application to earning.</p>
        </div>
        <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
          {steps.map((s, i) => (
            <div key={s.title} className="rounded-2xl border border-border bg-white p-6 shadow-sm">
              <div className="text-xs font-bold text-[#f97316] mb-2">Step {i + 1}</div>
              <h3 className="text-lg font-semibold text-foreground mb-2">{s.title}</h3>
              <p className="text-sm text-muted-foreground">{s.desc}</p>
            </div>
          ))}
        </div>
      </Section>

      {/* Eligibility */}
      <Section className="bg-white">
        <div className="grid md:grid-cols-12 gap-10 items-start">
          <div className="md:col-span-5">
            <h2 className="text-3xl md:text-4xl font-heading font-bold text-foreground mb-4">
              Eligibility criteria
            </h2>
            <p className="text-muted-foreground text-lg">
              Simple requirements so more people can start teaching.
            </p>
          </div>
          <div className="md:col-span-7">
            <ul className="space-y-3">
              {eligibility.map((e) => (
                <li key={e} className="flex items-start gap-3 text-base text-foreground">
                  <span className="mt-2 h-2 w-2 rounded-full bg-[#4c1d95]" />
                  <span>{e}</span>
                </li>
              ))}
            </ul>
          </div>
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
              Quick answers for common questions about teacher training.
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
            Start your teacher training today
          </h2>
          <p className="text-white/85 text-lg max-w-2xl mx-auto mb-6">
            Join the next batch, get certified, and launch your teaching journey with full support.
          </p>
          <Button size="lg" className="bg-[#f97316] hover:bg-[#ea580c] text-white font-semibold" asChild>
            <Link to="/instructor-registration">Start Teaching</Link>
          </Button>
        </div>
      </section>
    </main>
    <Footer />
  </div>
);

export default TeacherTraining;

