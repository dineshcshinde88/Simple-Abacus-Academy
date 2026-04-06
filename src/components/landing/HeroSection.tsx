import { Button } from "@/components/ui/button";
import { Link } from "react-router-dom";
import { AnimatePresence, motion } from "framer-motion";
import { ArrowLeft, ArrowRight, Play } from "lucide-react";
import { useEffect, useState } from "react";
import { placeholderImages } from "@/data/placeholderImages";

const slides = [
  {
    id: "students",
    tag: "🧮 For Children Aged 6–14",
    title: "Abacus & Mental Math",
    highlight: "Brain Power",
    body: "Build concentration, speed, memory, and confidence through our proven abacus training program. Join 10,000+ students excelling in math.",
    primary: { label: "Book Free Demo", to: "/book-demo" },
    secondary: { label: "Explore Programs", to: "/programs" },
    stats: [
      { num: "10K+", label: "Students" },
      { num: "50+", label: "Centers" },
      { num: "98%", label: "Success Rate" },
    ],
    image: placeholderImages.homeHero1,
    imageAlt: "Children learning mental math with abacus",
  },
  {
    id: "instructors",
    tag: "🎓 For Teachers & Mentors",
    title: "Become an Abacus",
    highlight: "Instructor",
    body: "Teach future problem-solvers with our certification program, lesson plans, and ongoing support for your coaching journey.",
    primary: { label: "Apply to Teach", to: "/contact" },
    secondary: { label: "View Programs", to: "/programs" },
    stats: [
      { num: "120+", label: "Certified Trainers" },
      { num: "7 Days", label: "Starter Training" },
      { num: "24/7", label: "Mentor Support" },
    ],
    image: placeholderImages.homeHero2,
    imageAlt: "Instructor guiding students with an abacus",
  },
  {
    id: "services",
    tag: "🏫 For Schools & Partners",
    title: "Other Services",
    highlight: "We Offer",
    body: "School workshops, competitive training, and custom camps that make math engaging, memorable, and measurable.",
    primary: { label: "Partner With Us", to: "/contact" },
    secondary: { label: "See Programs", to: "/programs" },
    stats: [
      { num: "35+", label: "Workshops" },
      { num: "18", label: "Cities" },
      { num: "4.9★", label: "Avg Rating" },
    ],
    image: placeholderImages.homeHero3,
    imageAlt: "Group workshop practicing mental math",
  },
];

const HeroSection = () => {
  const [activeIndex, setActiveIndex] = useState(0);
  const [isPaused, setIsPaused] = useState(false);
  const activeSlide = slides[activeIndex];

  useEffect(() => {
    if (isPaused) return;
    const interval = window.setInterval(() => {
      setActiveIndex((prev) => (prev + 1) % slides.length);
    }, 6000);
    return () => window.clearInterval(interval);
  }, [isPaused]);

  const goTo = (index: number) => {
    const normalized = (index + slides.length) % slides.length;
    setActiveIndex(normalized);
  };

  return (
    <section
      className="relative min-h-[90vh] bg-white overflow-hidden flex items-center"
      aria-roledescription="carousel"
      aria-label="Homepage highlights"
      onMouseEnter={() => setIsPaused(true)}
      onMouseLeave={() => setIsPaused(false)}
    >
      <div className="absolute top-20 left-10 w-72 h-72 rounded-full bg-blue-100/60 blur-3xl" />
      <div className="absolute bottom-10 right-10 w-96 h-96 rounded-full bg-blue-200/40 blur-3xl" />

      <div className="container mx-auto px-4 py-24 relative z-10">
        <div className="flex items-center justify-between gap-6 mb-8">
          <div className="flex items-center gap-2 text-sm text-muted-foreground">
            <span className="uppercase tracking-[0.2em]">Highlights</span>
            <span className="h-px w-10 bg-muted-foreground/40" />
            <span>
              {activeIndex + 1}/{slides.length}
            </span>
          </div>
          <div className="hidden md:flex items-center gap-2">
            <Button
              variant="hero-outline"
              size="icon"
              onClick={() => goTo(activeIndex - 1)}
              aria-label="Previous slide"
            >
              <ArrowLeft className="w-4 h-4" />
            </Button>
            <Button
              variant="hero-outline"
              size="icon"
              onClick={() => goTo(activeIndex + 1)}
              aria-label="Next slide"
            >
              <ArrowRight className="w-4 h-4" />
            </Button>
          </div>
        </div>

        <AnimatePresence mode="wait">
          <motion.div
            key={activeSlide.id}
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            exit={{ opacity: 0, y: -20 }}
            transition={{ duration: 0.6 }}
            className="grid lg:grid-cols-2 gap-12 items-center"
          >
            <div>
              <span className="inline-block px-4 py-1.5 rounded-full bg-secondary/10 text-secondary text-sm font-semibold mb-6">
                {activeSlide.tag}
              </span>
              <h1 className="text-4xl md:text-5xl lg:text-6xl font-heading font-extrabold text-foreground leading-tight mb-6">
                {activeSlide.title}{" "}
                <span className="text-secondary">{activeSlide.highlight}</span>
              </h1>
              <p className="text-lg text-muted-foreground mb-8 max-w-lg">
                {activeSlide.body}
              </p>
              <div className="flex flex-wrap gap-4">
                <Button variant="hero" size="lg" asChild>
                  <Link to={activeSlide.primary.to}>
                    {activeSlide.primary.label} <ArrowRight className="w-4 h-4" />
                  </Link>
                </Button>
                <Button variant="hero-outline" size="lg" asChild>
                  <Link to={activeSlide.secondary.to}>
                    <Play className="w-4 h-4" /> {activeSlide.secondary.label}
                  </Link>
                </Button>
              </div>

              <div className="flex gap-8 mt-10">
                {activeSlide.stats.map((stat) => (
                  <div key={stat.label}>
                    <div className="text-2xl font-heading font-bold text-secondary">
                      {stat.num}
                    </div>
                    <div className="text-sm text-muted-foreground">{stat.label}</div>
                  </div>
                ))}
              </div>
            </div>

            <div className="block">
              <div className="relative max-w-2xl mx-auto lg:max-w-none">
                <div className="absolute inset-0 gradient-accent rounded-3xl rotate-3 opacity-20" />
                <img
                  src={activeSlide.image}
                  alt={activeSlide.imageAlt}
                  className="relative rounded-3xl shadow-2xl w-full object-cover aspect-[4/3]"
                  loading="eager"
                />
              </div>
            </div>
          </motion.div>
        </AnimatePresence>

        <div className="flex items-center justify-center gap-3 mt-10">
          {slides.map((slide, index) => (
            <button
              key={slide.id}
              type="button"
              className={`h-2.5 rounded-full transition-all ${
                index === activeIndex ? "w-10 bg-black" : "w-3 bg-black/30"
              }`}
              aria-label={`Go to slide ${index + 1}`}
              aria-pressed={index === activeIndex}
              onClick={() => goTo(index)}
            />
          ))}
        </div>
      </div>
    </section>
  );
};

export default HeroSection;
