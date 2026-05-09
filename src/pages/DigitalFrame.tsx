import Navbar from "@/components/layout/Navbar";
import Footer from "@/components/layout/Footer";
import { Button } from "@/components/ui/button";
import { CheckCircle2 } from "lucide-react";
import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from "@/components/ui/accordion";
import VirtualAbacusTool from "@/components/abacus/VirtualAbacusTool";

const DigitalFrame = () => {
  return (
    <div className="min-h-screen bg-background">
      <Navbar />
      <main className="pt-16">
        <VirtualAbacusTool />

        <section className="py-16 bg-white">
          <div className="container mx-auto px-4">
            <div className="grid gap-8 lg:grid-cols-[1.1fr_0.9fr] items-start">
              <div>
                <h2 className="text-2xl md:text-3xl font-heading font-bold text-foreground">
                  Digital abacus for practice, teaching, and learning
                </h2>
                <p className="mt-4 text-muted-foreground leading-relaxed">
                  This online abacus mirrors the traditional frame so learners can move beads and build number sense
                  on screen. Each bead position helps children understand place value, calculation flow, and mental math
                  confidence through repeated practice.
                </p>

                <h3 className="mt-8 text-xl font-semibold text-foreground">What is this virtual abacus tool?</h3>
                <p className="mt-3 text-muted-foreground leading-relaxed">
                  The digital frame is designed to replicate the structure of a physical abacus while making it easier
                  to practice anywhere. Students can slide beads with a mouse or touch to visualize quantities and
                  strengthen their understanding of number patterns.
                </p>

                <div className="mt-6 space-y-3">
                  {[
                    "Daily skill practice",
                    "Online abacus classes",
                    "Trainer demonstrations",
                    "Concept clarity before mental math",
                  ].map((item) => (
                    <div key={item} className="flex items-center gap-3 text-foreground">
                      <CheckCircle2 className="h-5 w-5 text-secondary" />
                      <span>{item}</span>
                    </div>
                  ))}
                </div>
              </div>

              <div className="rounded-2xl border border-border bg-[#fff7ed] p-6 shadow-sm">
                <h3 className="text-xl font-heading font-bold text-foreground">Using the digital abacus frame</h3>
                <p className="mt-3 text-muted-foreground leading-relaxed">
                  A clean, no-frills tool that helps learners of all ages practice counting, addition, and subtraction
                  with confidence.
                </p>
                <div className="mt-5 space-y-3 text-foreground">
                  <div>Slide beads with mouse or touch</div>
                  <div>Represent values on each rod</div>
                  <div>Practice addition and subtraction</div>
                  <div>Reset quickly and try again</div>
                </div>
                <p className="mt-5 text-muted-foreground leading-relaxed">
                  This simulator makes it easy to practice repeatedly without the limitations of a physical abacus.
                </p>
              </div>
            </div>
          </div>
        </section>

        <section className="py-16">
          <div className="container mx-auto px-4">
            <div className="grid gap-10 lg:grid-cols-[1.1fr_0.9fr] items-center">
              <div>
                <h2 className="text-2xl md:text-3xl font-heading font-bold text-foreground">
                  Why choose digital instead of a physical abacus?
                </h2>
                <p className="mt-4 text-muted-foreground leading-relaxed">
                  A digital frame offers the same learning advantages as a mechanical abacus, with added flexibility.
                  Here are the key benefits:
                </p>
                <div className="mt-6 space-y-3">
                  {[
                    "No physical tool required",
                    "Practice anytime, anywhere",
                    "Ideal for online classes",
                    "Quick reset and reuse",
                    "Clear visibility for kids",
                  ].map((item) => (
                    <div key={item} className="flex items-center gap-3 text-foreground">
                      <CheckCircle2 className="h-5 w-5 text-secondary" />
                      <span>{item}</span>
                    </div>
                  ))}
                </div>
                <p className="mt-6 text-muted-foreground">
                  This is one of the fastest and most convenient abacus tools for kids and teachers.
                </p>
              </div>
              <div className="overflow-hidden rounded-2xl border border-border bg-white shadow-card">
                <img
                  src="https://images.unsplash.com/photo-1509062522246-3755977927d7?q=80&w=1200&auto=format&fit=crop"
                  alt="Students practicing abacus"
                  className="h-full w-full object-cover"
                  loading="lazy"
                />
              </div>
            </div>
          </div>
        </section>

        <section className="py-16 bg-white">
          <div className="container mx-auto px-4">
            <div className="grid gap-10 lg:grid-cols-2">
              <div>
                <h2 className="text-2xl md:text-3xl font-heading font-bold text-foreground">
                  Who can use this abacus calculator online?
                </h2>
                <p className="mt-3 text-muted-foreground">This virtual abacus is ideal for:</p>
                <div className="mt-5 space-y-3">
                  {[
                    "Children who practice daily to build speed and focus",
                    "Parents who want to support learning at home",
                    "Trainers who teach in-person or online",
                    "Schools and colleges running digital classes",
                  ].map((item) => (
                    <div key={item} className="flex items-start gap-3 text-foreground">
                      <CheckCircle2 className="mt-0.5 h-5 w-5 text-secondary" />
                      <span>{item}</span>
                    </div>
                  ))}
                </div>
                <p className="mt-5 text-muted-foreground">
                  If you have been searching for an abacus tool online, this digital frame is ready in one click.
                </p>
              </div>

              <div>
                <h2 className="text-2xl md:text-3xl font-heading font-bold text-foreground">
                  Designed for online abacus classes
                </h2>
                <p className="mt-3 text-muted-foreground">
                  Built for virtual instruction, this digital abacus helps trainers deliver clear, interactive lessons.
                </p>
                <div className="mt-5 space-y-3">
                  {[
                    "Explain bead movement clearly",
                    "Run live demonstrations with ease",
                    "Teach number concepts visually",
                    "Guide students during practice",
                  ].map((item) => (
                    <div key={item} className="flex items-start gap-3 text-foreground">
                      <CheckCircle2 className="mt-0.5 h-5 w-5 text-secondary" />
                      <span>{item}</span>
                    </div>
                  ))}
                </div>
                <p className="mt-5 text-muted-foreground">
                  Students can join sessions directly and gain real-time practice that often works better than solo
                  home practice.
                </p>
              </div>
            </div>
          </div>
        </section>

        <section className="py-12">
          <div className="container mx-auto px-4">
            <div className="rounded-2xl border border-border bg-gradient-to-r from-[#4c1d95] via-[#7c3aed] to-[#f97316] p-8 text-white">
              <h2 className="text-2xl md:text-3xl font-heading font-bold">
                Interested in teaching professionally?
              </h2>
              <p className="mt-3 text-white/90">
                Join our training programs and discover flexible options for online and hybrid abacus classes.
              </p>
              <div className="mt-6 flex flex-wrap gap-3">
                <Button variant="hero-outline" asChild>
                  <a href="/online-abacus-classes">Join Abacus Classes</a>
                </Button>
                <Button variant="hero-outline" asChild>
                  <a href="/book-demo">Book a Free Demo</a>
                </Button>
                <Button variant="hero-outline" asChild>
                  <a href="/teacher-training">Become a Certified Trainer</a>
                </Button>
              </div>
            </div>
          </div>
        </section>

        <section className="py-16 bg-white">
          <div className="container mx-auto px-4">
            <div className="grid gap-10 lg:grid-cols-[1.1fr_0.9fr] items-start">
              <div>
                <h2 className="text-2xl md:text-3xl font-heading font-bold text-foreground">
                  Online tool for free abacus practice
                </h2>
                <p className="mt-3 text-muted-foreground">
                  Use this simulator for practice sessions, demonstrations, concept building, and speed drills.
                </p>
                <div className="mt-5 space-y-3">
                  {[
                    "Practice sessions anytime",
                    "Demonstration for classes",
                    "Concept clarity and revision",
                    "Speed building routines",
                  ].map((item) => (
                    <div key={item} className="flex items-center gap-3 text-foreground">
                      <CheckCircle2 className="h-5 w-5 text-secondary" />
                      <span>{item}</span>
                    </div>
                  ))}
                </div>
                <p className="mt-5 text-muted-foreground">
                  Learners can progress level by level and strengthen fundamentals with consistent practice.
                </p>
              </div>
              <div className="rounded-2xl border border-border bg-card p-6 shadow-card">
                <h3 className="text-xl font-heading font-bold text-foreground">Digital vs Physical Abacus</h3>
                <div className="mt-4 grid gap-4 md:grid-cols-2 text-sm">
                  <div className="rounded-xl border border-border bg-white p-4">
                    <div className="font-semibold text-foreground mb-2">Digital Tool</div>
                    <ul className="space-y-2 text-muted-foreground">
                      <li>Available online</li>
                      <li>Instant reset</li>
                      <li>Great for online classes</li>
                      <li>No wear and tear</li>
                    </ul>
                  </div>
                  <div className="rounded-xl border border-border bg-white p-4">
                    <div className="font-semibold text-foreground mb-2">Physical Abacus</div>
                    <ul className="space-y-2 text-muted-foreground">
                      <li>Must be carried</li>
                      <li>Manual reset</li>
                      <li>Mostly classroom-based</li>
                      <li>Can wear out</li>
                    </ul>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </section>

        <section className="py-16">
          <div className="container mx-auto px-4">
            <div className="grid gap-10 lg:grid-cols-2">
              <div>
                <h2 className="text-2xl md:text-3xl font-heading font-bold text-foreground">
                  What is this online abacus tool for?
                </h2>
                <p className="mt-3 text-muted-foreground">
                  It is a learning aid, not a calculator. Use it to build number representation and calculation flow.
                </p>
                <div className="mt-5 space-y-3">
                  {[
                    "Learning number representation",
                    "Practicing abacus calculations",
                    "Supporting online classes",
                    "Trainer-led demonstrations",
                    "Daily home practice",
                  ].map((item) => (
                    <div key={item} className="flex items-center gap-3 text-foreground">
                      <CheckCircle2 className="h-5 w-5 text-secondary" />
                      <span>{item}</span>
                    </div>
                  ))}
                </div>
              </div>
              <div>
                <h2 className="text-2xl md:text-3xl font-heading font-bold text-foreground">
                  Working principle of the digital frame
                </h2>
                <div className="mt-5 space-y-3 text-muted-foreground">
                  <p>Each rod represents a place value (ones, tens, hundreds, etc.).</p>
                  <p>Beads move up and down toward the center bar to form values.</p>
                  <p>Numbers are formed visually, so learners see the process—not just the answer.</p>
                </div>
              </div>
            </div>
          </div>
        </section>

        <section className="py-16 bg-white">
          <div className="container mx-auto px-4">
            <div className="grid gap-10 lg:grid-cols-2">
              <div>
                <h2 className="text-2xl md:text-3xl font-heading font-bold text-foreground">
                  When to use this abacus tool
                </h2>
                <div className="mt-5 flex flex-wrap gap-3">
                  {["Live classes", "Concept explanation", "Daily practice", "Before tests", "Teaching aid"].map((item) => (
                    <span key={item} className="rounded-full border border-border bg-white px-4 py-2 text-sm">
                      {item}
                    </span>
                  ))}
                </div>
                <p className="mt-4 text-muted-foreground">
                  Works on desktop, tablet, and mobile browsers with no installation required.
                </p>
              </div>
              <div>
                <h2 className="text-2xl md:text-3xl font-heading font-bold text-foreground">
                  Who this digital abacus is for
                </h2>
                <div className="mt-5 space-y-3">
                  {[
                    "Students from beginner to advanced levels",
                    "Parents assisting children at home",
                    "Abacus teachers (online or offline)",
                    "Schools and coaching institutes",
                  ].map((item) => (
                    <div key={item} className="flex items-center gap-3 text-foreground">
                      <CheckCircle2 className="h-5 w-5 text-secondary" />
                      <span>{item}</span>
                    </div>
                  ))}
                </div>
                <p className="mt-4 text-muted-foreground">
                  Digital access removes the challenge of finding a physical abacus nearby.
                </p>
              </div>
            </div>
          </div>
        </section>

        <section className="py-16">
          <div className="container mx-auto px-4">
            <div className="grid gap-10 lg:grid-cols-[1.2fr_0.8fr] items-start">
              <div>
                <h2 className="text-2xl md:text-3xl font-heading font-bold text-foreground">
                  Free access to the abacus tool
                </h2>
                <p className="mt-3 text-muted-foreground">
                  Use it for practice, demonstrations, and concept clarity without any setup.
                </p>
                <div className="mt-5 space-y-3">
                  {["Practice sessions", "Demonstrations", "Concept clarity"].map((item) => (
                    <div key={item} className="flex items-center gap-3 text-foreground">
                      <CheckCircle2 className="h-5 w-5 text-secondary" />
                      <span>{item}</span>
                    </div>
                  ))}
                </div>
                <p className="mt-4 text-muted-foreground">
                  For structured learning and tracking progress, explore our classes and worksheets.
                </p>
              </div>
              <div className="rounded-2xl border border-border bg-card p-6 shadow-card">
                <h3 className="text-xl font-heading font-bold text-foreground">Other learning tools</h3>
                <div className="mt-4 space-y-2 text-muted-foreground">
                  <div>Franchise inquiry</div>
                  <div>Student inquiry</div>
                  <div>Competition conductor</div>
                  <div>School inquiry</div>
                  <div>Worksheet subscription</div>
                </div>
              </div>
            </div>
          </div>
        </section>

        <section className="py-16 bg-white">
          <div className="container mx-auto px-4">
            <div className="grid gap-10 lg:grid-cols-[1.1fr_0.9fr] items-start">
              <div>
                <h2 className="text-2xl md:text-3xl font-heading font-bold text-foreground">
                  For trainers & institutions
                </h2>
                <div className="mt-5 space-y-3">
                  {[
                    "Demonstrate calculations clearly",
                    "Teach students in online batches",
                    "Support hybrid learning models",
                  ].map((item) => (
                    <div key={item} className="flex items-center gap-3 text-foreground">
                      <CheckCircle2 className="h-5 w-5 text-secondary" />
                      <span>{item}</span>
                    </div>
                  ))}
                </div>
                <p className="mt-4 text-muted-foreground">
                  If you want to teach professionally, explore our trainer certification and partner programs.
                </p>
              </div>
              <div className="rounded-2xl border border-border bg-white p-6 shadow-card">
                <h3 className="text-xl font-heading font-bold text-foreground">Continue learning</h3>
                <div className="mt-4 space-y-2">
                  <a className="text-primary hover:underline" href="/teacher-training">Abacus Trainer Certification Program</a>
                  <a className="text-primary hover:underline" href="/franchise">Franchise & Partner Opportunities</a>
                </div>
              </div>
            </div>
          </div>
        </section>

        <section className="py-16">
          <div className="container mx-auto px-4 max-w-4xl">
            <div className="text-center mb-10">
              <h2 className="text-2xl md:text-3xl font-heading font-bold text-foreground">
                Frequently Asked Questions
              </h2>
              <p className="mt-2 text-muted-foreground">Quick answers about the digital abacus tool.</p>
            </div>
            <Accordion type="single" collapsible className="w-full">
              {[
                {
                  q: "What is an abacus tool?",
                  a: "An abacus is a counting device used to learn arithmetic and practice calculations.",
                },
                {
                  q: "Is this online tool free?",
                  a: "Yes. The digital frame is free to use for practice and demonstrations.",
                },
                {
                  q: "Can it replace a physical abacus?",
                  a: "It offers the same learning benefits with added convenience for online use.",
                },
                {
                  q: "Does it work on mobile devices?",
                  a: "Yes. It runs on mobile and desktop browsers without installation.",
                },
                {
                  q: "Is it suitable for beginners?",
                  a: "Absolutely. It helps beginners learn bead movement and number representation.",
                },
                {
                  q: "Can trainers use it in classes?",
                  a: "Yes. It is useful for both online and offline instruction.",
                },
                {
                  q: "Is it helpful for exams and competitions?",
                  a: "Yes. It strengthens concepts and supports focused practice before tests.",
                },
                {
                  q: "Do I need to install anything?",
                  a: "No. It is fully online and requires no installation.",
                },
                {
                  q: "What age is it appropriate for?",
                  a: "It is suitable for children aged four years and above.",
                },
                {
                  q: "What is the best way to continue structured learning?",
                  a: "Join online classes or use unlimited practice worksheets for guided progress.",
                },
              ].map((item, idx) => (
                <AccordionItem key={item.q} value={`faq-${idx}`}>
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
        </section>
      </main>
      <Footer />
    </div>
  );
};

export default DigitalFrame;



