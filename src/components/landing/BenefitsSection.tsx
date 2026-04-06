import { motion } from "framer-motion";
import { Brain, Zap, Target, Trophy, BookOpen, Users } from "lucide-react";

const benefits = [
  { icon: Brain, title: "Enhanced Memory", description: "Abacus training strengthens working memory and recall abilities in children.", color: "bg-info/10 text-info" },
  { icon: Zap, title: "Lightning Speed", description: "Students develop remarkable calculation speed that outperforms calculators.", color: "bg-secondary/10 text-secondary" },
  { icon: Target, title: "Deep Concentration", description: "Regular practice dramatically improves focus and attention span.", color: "bg-success/10 text-success" },
  { icon: Trophy, title: "Confidence Boost", description: "Math mastery builds unshakable self-confidence in academics and beyond.", color: "bg-warm/10 text-warm" },
  { icon: BookOpen, title: "Academic Excellence", description: "Students consistently outperform peers across all subjects, not just math.", color: "bg-primary/10 text-primary" },
  { icon: Users, title: "Life Skills", description: "Problem-solving, logical thinking, and analytical skills for lifelong success.", color: "bg-accent/10 text-accent" },
];

const BenefitsSection = () => (
  <section className="py-20 bg-background">
    <div className="container mx-auto px-4">
      <motion.div
        initial={{ opacity: 0, y: 20 }}
        whileInView={{ opacity: 1, y: 0 }}
        viewport={{ once: true }}
        className="text-center mb-14"
      >
        <span className="inline-block px-4 py-1.5 rounded-full bg-accent-soft text-secondary text-sm font-semibold mb-4">
          Why Abacus Training?
        </span>
        <h2 className="text-3xl md:text-4xl font-heading font-bold text-foreground mb-4">
          Transform Your Child's <span className="text-gradient">Brain Power</span>
        </h2>
        <p className="text-muted-foreground max-w-2xl mx-auto">
          Scientific research proves abacus training activates both brain hemispheres, leading to holistic cognitive development.
        </p>
      </motion.div>

      <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
        {benefits.map((b, i) => (
          <motion.div
            key={b.title}
            initial={{ opacity: 0, y: 20 }}
            whileInView={{ opacity: 1, y: 0 }}
            viewport={{ once: true }}
            transition={{ delay: i * 0.1 }}
            className="group bg-card rounded-xl p-6 shadow-card hover:shadow-card-hover transition-all duration-300 border border-border hover:border-secondary/30"
          >
            <div className={`w-12 h-12 rounded-lg ${b.color} flex items-center justify-center mb-4 group-hover:scale-110 transition-transform`}>
              <b.icon className="w-6 h-6" />
            </div>
            <h3 className="font-heading font-semibold text-lg text-foreground mb-2">{b.title}</h3>
            <p className="text-sm text-muted-foreground leading-relaxed">{b.description}</p>
          </motion.div>
        ))}
      </div>
    </div>
  </section>
);

export default BenefitsSection;
