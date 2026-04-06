import { Link } from "react-router-dom";
import { Button } from "@/components/ui/button";
import { CheckCircle2 } from "lucide-react";

const highlights = [
  "Nurture advanced concentration skills",
  "Master quick mental calculations",
  "Gain lifelong confidence and learning tools",
];

const VideoIntroSection = () => (
  <section className="relative py-16">
    <div className="container mx-auto px-4">
      <div className="rounded-[32px] border border-secondary/30 bg-white shadow-card p-6 md:p-10">
        <div className="grid lg:grid-cols-[1.2fr_1fr] gap-10 items-start">
          <div className="w-full overflow-hidden rounded-2xl bg-slate-900/5">
            <div className="relative aspect-video w-full">
              <iframe
                className="absolute inset-0 h-full w-full"
                src="https://www.youtube.com/embed/o6yUx027P8M?si=kfYuTZx-jfVBSnHm"
                title="YouTube video player"
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                referrerPolicy="strict-origin-when-cross-origin"
                allowFullScreen
              />
            </div>
          </div>

          <div>
            <span className="text-sm font-semibold uppercase tracking-[0.22em] text-secondary">
              Best Online Abacus Classes in India
            </span>
            <h2 className="mt-4 text-3xl md:text-4xl font-heading font-extrabold text-primary">
              Unleash potential with Abacus & Vedic Maths
            </h2>
            <div className="mt-4 space-y-3 text-base text-muted-foreground leading-relaxed">
              <p>
                SIMPLE ABACUS builds strong mathematical foundations through structured Abacus and
                Vedic Maths training.
              </p>
              <p>
                With the right guidance and practice, children improve concentration, memory,
                speed, and confidence in Mathematics.
              </p>
              <p>
                We teach step-by-step with individual attention to help every child perform their
                best and develop sharper minds for the future.
              </p>
            </div>

            <div className="mt-6 space-y-3">
              {highlights.map((item) => (
                <div key={item} className="flex items-start gap-3 text-sm text-primary">
                  <CheckCircle2 className="mt-0.5 h-5 w-5 text-secondary" />
                  <span>{item}</span>
                </div>
              ))}
            </div>

            <div className="mt-8 flex flex-wrap gap-4">
              <Button variant="hero" size="lg" asChild>
                <Link to="/about">Know More</Link>
              </Button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
);

export default VideoIntroSection;
