import Navbar from "@/components/layout/Navbar";
import Footer from "@/components/layout/Footer";
import { motion } from "framer-motion";
import { Target, Eye, Heart, Users, Award, BookOpen } from "lucide-react";
import { placeholderImages } from "@/data/placeholderImages";

const stats = [
  { label: "Students Trained", value: "100+" },
  { label: "Certified Trainers", value: "180+" },
  { label: "Active Learning Centres", value: "3+" },
  { label: "Years of Excellence", value: "14+" },
];

const values = [
  { icon: Target, title: "Our Mission", desc: "Make maths simple and joyful by building focus, memory, and calculation speed through a step-by-step abacus journey." },
  { icon: Eye, title: "Our Vision", desc: "Create confident learners who think logically, learn faster, and grow with a strong academic base." },
  { icon: Heart, title: "Our Values", desc: "Child-first teaching, patient mentoring, consistent practice, and celebrating small wins every week." },
];

const team = [
  { name: "Varsha Shinde", role: "Founder & Master Trainer", desc: "14+ years in abacus training and child learning" },
  { name: "Anita Desai", role: "Senior Trainer", desc: "Certified Level 10 instructor, 1,500+ students mentored" },
  { name: "Rajesh Mehta", role: "Program Coordinator", desc: "Handles batches, exams, and parent communication" },
];

const fadeUp = { initial: { opacity: 0, y: 20 }, whileInView: { opacity: 1, y: 0 }, viewport: { once: true } };

const About = () => (
  <div className="min-h-screen">
    <Navbar />
    <main className="pt-16">
      {/* About Simple Abacus */}
      <section className="py-20 md:py-24 bg-card border-b border-border">
        <div className="container mx-auto px-4">
          <div className="grid lg:grid-cols-2 gap-10 items-center">
            <motion.div {...fadeUp}>
              <h1 className="text-4xl md:text-5xl font-heading font-bold text-foreground mb-6">
                About <span className="text-gradient">Simple Abacus</span>
              </h1>
              <p className="text-muted-foreground leading-relaxed mb-4">
                Simple Abacus Academy is a child-focused learning institute that helps students master mental maths with
                clarity, confidence, and consistency.
              </p>
              <p className="text-muted-foreground leading-relaxed mb-4">
                Our programs are designed for ages 6 to 14 and follow a structured level system that turns practice into
                a habit and calculations into a skill.
              </p>
              <p className="text-muted-foreground leading-relaxed mb-3">
                With the right guidance, every child can develop:
              </p>
              <ul className="list-disc pl-6 text-muted-foreground space-y-1 mb-4">
                <li>Stronger focus and attention</li>
                <li>Sharp memory and recall</li>
                <li>Faster and more accurate calculations</li>
                <li>Logical thinking and problem solving</li>
                <li>Confidence in school and exams</li>
              </ul>
              <p className="text-muted-foreground leading-relaxed mb-4">
                Our students regularly participate in inter-school math challenges and abacus meets. Many earn top ranks
                at the city and district level, and several continue with teacher training after completing Level 10.
              </p>
              <p className="text-muted-foreground leading-relaxed mb-4">
                We keep lessons short, clear, and interactive. Regular practice sheets, weekly feedback, and monthly
                assessments help students track progress without pressure.
              </p>
              <p className="text-foreground font-medium leading-relaxed">
                At SIMPLE ABACUS, we are not just teaching maths -
                <br />
                We are shaping confident, focused, and future-ready students.
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
                src={placeholderImages.aboutHero}
                alt="Students learning at Simple Abacus Academy"
                className="relative w-full rounded-3xl shadow-2xl object-cover aspect-[4/3]"
                loading="lazy"
              />
            </motion.div>
          </div>
        </div>
      </section>

      {/* Stats */}
      <section className="py-12 bg-card border-b border-border">
        <div className="container mx-auto px-4">
          <div className="grid grid-cols-2 md:grid-cols-4 gap-6">
            {stats.map((s, i) => (
              <motion.div key={s.label} {...fadeUp} transition={{ delay: i * 0.1 }} className="text-center">
                <div className="text-3xl md:text-4xl font-heading font-bold text-secondary mb-1">{s.value}</div>
                <div className="text-sm text-muted-foreground">{s.label}</div>
              </motion.div>
            ))}
          </div>
        </div>
      </section>

      {/* Mission / Vision / Values */}
      <section className="py-20">
        <div className="container mx-auto px-4">
          <div className="grid md:grid-cols-3 gap-8 max-w-5xl mx-auto">
            {values.map((v, i) => (
              <motion.div key={v.title} {...fadeUp} transition={{ delay: i * 0.15 }} className="bg-card rounded-xl p-8 border border-border shadow-card text-center">
                <div className="w-14 h-14 rounded-xl gradient-accent flex items-center justify-center mx-auto mb-4">
                  <v.icon className="w-7 h-7 text-accent-foreground" />
                </div>
                <h3 className="text-xl font-heading font-bold text-foreground mb-3">{v.title}</h3>
                <p className="text-muted-foreground text-sm leading-relaxed">{v.desc}</p>
              </motion.div>
            ))}
          </div>
        </div>
      </section>

      {/* Founder Message */}
      <section className="py-20 bg-muted/40 border-y border-border">
        <div className="container mx-auto px-4">
          <div className="grid lg:grid-cols-2 gap-10 items-center max-w-6xl mx-auto">
            <motion.div {...fadeUp}>
              <span className="inline-block px-4 py-1.5 rounded-full bg-accent-soft text-secondary text-sm font-semibold mb-4">
                Founder Message
              </span>
              <p className="text-muted-foreground leading-relaxed mb-4">
                At SIMPLE ABACUS, our journey began with a simple vision - to remove the fear of Mathematics and build confident, capable students.
              </p>
              <p className="text-muted-foreground leading-relaxed mb-4">
                As an educator trained in Abacus and Vedic Mathematics, I have closely observed that many students struggle not because they lack ability, but because they lack the right learning method and guidance. This inspired me to start SIMPLE ABACUS.
              </p>
              <p className="text-muted-foreground leading-relaxed mb-3">
                With structured training, consistent practice, and a positive learning environment, I have seen students:
              </p>
              <ul className="list-disc pl-6 text-muted-foreground space-y-1 mb-4">
                <li>Improve concentration and focus</li>
                <li>Increase calculation speed dramatically</li>
                <li>Develop strong memory power</li>
                <li>Gain confidence in academics</li>
              </ul>
              <p className="text-muted-foreground leading-relaxed mb-4">
                My teaching approach focuses on clarity, patience, and personal attention to every child. I believe that when children enjoy learning, success naturally follows.
              </p>
              <p className="text-foreground font-medium leading-relaxed mb-6">
                At SIMPLE ABACUS, we are not just teaching numbers -
                <br />
                We are developing sharper minds and brighter futures.
              </p>
              <div className="text-foreground">
                <p className="font-semibold">Warm Regards,</p>
                <p className="font-semibold">Varsha Shinde</p>
                <p className="text-muted-foreground">Founder - SIMPLE ABACUS</p>
                <p className="text-muted-foreground">Manjari Budruk, Pune</p>
              </div>
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
                src={placeholderImages.aboutFounder}
                alt="Founder - Varsha Shinde"
                className="relative w-full rounded-3xl shadow-2xl object-cover aspect-[4/5] bg-card"
                loading="lazy"
              />
            </motion.div>
          </div>
        </div>
      </section>

      {/* Team */}
      <section className="py-20">
        <div className="container mx-auto px-4">
          <motion.div {...fadeUp} className="text-center mb-14">
            <span className="inline-block px-4 py-1.5 rounded-full bg-accent-soft text-secondary text-sm font-semibold mb-4">Leadership</span>
            <h2 className="text-3xl md:text-4xl font-heading font-bold text-foreground">Meet Our <span className="text-gradient">Team</span></h2>
          </motion.div>
          <div className="grid md:grid-cols-3 gap-8 max-w-4xl mx-auto">
            {team.map((t, i) => (
              <motion.div key={t.name} {...fadeUp} transition={{ delay: i * 0.15 }} className="bg-card rounded-xl p-6 border border-border shadow-card text-center">
                <div className="w-20 h-20 rounded-full bg-muted flex items-center justify-center mx-auto mb-4">
                  <Users className="w-8 h-8 text-muted-foreground" />
                </div>
                <h4 className="font-heading font-bold text-foreground">{t.name}</h4>
                <div className="text-sm text-secondary font-medium mb-2">{t.role}</div>
                <p className="text-xs text-muted-foreground">{t.desc}</p>
              </motion.div>
            ))}
          </div>
        </div>
      </section>
    </main>
    <Footer />
  </div>
);

export default About;
