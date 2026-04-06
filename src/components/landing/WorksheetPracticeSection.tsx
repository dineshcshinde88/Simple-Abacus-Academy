import { Link } from "react-router-dom";
import { Button } from "@/components/ui/button";
import { CheckCircle2, Download, Infinity, Laptop, LogIn } from "lucide-react";
import { placeholderImages } from "@/data/placeholderImages";

const features = [
  { icon: Infinity, text: "Unlimited Abacus Practice Sessions" },
  { icon: CheckCircle2, text: "Abacus Visualization Training" },
  { icon: LogIn, text: "24/7 Student Login for Abacus Practice" },
  { icon: Download, text: "Downloadable Abacus Worksheets" },
  { icon: Laptop, text: "Level-Wise Abacus Practice Worksheets" },
];

const WorksheetPracticeSection = () => (
  <section className="py-16">
    <div className="container mx-auto px-4">
      <div className="grid items-stretch lg:grid-cols-[1.05fr_0.95fr] overflow-hidden rounded-[32px] bg-white shadow-card">
        <div className="h-full p-8 md:p-12">
          <span className="inline-flex items-center gap-2 rounded-full bg-secondary/10 px-4 py-2 text-xs font-semibold uppercase tracking-[0.2em] text-secondary">
            Abacus & Vedic Maths Worksheet Practice
          </span>
          <h2 className="mt-5 text-3xl md:text-4xl font-heading font-extrabold text-primary">
            Unlimited Abacus Worksheet Practice
          </h2>
          <p className="mt-4 text-base text-muted-foreground leading-relaxed">
            Give students the advantage of daily abacus worksheet practice designed to improve
            calculation speed, accuracy, concentration, and confidence in mental math.
          </p>

          <div className="mt-6 grid gap-3 text-sm text-primary">
            {features.map(({ icon: Icon, text }) => (
              <div key={text} className="flex items-start gap-3">
                <Icon className="mt-0.5 h-5 w-5 text-secondary" />
                <span>{text}</span>
              </div>
            ))}
          </div>

          <p className="mt-6 text-sm text-muted-foreground">
            Students can practice abacus anytime, anywhere using laptop, mobile, or tablet for
            flexible and effective learning.
          </p>

          <div className="mt-8">
            <Button variant="hero" size="lg" asChild>
              <Link to="/worksheets-subscription">Subscribe for Unlimited Abacus Practice</Link>
            </Button>
          </div>
        </div>

        <div className="relative flex items-center justify-center bg-white p-4 md:p-6">
          <div className="relative flex h-full w-full flex-col rounded-3xl border border-border bg-white p-4 md:p-5 shadow-xl">
            <div className="flex-1 w-full max-h-[320px] overflow-hidden rounded-2xl bg-slate-100 md:max-h-[380px]">
              <img
                src={placeholderImages.worksheetPractice}
                alt="Practice from any device"
                className="h-full w-full object-cover"
                loading="lazy"
              />
            </div>
            <div className="mt-5 text-center" />
          </div>
        </div>
      </div>
    </div>
  </section>
);

export default WorksheetPracticeSection;

