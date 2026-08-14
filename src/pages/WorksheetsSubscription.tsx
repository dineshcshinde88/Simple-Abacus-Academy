import { BookOpenCheck, Brain, CheckCircle2, Sparkles } from "lucide-react";
import { Link } from "react-router-dom";
import Navbar from "@/components/layout/Navbar";
import Footer from "@/components/layout/Footer";
import { Button } from "@/components/ui/button";
import { placeholderImages } from "@/data/placeholderImages";

const categories = [
  {
    title: "Abacus Worksheets",
    description: "Level 0 (Foundation) to Level 7 practice with questions, practice mode, and progress tracking.",
    image: placeholderImages.moduleSection,
    href: "/abacus-worksheet-subscription",
    levels: ["Level 0 (Foundation)", "Level 1", "Level 2", "Level 3", "Level 4", "Level 5", "Level 6", "Level 7"],
    icon: Brain,
    accent: "from-[#4B1E83] via-[#6f2dbd] to-orange-500",
  },
  {
    title: "Vedic Maths Worksheets",
    description: "Level 1 to Level 4 speed maths worksheets with mental maths tricks and timed practice.",
    image: placeholderImages.vedicMathsHero,
    href: "/vedic-maths-worksheet-subscription",
    levels: ["Level 1", "Level 2", "Level 3", "Level 4"],
    icon: Sparkles,
    accent: "from-orange-500 via-[#6f2dbd] to-[#4B1E83]",
  },
];

const WorksheetsSubscription = () => (
  <div className="min-h-screen bg-background">
    <Navbar />
    <main className="pt-16">
      <section className="bg-[#f7f3fb] py-14">
        <div className="container mx-auto px-4">
          <div className="mx-auto max-w-3xl text-center">
            <div className="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-[#4B1E83] shadow-card">
              <BookOpenCheck className="h-6 w-6" />
            </div>
            <h1 className="mt-5 text-4xl font-heading font-bold text-[#4B1E83] md:text-5xl">Worksheet Subscription</h1>
            <p className="mt-4 text-base text-slate-600 md:text-lg">
              Choose Abacus or Vedic Maths, select a level, and unlock premium worksheet practice after secure payment.
            </p>
          </div>

          <div className="mt-10 grid gap-6 lg:grid-cols-2">
            {categories.map((category) => (
              <article key={category.title} className="overflow-hidden rounded-2xl bg-white shadow-card">
                <div className={`bg-gradient-to-br ${category.accent} p-5`}>
                  <div className="flex h-64 items-center justify-center overflow-hidden rounded-xl bg-white/95">
                    <img src={category.image} alt={category.title} className="h-full w-full object-contain" />
                  </div>
                </div>
                <div className="p-6">
                  <div className="flex items-start gap-3">
                    <div className="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-purple-50 text-[#4B1E83]">
                      <category.icon className="h-5 w-5" />
                    </div>
                    <div>
                      <h2 className="text-2xl font-heading font-bold text-[#4B1E83]">{category.title}</h2>
                      <p className="mt-2 text-sm text-slate-600">{category.description}</p>
                    </div>
                  </div>

                  <div className="mt-5 grid gap-2 sm:grid-cols-2">
                    {category.levels.map((level) => (
                      <Link
                        key={level}
                        to={`${category.href}?level=${encodeURIComponent(level)}`}
                        className="flex items-center justify-between rounded-xl border border-orange-200 px-4 py-3 text-sm font-semibold text-purple-700 transition hover:border-orange-500 hover:bg-orange-50"
                      >
                        <span>{level}</span>
                        <span>
                          <span className="text-xs text-red-500 line-through">Rs.199</span>{" "}
                          <span className="text-orange-500">Rs.99</span>
                        </span>
                      </Link>
                    ))}
                  </div>

                  <div className="mt-5 grid gap-3 rounded-xl bg-slate-50 p-4 text-sm text-slate-600 sm:grid-cols-2">
                    {["3 Months Rs.99", "1 Year Rs.199", "Secure Razorpay", "Purchased level access"].map((item) => (
                      <div key={item} className="flex items-center gap-2">
                        <CheckCircle2 className="h-4 w-4 text-emerald-600" />
                        {item}
                      </div>
                    ))}
                  </div>

                  <Button className="mt-6 w-full rounded-full bg-[#4B1E83] py-6 font-semibold hover:bg-[#3c176a]" asChild>
                    <Link to={category.href}>Subscribe Now</Link>
                  </Button>
                </div>
              </article>
            ))}
          </div>
        </div>
      </section>
    </main>
    <Footer />
  </div>
);

export default WorksheetsSubscription;
