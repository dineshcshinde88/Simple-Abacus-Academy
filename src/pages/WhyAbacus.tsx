import Navbar from "@/components/layout/Navbar";
import Footer from "@/components/layout/Footer";
import { motion } from "framer-motion";
import { Button } from "@/components/ui/button";
import { Link } from "react-router-dom";
import { Brain, Zap, Focus, TrendingUp, Shield, Smile, ArrowRight } from "lucide-react";

const benefits = [
  { icon: Brain, title: "Builds Thinking Skills", desc: "Abacus uses visual and logical thinking together, helping children solve problems faster and with more clarity.", color: "bg-info/10 text-info" },
  { icon: Zap, title: "Faster Calculations", desc: "Students learn to calculate mentally without fingers or calculators, improving speed step by step.", color: "bg-secondary/10 text-secondary" },
  { icon: Focus, title: "Improves Concentration", desc: "Regular practice builds attention span and focus, which supports learning in every subject.", color: "bg-success/10 text-success" },
  { icon: TrendingUp, title: "Better Academic Confidence", desc: "Children feel proud when they solve sums quickly, which increases confidence in school.", color: "bg-warm/10 text-warm" },
  { icon: Shield, title: "Strong Memory", desc: "Visual bead practice strengthens memory and recall, making learning smoother and easier.", color: "bg-info/10 text-info" },
  { icon: Smile, title: "Learning Feels Fun", desc: "Games, speed tests, and small rewards keep children motivated and happy to practice.", color: "bg-secondary/10 text-secondary" },
];

const research = [
  { stat: "5,000+", label: "Learners trained across India" },
  { stat: "10", label: "Structured levels from beginner to advanced" },
  { stat: "60 min", label: "Average weekly guided practice" },
  { stat: "Monthly", label: "Progress checks and feedback" },
];

const fadeUp = { initial: { opacity: 0, y: 20 }, whileInView: { opacity: 1, y: 0 }, viewport: { once: true } };

const WhyAbacus = () => (
  <div className="min-h-screen">
    <Navbar />
    <main className="pt-16">
      {/* Hero */}
      <section className="gradient-hero py-20 md:py-28">
        <div className="container mx-auto px-4 text-center">
          <motion.h1 {...fadeUp} className="text-4xl md:text-5xl font-heading font-bold text-primary-foreground mb-4">
            Why <span className="text-gradient">Abacus?</span>
          </motion.h1>
          <motion.p {...fadeUp} transition={{ delay: 0.1 }} className="text-primary-foreground/80 max-w-2xl mx-auto text-lg">
            Abacus training builds focus, memory, and confidence while making maths feel easy and enjoyable.
          </motion.p>
        </div>
      </section>

      {/* Benefits Grid */}
      <section className="py-20">
        <div className="container mx-auto px-4">
          <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-8 max-w-5xl mx-auto">
            {benefits.map((b, i) => (
              <motion.div
                key={b.title}
                {...fadeUp}
                transition={{ delay: i * 0.1 }}
                className="bg-card rounded-xl p-6 border border-border shadow-card hover:shadow-card-hover transition-shadow"
              >
                <div className={`w-12 h-12 rounded-xl ${b.color} flex items-center justify-center mb-4`}>
                  <b.icon className="w-6 h-6" />
                </div>
                <h3 className="text-lg font-heading font-bold text-foreground mb-2">{b.title}</h3>
                <p className="text-sm text-muted-foreground leading-relaxed">{b.desc}</p>
              </motion.div>
            ))}
          </div>
        </div>
      </section>

      {/* Research Stats */}
      <section className="py-20 bg-muted/50">
        <div className="container mx-auto px-4">
          <motion.div {...fadeUp} className="text-center mb-14">
            <span className="inline-block px-4 py-1.5 rounded-full bg-accent-soft text-secondary text-sm font-semibold mb-4">Program Highlights</span>
            <h2 className="text-3xl md:text-4xl font-heading font-bold text-foreground">Learning That <span className="text-gradient">Shows</span></h2>
            <p className="text-muted-foreground mt-3 max-w-xl mx-auto">
              A consistent approach with clear milestones helps children progress steadily and feel confident at every level.
            </p>
          </motion.div>
          <div className="grid grid-cols-2 md:grid-cols-4 gap-6 max-w-4xl mx-auto">
            {research.map((r, i) => (
              <motion.div key={r.label} {...fadeUp} transition={{ delay: i * 0.1 }} className="bg-card rounded-xl p-6 border border-border shadow-card text-center">
                <div className="text-3xl md:text-4xl font-heading font-bold text-secondary mb-2">{r.stat}</div>
                <p className="text-sm text-muted-foreground">{r.label}</p>
              </motion.div>
            ))}
          </div>
        </div>
      </section>

      {/* Age Groups */}
      <section className="py-20">
        <div className="container mx-auto px-4 max-w-3xl text-center">
          <motion.div {...fadeUp}>
            <h2 className="text-3xl md:text-4xl font-heading font-bold text-foreground mb-6">Best Age to Start: <span className="text-gradient">6-14 Years</span></h2>
            <p className="text-muted-foreground mb-8 leading-relaxed">
              The 6-14 age range is ideal for building strong mental math habits. Children learn quickly, enjoy visual methods,
              and develop confidence that helps across all subjects.
            </p>
            <Button variant="hero" size="lg" asChild>
              <Link to="/book-demo">Book a Free Demo <ArrowRight className="w-5 h-5" /></Link>
            </Button>
          </motion.div>
        </div>
      </section>
    </main>
    <Footer />
  </div>
);

export default WhyAbacus;
