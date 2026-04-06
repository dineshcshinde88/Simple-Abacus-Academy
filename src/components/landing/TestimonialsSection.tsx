import { useEffect, useState } from "react";
import { motion } from "framer-motion";
import { Star, Quote } from "lucide-react";
import { Carousel, CarouselContent, CarouselItem, type CarouselApi } from "@/components/ui/carousel";

const testimonials = [
  { name: "Priya Sharma", role: "Parent", text: "My daughter went from struggling with basic math to topping her class in just 6 months. The transformation is incredible!", rating: 5 },
  { name: "Rajesh Kumar", role: "Parent", text: "The practice engine is a game-changer. My son loves the timed challenges and has developed remarkable mental math speed.", rating: 5 },
  { name: "Sneha Patel", role: "Student, Level 8", text: "I can now solve complex calculations faster than a calculator! Abacus training gave me confidence I never had before.", rating: 5 },
  { name: "Arjun Mehta", role: "Parent", text: "Within weeks, my child became more focused and started enjoying numbers. We can clearly see stronger confidence in schoolwork.", rating: 5 },
];

const TestimonialCard = ({ name, role, text, rating }: (typeof testimonials)[number]) => (
  <div className="bg-card rounded-xl p-6 shadow-card border border-border h-full">
    <Quote className="w-8 h-8 text-secondary/30 mb-3" />
    <p className="text-sm text-foreground leading-relaxed mb-4">"{text}"</p>
    <div className="flex gap-0.5 mb-3">
      {Array.from({ length: rating }).map((_, j) => (
        <Star key={j} className="w-4 h-4 fill-secondary text-secondary" />
      ))}
    </div>
    <div className="font-heading font-semibold text-foreground text-sm">{name}</div>
    <div className="text-xs text-muted-foreground">{role}</div>
  </div>
);

const TestimonialsSection = () => {
  const [api, setApi] = useState<CarouselApi>();

  useEffect(() => {
    if (!api) {
      return;
    }

    const timer = setInterval(() => {
      if (api.canScrollNext()) {
        api.scrollNext();
      } else {
        api.scrollTo(0);
      }
    }, 3500);

    return () => clearInterval(timer);
  }, [api]);

  return (
    <section className="py-20 bg-background">
      <div className="container mx-auto px-4">
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          whileInView={{ opacity: 1, y: 0 }}
          viewport={{ once: true }}
          className="text-center mb-14"
        >
          <span className="inline-block px-4 py-1.5 rounded-full bg-accent-soft text-secondary text-sm font-semibold mb-4">
            Testimonials
          </span>
          <h2 className="text-3xl md:text-4xl font-heading font-bold text-foreground mb-4">
            What Parents & Students <span className="text-gradient">Say</span>
          </h2>
        </motion.div>

        <div className="md:hidden max-w-md mx-auto">
          <Carousel setApi={setApi} opts={{ loop: true }}>
            <CarouselContent>
              {testimonials.map((t, i) => (
                <CarouselItem key={t.name}>
                  <motion.div
                    initial={{ opacity: 0, y: 20 }}
                    whileInView={{ opacity: 1, y: 0 }}
                    viewport={{ once: true }}
                    transition={{ delay: i * 0.1 }}
                  >
                    <TestimonialCard {...t} />
                  </motion.div>
                </CarouselItem>
              ))}
            </CarouselContent>
          </Carousel>
        </div>

        <div className="hidden md:grid md:grid-cols-2 lg:grid-cols-4 gap-6 max-w-7xl mx-auto">
          {testimonials.map((t, i) => (
            <motion.div
              key={t.name}
              initial={{ opacity: 0, y: 20 }}
              whileInView={{ opacity: 1, y: 0 }}
              viewport={{ once: true }}
              transition={{ delay: i * 0.1 }}
            >
              <TestimonialCard {...t} />
            </motion.div>
          ))}
        </div>
      </div>
    </section>
  );
};

export default TestimonialsSection;
