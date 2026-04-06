import { motion } from "framer-motion";
import { Button } from "@/components/ui/button";
import { Link } from "react-router-dom";
import { ArrowRight, Star } from "lucide-react";

const levels = [
  { range: "Level 1–3", title: "Beginner", age: "6–8 years", desc: "Foundation of abacus operations, basic addition & subtraction with beads.", features: ["Abacus basics", "Single-digit operations", "Visual calculation"], color: "border-info/30 bg-info/5" },
  { range: "Level 4–6", title: "Intermediate", age: "8–11 years", desc: "Mental math without physical abacus, multiplication & division mastery.", features: ["Mental visualization", "Multi-digit operations", "Speed drills"], popular: true, color: "border-secondary/40 bg-secondary/5" },
  { range: "Level 7–10", title: "Advanced", age: "11–14 years", desc: "Complex calculations, competition preparation, and mastery certification.", features: ["Competition prep", "Complex operations", "Certification exam"], color: "border-success/30 bg-success/5" },
];

const ProgramsSection = () => (
  <section className="py-20 bg-muted/50">
    <div className="container mx-auto px-4">
      <motion.div
        initial={{ opacity: 0, y: 20 }}
        whileInView={{ opacity: 1, y: 0 }}
        viewport={{ once: true }}
        className="text-center mb-14"
      >
        <span className="inline-block px-4 py-1.5 rounded-full bg-accent-soft text-secondary text-sm font-semibold mb-4">
          Our Programs
        </span>
        <h2 className="text-3xl md:text-4xl font-heading font-bold text-foreground mb-4">
          Structured Learning <span className="text-gradient">Levels</span>
        </h2>
        <p className="text-muted-foreground max-w-2xl mx-auto">
          Our progressive curriculum ensures every child advances at their own pace with measurable milestones.
        </p>
      </motion.div>

      <div className="grid md:grid-cols-3 gap-6 max-w-5xl mx-auto">
        {levels.map((l, i) => (
          <motion.div
            key={l.title}
            initial={{ opacity: 0, y: 20 }}
            whileInView={{ opacity: 1, y: 0 }}
            viewport={{ once: true }}
            transition={{ delay: i * 0.15 }}
            className={`relative bg-card rounded-xl p-6 border-2 ${l.color} shadow-card hover:shadow-card-hover transition-all duration-300`}
          >
            {l.popular && (
              <div className="absolute -top-3 left-1/2 -translate-x-1/2 px-3 py-1 rounded-full gradient-accent text-accent-foreground text-xs font-bold flex items-center gap-1">
                <Star className="w-3 h-3" /> Most Popular
              </div>
            )}
            <div className="text-xs font-semibold text-muted-foreground uppercase tracking-wider mb-1">{l.range}</div>
            <h3 className="text-xl font-heading font-bold text-foreground mb-1">{l.title}</h3>
            <div className="text-sm text-secondary font-medium mb-3">Ages {l.age}</div>
            <p className="text-sm text-muted-foreground mb-4">{l.desc}</p>
            <ul className="space-y-2 mb-6">
              {l.features.map((f) => (
                <li key={f} className="flex items-center gap-2 text-sm text-foreground">
                  <div className="w-1.5 h-1.5 rounded-full bg-secondary shrink-0" />
                  {f}
                </li>
              ))}
            </ul>
            <Button variant={l.popular ? "hero" : "outline"} size="sm" className="w-full" asChild>
              <Link to="/book-demo">
                Learn More <ArrowRight className="w-4 h-4" />
              </Link>
            </Button>
          </motion.div>
        ))}
      </div>
    </div>
  </section>
);

export default ProgramsSection;
