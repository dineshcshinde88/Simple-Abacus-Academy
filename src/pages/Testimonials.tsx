import Navbar from "@/components/layout/Navbar";
import Footer from "@/components/layout/Footer";
import { Button } from "@/components/ui/button";
import { Carousel, CarouselContent, CarouselItem, CarouselNext, CarouselPrevious, type CarouselApi } from "@/components/ui/carousel";
import { Quote, Star } from "lucide-react";
import { useEffect, useState } from "react";

const reviews = [
  { name: "Priya Sharma", role: "Parent", text: "My daughter went from struggling with basic math to topping her class in just 6 months. The transformation is incredible!", rating: 5 },
  { name: "Rajesh Kumar", role: "Parent", text: "The practice engine is a game-changer. My son loves the timed challenges and has developed remarkable mental math speed.", rating: 5 },
  { name: "Sneha Patel", role: "Student, Level 8", text: "I can now solve complex calculations faster than a calculator! Abacus training gave me confidence I never had before.", rating: 5 },
  { name: "Arjun Mehta", role: "Parent", text: "Within weeks, my child became more focused and started enjoying numbers. We can clearly see stronger confidence in schoolwork.", rating: 5 },
  { name: "Anita Roy", role: "Parent", text: "The trainers are supportive and the worksheets keep practice consistent. We are very happy with the progress.", rating: 5 },
  { name: "Vikram Singh", role: "Student", text: "I love the online classes. The abacus tool makes it easy to practice every day.", rating: 5 },
];

const ReviewCard = ({ name, role, text, rating }: (typeof reviews)[number]) => (
  <div className="bg-card rounded-2xl p-6 shadow-card border border-border h-full">
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

const Testimonials = () => {
  const [api, setApi] = useState<CarouselApi>();

  useEffect(() => {
    if (!api) return;
    const timer = setInterval(() => {
      if (api.canScrollNext()) {
        api.scrollNext();
      } else {
        api.scrollTo(0);
      }
    }, 3000);
    return () => clearInterval(timer);
  }, [api]);

  return (
    <div className="min-h-screen bg-background">
      <Navbar />
      <main className="pt-16">
        <section className="py-16">
          <div className="container mx-auto px-4">
            <div className="text-center mb-10">
              <h1 className="text-3xl md:text-4xl font-heading font-bold text-foreground">Testimonials</h1>
              <p className="mt-2 text-muted-foreground">What parents and students say about Abacus Trainer.</p>
              <div className="mt-4 flex justify-center">
                <Button asChild variant="hero">
                  <a
                    href="https://share.google/Qw7hCswcBVJh4ZECE"
                    target="_blank"
                    rel="noopener noreferrer"
                  >
                    View All Google Reviews
                  </a>
                </Button>
              </div>
            </div>

            <div className="relative max-w-6xl mx-auto">
              <Carousel setApi={setApi} opts={{ loop: true }}>
                <CarouselContent>
                  {reviews.map((review) => (
                    <CarouselItem key={review.name} className="basis-full md:basis-1/2 lg:basis-1/3">
                      <ReviewCard {...review} />
                    </CarouselItem>
                  ))}
                </CarouselContent>
                <CarouselPrevious className="hidden md:flex" />
                <CarouselNext className="hidden md:flex" />
              </Carousel>
            </div>
          </div>
        </section>
      </main>
      <Footer />
    </div>
  );
};

export default Testimonials;
