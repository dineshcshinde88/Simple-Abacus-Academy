import { motion } from "framer-motion";
import { placeholderImages } from "@/data/placeholderImages";

const AboutAcademySection = () => (
  <section className="py-20 bg-card border-b border-border">
    <div className="container mx-auto px-4">
      <div className="grid lg:grid-cols-2 gap-10 items-center">
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          whileInView={{ opacity: 1, y: 0 }}
          viewport={{ once: true }}
        >
          <span className="inline-block px-4 py-1.5 rounded-full bg-accent-soft text-secondary text-sm font-semibold mb-4">
            About Simple Abacus Academy
          </span>
          <p className="text-muted-foreground leading-relaxed mb-4">
            SIMPLE ABACUS is dedicated to building strong mathematical foundations and confident young minds through structured Abacus and Vedic Maths training.
          </p>
          <p className="text-muted-foreground leading-relaxed mb-4">
            We believe that every child has great potential. With the right guidance, regular practice, and positive learning environment, students can improve their concentration, memory power, speed, and confidence in Mathematics.
          </p>
          <p className="text-muted-foreground leading-relaxed mb-4">
            Our teaching approach focuses on clarity, step-by-step learning, and individual attention to help each child achieve their best performance.
          </p>
          <p className="text-foreground font-medium leading-relaxed">
            At SIMPLE ABACUS, we are not just teaching Maths — we are developing sharper minds and brighter futures.
          </p>
        </motion.div>

        <motion.div
          initial={{ opacity: 0, scale: 0.95 }}
          whileInView={{ opacity: 1, scale: 1 }}
          viewport={{ once: true }}
          transition={{ delay: 0.1 }}
          className="relative"
        >
          <div className="absolute inset-0 gradient-accent rounded-3xl -rotate-2 opacity-15" />
          <img
            src={placeholderImages.aboutAcademy}
            alt="Students learning at Simple Abacus Academy"
            className="relative w-full rounded-3xl shadow-2xl object-cover aspect-[4/3]"
            loading="lazy"
          />
        </motion.div>
      </div>
    </div>
  </section>
);

export default AboutAcademySection;
