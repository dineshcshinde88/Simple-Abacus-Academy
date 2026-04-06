import { Button } from "@/components/ui/button";
import { Link } from "react-router-dom";
import { motion } from "framer-motion";
import { ArrowRight, Phone } from "lucide-react";

const CTASection = () => (
  <section className="py-20 gradient-hero relative overflow-hidden">
    <div className="absolute top-0 left-1/4 w-96 h-96 rounded-full bg-secondary/10 blur-3xl" />
    <div className="absolute bottom-0 right-1/4 w-72 h-72 rounded-full bg-accent-glow/10 blur-3xl" />

    <div className="container mx-auto px-4 relative z-10">
      <motion.div
        initial={{ opacity: 0, y: 20 }}
        whileInView={{ opacity: 1, y: 0 }}
        viewport={{ once: true }}
        className="text-center max-w-2xl mx-auto"
      >
        <h2 className="text-3xl md:text-4xl font-heading font-bold text-primary-foreground mb-4">
          Ready to Unlock Your Child's Potential?
        </h2>
        <p className="text-primary-foreground/70 mb-8">
          Book a free demo session today and see the difference abacus training can make. No commitment required.
        </p>
        <div className="flex flex-wrap justify-center gap-4">
          <Button variant="hero" size="lg" asChild>
            <Link to="/book-demo">
              Book Free Demo <ArrowRight className="w-4 h-4" />
            </Link>
          </Button>
          <Button variant="hero-outline" size="lg" asChild>
            <Link to="/contact">
              <Phone className="w-4 h-4" /> Contact Us
            </Link>
          </Button>
        </div>
      </motion.div>
    </div>
  </section>
);

export default CTASection;
