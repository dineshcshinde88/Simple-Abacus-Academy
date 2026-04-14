import { Link } from "react-router-dom";
import { Button } from "@/components/ui/button";
import abacusStudentsImage from "@/assets/modules/abacus-for-students.png";
import vedicStudentsImage from "@/assets/modules/vedic-maths-for-students.png";
import instructorsImage from "@/assets/modules/abacus-vedic-instructors.png";

const modules = [
  {
    title: "Abacus for Students",
    body: "Learn the fundamentals of abacus in a fun way!",
    cta: "Get a Free Demo",
    tone: "orange",
    image: abacusStudentsImage,
    to: "/book-demo",
  },
  {
    title: "Vedic Maths for Students",
    body: "Master quick calculations with Vedic methods!",
    cta: "Get a Free Demo",
    tone: "purple",
    image: vedicStudentsImage,
    to: "/book-demo",
  },
  {
    title: "Abacus & Vedic Maths for Instructors",
    body: "Join us to teach and inspire future abacus learners!",
    cta: "Join with Us",
    tone: "orange",
    image: instructorsImage,
    to: "/teacher-training",
  },
];

const ModuleSection = () => (
  <section className="py-16">
    <div className="container mx-auto px-4">
      <div className="text-center">
        <h2 className="text-3xl md:text-4xl font-heading font-extrabold text-primary">
          Our Module
        </h2>
      </div>

      <div className="mt-10 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
        {modules.map((module) => (
          <div
            key={module.title}
            className="rounded-2xl border border-muted bg-white p-5 shadow-card flex flex-col"
          >
            <div className="overflow-hidden rounded-xl bg-slate-50">
              <img
                src={module.image}
                alt={module.title}
                className="h-56 md:h-64 w-full object-contain"
                loading="lazy"
              />
            </div>
            <h3 className="mt-5 text-lg font-semibold text-primary text-center">
              {module.title}
            </h3>
            <p className="mt-3 text-sm text-muted-foreground text-center">{module.body}</p>
            <div className="mt-auto pt-5 flex justify-center">
              <Button
                className={
                  module.tone === "purple"
                    ? "bg-[#4b1688] text-white hover:bg-[#3b136b]"
                    : ""
                }
                variant={module.tone === "orange" ? "hero" : "default"}
                asChild
              >
                <Link to={module.to}>{module.cta}</Link>
              </Button>
            </div>
          </div>
        ))}
      </div>
    </div>
  </section>
);

export default ModuleSection;
