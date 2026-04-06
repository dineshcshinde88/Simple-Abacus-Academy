import { Link } from "react-router-dom";
import { Button } from "@/components/ui/button";
import { placeholderImages } from "@/data/placeholderImages";

const modules = [
  {
    title: "Abacus for Students",
    body: "Learn the fundamentals of abacus in a fun way!",
    cta: "Get a Free Demo",
    tone: "orange",
    image: placeholderImages.homeHero1,
    to: "/book-demo",
  },
  {
    title: "Vedic Maths for Students",
    body: "Master quick calculations with Vedic methods!",
    cta: "Get a Free Demo",
    tone: "purple",
    image: placeholderImages.aboutHero,
    to: "/book-demo",
  },
  {
    title: "Abacus & Vedic Maths for Instructors",
    body: "Join us to teach and inspire future abacus learners!",
    cta: "Join with Us",
    tone: "orange",
    image: placeholderImages.homeHero2,
    to: "/teacher-training",
  },
  {
    title: "Abacus & Vedic Maths for Schools",
    body: "Integrate abacus learning into your school curriculum!",
    cta: "Enquire Here",
    tone: "purple",
    image: placeholderImages.homeHero3,
    to: "/contact",
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

      <div className="mt-10 grid gap-6 md:grid-cols-2 lg:grid-cols-4">
        {modules.map((module) => (
          <div
            key={module.title}
            className="rounded-2xl border border-muted bg-white p-5 shadow-card flex flex-col"
          >
            <div className="overflow-hidden rounded-xl">
              <img
                src={module.image}
                alt={module.title}
                className="h-44 w-full object-cover"
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
