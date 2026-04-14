import Navbar from "@/components/layout/Navbar";
import Footer from "@/components/layout/Footer";
import { Button } from "@/components/ui/button";
import { Sparkles } from "lucide-react";
import { Link } from "react-router-dom";

const teachers = [
  {
    name: "Poonam Yuvraj Gavhane",
    qualification: "Certified Abacus Trainer",
    experience: "5+ Years Experience",
    location: "Pune, Maharashtra",
    specialization: "Abacus",
    image: "/assets/teachers/poonam-gavhane.png",
    description:
      "Patient, structured instruction that builds number sense, focus, and confident mental math habits.",
  },
  {
    name: "Mahanthi Kamini Devi",
    qualification: "Certified Abacus Trainer",
    experience: "6+ Years Experience",
    location: "Thane, Maharashtra",
    specialization: "Abacus",
    image: "/assets/teachers/mahanthi-kamini-devi.png",
    description:
      "Known for engaging classes and step-by-step guidance that keeps learners motivated and consistent.",
  },
  {
    name: "Nayana Uday Patil",
    qualification: "Certified Abacus Trainer",
    experience: "4+ Years Experience",
    location: "Pune, Maharashtra",
    specialization: "Abacus",
    image: "/assets/teachers/nayana-uday-patil.png",
    description:
      "Focuses on accuracy, speed, and confidence with child-friendly teaching and regular feedback.",
  },
  {
    name: "Ashvini Balu Talekar",
    qualification: "Certified Abacus Trainer",
    experience: "5+ Years Experience",
    location: "Pune, Maharashtra",
    specialization: "Abacus",
    image: "/assets/teachers/ashvini-balu-talekar.png",
    description:
      "Encouraging mentor who blends fun practice with clear fundamentals and personalized attention.",
  },
];

const highlights = [
  {
    title: "Experienced Trainers",
    desc: "Years of hands-on classroom expertise guiding learners from basics to mastery.",
  },
  {
    title: "Child-Friendly Methods",
    desc: "Positive, engaging sessions that keep children curious and confident.",
  },
  {
    title: "Personalized Attention",
    desc: "Small-batch focus with regular feedback and progress tracking.",
  },
  {
    title: "Certified Professionals",
    desc: "Trainers who are qualified and committed to quality outcomes.",
  },
];

const Teachers = () => (
  <div className="min-h-screen bg-white">
    <Navbar />
    <main className="pt-16">
      <section className="relative overflow-hidden">
        <div className="absolute inset-0 bg-gradient-to-br from-[#eaf4ff] via-white to-[#fff4e6]" />
        <div className="absolute -right-24 -top-24 h-64 w-64 rounded-full bg-[#f97316]/10 blur-3xl" />
        <div className="absolute -left-20 bottom-0 h-64 w-64 rounded-full bg-[#0ea5e9]/10 blur-3xl" />
        <div className="relative container mx-auto px-4 py-20 md:py-24 text-center">
          <span className="inline-flex items-center gap-2 rounded-full bg-white/80 px-4 py-1 text-sm font-semibold text-[#0f5132] shadow-sm">
            <Sparkles className="h-4 w-4 text-[#f97316]" />
            Simple Abacus Academy
          </span>
          <h1 className="mt-5 text-4xl md:text-5xl font-heading font-bold text-[#0f172a]">
            Meet Our Expert Teachers
          </h1>
          <p className="mt-4 text-base md:text-lg text-slate-600 max-w-2xl mx-auto">
            Certified Abacus Trainers Dedicated to Your Child’s Growth
          </p>
        </div>
      </section>

      <section className="py-16 md:py-20">
        <div className="container mx-auto px-4">
          <div className="mx-auto max-w-6xl grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            {teachers.map((teacher) => (
              <article
                key={teacher.name}
                className="group bg-white border border-slate-200 rounded-2xl overflow-hidden shadow-[0_10px_30px_rgba(15,23,42,0.08)] transition-transform duration-300 hover:-translate-y-2 max-w-sm mx-auto w-full"
              >
                <div className="relative">
                  <img
                    src={teacher.image}
                    alt={teacher.name}
                    className="h-72 w-full object-contain bg-[#eef5ff]"
                    loading="lazy"
                  />
                  <div className="absolute left-4 bottom-4 rounded-full bg-white/90 px-3 py-1 text-xs font-semibold text-[#0f5132] shadow-sm">
                    {teacher.specialization}
                  </div>
                </div>
                <div className="p-6">
                  <h3 className="text-xl font-heading font-bold text-[#0f172a]">
                    {teacher.name}
                  </h3>
                  <p className="text-sm font-medium text-[#0f5132] mt-1">
                    {teacher.qualification}
                  </p>
                  <p className="text-sm text-slate-500 mt-1">
                    {teacher.experience} • {teacher.location}
                  </p>
                  <p className="mt-4 text-sm text-slate-600 leading-relaxed">
                    {teacher.description}
                  </p>
                  <div className="mt-6 h-1 w-12 rounded-full bg-[#f97316]" aria-hidden="true" />
                </div>
              </article>
            ))}
          </div>
        </div>
      </section>

      <section className="py-16 bg-[#f8fafc]">
        <div className="container mx-auto px-4">
          <div className="text-center max-w-2xl mx-auto">
            <h2 className="text-3xl md:text-4xl font-heading font-bold text-[#0f172a]">
              Why Our Teachers?
            </h2>
            <p className="mt-3 text-slate-600">
              A dedicated team that blends experience, warmth, and proven methods to help every child excel.
            </p>
          </div>
          <div className="mt-10 grid gap-6 md:grid-cols-2 lg:grid-cols-4">
            {highlights.map((item) => (
              <div key={item.title} className="bg-white rounded-xl border border-slate-200 p-6 shadow-sm">
                <h3 className="text-lg font-heading font-bold text-[#0f5132] mb-2">{item.title}</h3>
                <p className="text-sm text-slate-600 leading-relaxed">{item.desc}</p>
              </div>
            ))}
          </div>
        </div>
      </section>

      <section className="py-16 md:py-20">
        <div className="container mx-auto px-4">
          <div className="rounded-3xl bg-gradient-to-r from-[#0ea5e9] via-[#38bdf8] to-[#f97316] p-10 md:p-14 text-white shadow-xl">
            <div className="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
              <div>
                <h2 className="text-3xl md:text-4xl font-heading font-bold">Ready to Learn with the Best?</h2>
                <p className="mt-3 text-white/90 max-w-xl">
                  Join Simple Abacus Academy and experience expert-led learning that builds speed, focus, and confidence.
                </p>
              </div>
              <div className="flex flex-wrap gap-3">
                <Button size="lg" className="bg-white text-[#0f5132] hover:bg-white/90" asChild>
                  <Link to="/programs">Enroll Now</Link>
                </Button>
                <Button size="lg" variant="outline" className="border-white text-white hover:bg-white/10" asChild>
                  <Link to="/teacher-training">Become a Teacher</Link>
                </Button>
              </div>
            </div>
          </div>
        </div>
      </section>
    </main>
    <Footer />
  </div>
);

export default Teachers;
