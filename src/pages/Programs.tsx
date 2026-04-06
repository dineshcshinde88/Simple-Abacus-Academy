import Navbar from "@/components/layout/Navbar";
import Footer from "@/components/layout/Footer";
import { motion } from "framer-motion";
import { Button } from "@/components/ui/button";
import { Link } from "react-router-dom";
import { ArrowRight, Star, CheckCircle, Clock, BookOpen, Trophy } from "lucide-react";

const levels = [
  {
    range: "Level 1-3",
    title: "Beginner",
    age: "6-8 years",
    duration: "6 months",
    desc: "Build the base with a physical abacus. Students learn bead concepts, number sense, and accurate addition and subtraction.",
    features: [
      "Abacus setup and finger movement",
      "Single and double digit operations",
      "Number sense and place value",
      "Daily speed drills",
      "Confidence building activities",
    ],
    color: "border-info/30 bg-info/5",
  },
  {
    range: "Level 4-6",
    title: "Intermediate",
    age: "8-11 years",
    duration: "9 months",
    popular: true,
    desc: "Move from physical to mental abacus. Students improve speed, accuracy, and confidence with multi-digit calculations.",
    features: [
      "Mental image practice and accuracy",
      "Multi-digit multiplication and division",
      "Speed drills (3-6 sec per problem)",
      "Weekly worksheets and feedback",
      "Monthly progress tests",
    ],
    color: "border-secondary/40 bg-secondary/5",
  },
  {
    range: "Level 7-10",
    title: "Advanced",
    age: "11-14 years",
    duration: "12 months",
    desc: "Master high-speed mental maths and real exam readiness. Ideal for students who enjoy challenges and competitions.",
    features: [
      "Complex multi-step calculations",
      "Exam and competition practice",
      "Advanced speed and accuracy drills",
      "Performance tracking and mentoring",
      "Certification after each level",
    ],
    color: "border-success/30 bg-success/5",
  },
];

const extras = [
  { icon: Clock, title: "Flexible Scheduling", desc: "Weekday and weekend batches with online or offline options." },
  { icon: BookOpen, title: "Practice Engine", desc: "Unlimited timed questions with easy, medium, and fast modes." },
  { icon: Trophy, title: "Exams and Certification", desc: "Level exams, report cards, and certificates after completion." },
];

const fadeUp = { initial: { opacity: 0, y: 20 }, whileInView: { opacity: 1, y: 0 }, viewport: { once: true } };

const Programs = () => (
  <div className="min-h-screen">
    <Navbar />
    <main className="pt-16">
      {/* Hero */}
      <section className="gradient-hero py-20 md:py-28">
        <div className="container mx-auto px-4 text-center">
          <motion.h1 {...fadeUp} className="text-4xl md:text-5xl font-heading font-bold text-primary-foreground mb-4">
            Courses and <span className="text-gradient">Syllabus</span>
          </motion.h1>
          <motion.p {...fadeUp} transition={{ delay: 0.1 }} className="text-primary-foreground/80 max-w-2xl mx-auto text-lg">
            A structured 10-level curriculum for ages 6-14, designed to move students from basics to advanced mental maths.
          </motion.p>
        </div>
      </section>

      {/* Levels */}
      <section className="py-20">
        <div className="container mx-auto px-4 space-y-10 max-w-4xl">
          {levels.map((l, i) => (
            <motion.div
              key={l.title}
              {...fadeUp}
              transition={{ delay: i * 0.15 }}
              className={`relative bg-card rounded-2xl p-8 border-2 ${l.color} shadow-card`}
            >
              {l.popular && (
                <div className="absolute -top-3 left-6 px-3 py-1 rounded-full gradient-accent text-accent-foreground text-xs font-bold flex items-center gap-1">
                  <Star className="w-3 h-3" /> Most Popular
                </div>
              )}
              <div className="flex flex-col md:flex-row md:items-start gap-6">
                <div className="flex-1">
                  <div className="text-xs font-semibold text-muted-foreground uppercase tracking-wider mb-1">{l.range}</div>
                  <h3 className="text-2xl font-heading font-bold text-foreground mb-1">{l.title}</h3>
                  <div className="flex items-center gap-3 mb-3">
                    <span className="text-sm text-secondary font-medium">Ages {l.age}</span>
                    <span className="text-sm text-muted-foreground">- {l.duration}</span>
                  </div>
                  <p className="text-muted-foreground mb-4">{l.desc}</p>
                  <ul className="space-y-2">
                    {l.features.map((f) => (
                      <li key={f} className="flex items-center gap-2 text-sm text-foreground">
                        <CheckCircle className="w-4 h-4 text-success shrink-0" /> {f}
                      </li>
                    ))}
                  </ul>
                </div>
                <div className="md:self-center">
                  <Button variant={l.popular ? "hero" : "outline"} asChild>
                    <Link to="/book-demo">Enroll Now <ArrowRight className="w-4 h-4" /></Link>
                  </Button>
                </div>
              </div>
            </motion.div>
          ))}
        </div>
      </section>

      {/* Extras */}
      <section className="py-20 bg-muted/50">
        <div className="container mx-auto px-4">
          <motion.div {...fadeUp} className="text-center mb-14">
            <h2 className="text-3xl md:text-4xl font-heading font-bold text-foreground">What's <span className="text-gradient">Included</span></h2>
          </motion.div>
          <div className="grid md:grid-cols-3 gap-8 max-w-4xl mx-auto">
            {extras.map((e, i) => (
              <motion.div key={e.title} {...fadeUp} transition={{ delay: i * 0.15 }} className="bg-card rounded-xl p-6 border border-border shadow-card text-center">
                <div className="w-12 h-12 rounded-xl gradient-accent flex items-center justify-center mx-auto mb-4">
                  <e.icon className="w-6 h-6 text-accent-foreground" />
                </div>
                <h4 className="font-heading font-bold text-foreground mb-2">{e.title}</h4>
                <p className="text-sm text-muted-foreground">{e.desc}</p>
              </motion.div>
            ))}
          </div>
        </div>
      </section>
    </main>
    <Footer />
  </div>
);

export default Programs;
