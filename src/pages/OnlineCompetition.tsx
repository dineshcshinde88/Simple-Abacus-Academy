import { Link } from "react-router-dom";
import Navbar from "@/components/layout/Navbar";
import Footer from "@/components/layout/Footer";
import { Button } from "@/components/ui/button";
import { Award, BookOpenCheck, CreditCard, ShieldCheck, Trophy, Users } from "lucide-react";

const features = [
  { icon: Users, title: "Student Registration", text: "Register for upcoming competitions and wait for admin approval." },
  { icon: CreditCard, title: "Competition Access", text: "Purchase eligible competition access after approval." },
  { icon: BookOpenCheck, title: "Practice Kits", text: "Prepare with PDFs, MCQs, videos, and mock tests before the exam." },
  { icon: Trophy, title: "Leaderboard", text: "View rankings by score, accuracy, and completion time." },
];

const OnlineCompetition = () => (
  <div className="min-h-screen bg-white">
    <Navbar />
    <main className="pt-32 md:pt-36">
      <section className="bg-slate-950 text-white">
        <div className="container mx-auto grid gap-10 px-4 py-14 lg:grid-cols-[1.05fr_0.95fr] lg:items-center">
          <div>
            <div className="inline-flex items-center gap-2 rounded-full bg-white/10 px-4 py-2 text-sm font-semibold text-orange-200">
              <Award className="h-4 w-4" /> Online Competition Platform
            </div>
            <h1 className="mt-6 max-w-3xl text-4xl font-heading font-bold tracking-normal md:text-5xl">
              Compete, practice, and track results in one secure exam portal.
            </h1>
            <p className="mt-5 max-w-2xl text-base leading-7 text-slate-300">
              Students can register for Simple Abacus competitions, purchase access, prepare with practice kits, attempt timed exams, and view published results.
            </p>
            <div className="mt-8 flex flex-col gap-3 sm:flex-row">
              <Button className="bg-orange-500 hover:bg-orange-600 text-white" asChild>
                <Link to="/student-registration">Register Now</Link>
              </Button>
              <Button variant="outline" className="border-white/30 bg-white/10 text-white hover:bg-white hover:text-slate-950" asChild>
                <Link to="/student-login">Student Login</Link>
              </Button>
            </div>
          </div>

          <div className="rounded-lg border border-white/10 bg-white/5 p-5">
            <div className="grid gap-3 sm:grid-cols-2">
              {[
                ["Upcoming", "Competitions"],
                ["Timed", "Online Exams"],
                ["Instant", "Scores"],
                ["Live", "Leaderboard"],
              ].map(([value, label]) => (
                <div key={label} className="rounded-lg bg-white p-5 text-slate-950">
                  <p className="text-2xl font-bold">{value}</p>
                  <p className="mt-1 text-sm text-slate-500">{label}</p>
                </div>
              ))}
            </div>
          </div>
        </div>
      </section>

      <section className="container mx-auto px-4 py-12">
        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
          {features.map((item) => (
            <div key={item.title} className="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
              <item.icon className="h-7 w-7 text-[#5b21b6]" />
              <h2 className="mt-4 text-lg font-bold text-slate-900">{item.title}</h2>
              <p className="mt-2 text-sm leading-6 text-slate-600">{item.text}</p>
            </div>
          ))}
        </div>

        <div className="mt-10 rounded-lg border border-emerald-200 bg-emerald-50 p-5">
          <div className="flex items-start gap-3">
            <ShieldCheck className="mt-0.5 h-5 w-5 text-emerald-700" />
            <div>
              <h2 className="font-bold text-emerald-950">Secure exam access</h2>
              <p className="mt-1 text-sm text-emerald-800">
                Admin approval, paid access, timer-based attempts, answer autosave, and leaderboard publishing are supported in the competition module structure.
              </p>
            </div>
          </div>
        </div>
      </section>
    </main>
    <Footer />
  </div>
);

export default OnlineCompetition;
