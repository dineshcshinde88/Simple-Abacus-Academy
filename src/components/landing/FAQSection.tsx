import { useState } from "react";
import { Minus, Plus } from "lucide-react";
import { AnimatePresence, motion } from "framer-motion";

const faqs = [
  {
    q: "What age group is this program suitable for?",
    a: "Our abacus program is designed for children aged 6 to 14 years. The curriculum is divided into levels that match different age groups and skill levels.",
  },
  {
    q: "How many classes are there each week?",
    a: "Most batches meet 1-2 times per week for 60 minutes. We help you choose a pace based on your child's age and school schedule.",
  },
  {
    q: "Is the program online or offline?",
    a: "We offer both. You can join from home with live online classes or attend at a nearby center, depending on your location.",
  },
  {
    q: "Do students need a physical abacus?",
    a: "Yes for the first levels. Beginners learn on a physical abacus and gradually transition to mental visualization as they progress.",
  },
  {
    q: "What is the average class size?",
    a: "Batches are small so each child gets attention. Typical groups have 8-12 students.",
  },
  {
    q: "Can my child practice at home?",
    a: "Absolutely. Students get practice sheets and access to our online practice engine for timed drills.",
  },
  {
    q: "How long does it take to see results?",
    a: "Most parents notice better focus and confidence within a few months. Speed and accuracy improve steadily with practice.",
  },
  {
    q: "Are there exams and certifications?",
    a: "Yes. We conduct level tests and provide report cards. Certificates are issued after successful completion of each level.",
  },
  {
    q: "What if my child misses a class?",
    a: "We share practice material and try to arrange a revision class when possible, so your child can catch up easily.",
  },
  {
    q: "How do I enroll my child?",
    a: "Click 'Book Free Demo' to schedule a trial session. After the demo, you can register your child through our online form. An admin will approve the registration.",
  },
];

const FAQSection = () => {
  const [openIndex, setOpenIndex] = useState<number | null>(0);

  return (
    <section className="py-20 bg-muted/50">
      <div className="container mx-auto px-4">
        <div className="grid gap-10 lg:grid-cols-[0.95fr_1.05fr] lg:items-start">
          <motion.div
            initial={{ opacity: 0, y: 20 }}
            whileInView={{ opacity: 1, y: 0 }}
            viewport={{ once: true }}
            className="lg:sticky lg:top-24"
          >
            <span className="inline-flex items-center gap-2 rounded-full bg-accent-soft px-4 py-1.5 text-sm font-semibold text-secondary">
              FAQ
              <span className="h-1.5 w-1.5 rounded-full bg-secondary/70" />
              Student Support
            </span>
            <h2 className="mt-5 text-3xl font-heading font-bold text-foreground md:text-4xl">
              Fast answers for parents, students, and educators.
            </h2>
            <p className="mt-4 text-base leading-relaxed text-muted-foreground">
              Everything you need to feel confident before enrolling. We keep responses short, clear, and focused on
              real learning outcomes.
            </p>
            <div className="mt-8 flex flex-wrap gap-3 text-xs font-semibold uppercase tracking-wide text-secondary/70">
              <span className="rounded-full border border-border bg-card px-3 py-1.5">Flexible learning</span>
              <span className="rounded-full border border-border bg-card px-3 py-1.5">Certified teachers</span>
              <span className="rounded-full border border-border bg-card px-3 py-1.5">Weekly practice</span>
            </div>
          </motion.div>

          <div className="space-y-4">
            {faqs.map((faq, index) => {
              const isOpen = openIndex === index;

              return (
                <div
                  key={faq.q}
                  className="rounded-2xl border border-border bg-card px-5 py-4 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-card"
                >
                  <button
                    type="button"
                    onClick={() => setOpenIndex(isOpen ? null : index)}
                    className="flex w-full items-center justify-between gap-4 text-left font-heading text-sm font-semibold text-foreground md:text-base"
                    aria-expanded={isOpen}
                    aria-controls={`faq-panel-${index}`}
                  >
                    <span>{faq.q}</span>
                    <span className="flex h-9 w-9 items-center justify-center rounded-full border border-border bg-muted/60 text-secondary transition-colors">
                      {isOpen ? <Minus className="h-4 w-4" /> : <Plus className="h-4 w-4" />}
                    </span>
                  </button>

                  <AnimatePresence initial={false}>
                    {isOpen && (
                      <motion.div
                        id={`faq-panel-${index}`}
                        initial={{ height: 0, opacity: 0 }}
                        animate={{ height: "auto", opacity: 1 }}
                        exit={{ height: 0, opacity: 0 }}
                        transition={{ duration: 0.25, ease: "easeOut" }}
                        className="overflow-hidden"
                      >
                        <p className="pt-3 text-sm leading-relaxed text-muted-foreground md:text-base">
                          {faq.a}
                        </p>
                      </motion.div>
                    )}
                  </AnimatePresence>
                </div>
              );
            })}
          </div>
        </div>
      </div>
    </section>
  );
};

export default FAQSection;
