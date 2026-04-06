import { Link } from "react-router-dom";
import { Button } from "@/components/ui/button";

const addOns = [
  {
    kicker: "Learn together with",
    title: "For Schools",
    body:
      "Our step-by-step Abacus lessons fit any school routine, and teachers get hands-on training plus ongoing backup.",
    cta: "View All Courses",
    accent: "bg-[#4c1d95] hover:bg-[#43208a] text-white",
    to: "/programs",
  },
  {
    kicker: "Get the skills",
    title: "Competition Conductor",
    body:
      "We also pack up competition kits so you can run certified tournaments and still have plenty of training sheets left over.",
    cta: "Get Started",
    accent: "bg-[#dc2626] hover:bg-[#b91c1c] text-white",
    to: "/contact",
  },
  {
    kicker: "Partner with Us",
    title: "Franchise Opportunities",
    body:
      "We hand over the full playbookâ€”branding, lesson plans, and expert adviceâ€”so you look like the local math wizard.",
    cta: "Join Now",
    accent: "bg-[#4c1d95] hover:bg-[#43208a] text-white",
    to: "/franchise",
  },
  {
    kicker: "Buy Courses",
    title: "For Worksheet Subscription",
    body:
      "Bite-sized worksheet practice in Abacus and Vedic Maths is available online alongside printable drills.",
    cta: "Start Subscription",
    accent: "bg-[#dc2626] hover:bg-[#b91c1c] text-white",
    to: "/worksheets-subscription",
  },
];

const AddOnServicesSection = () => (
  <section className="py-16 bg-white">
    <div className="container mx-auto px-4">
      <div className="text-center">
        <h2 className="text-3xl md:text-4xl font-heading font-extrabold text-primary">
          Our Add-On Services
        </h2>
      </div>

      <div className="mt-10 grid gap-6 md:grid-cols-2 lg:grid-cols-4">
        {addOns.map((item) => (
          <div
            key={item.title}
            className="rounded-2xl border border-secondary/20 bg-white shadow-card p-6 flex flex-col"
          >
            <div className="text-xs uppercase tracking-wide text-muted-foreground">{item.kicker}</div>
            <h3 className="mt-2 text-xl font-bold text-[#4c1d95]">{item.title}</h3>
            <p className="mt-3 text-sm text-muted-foreground flex-1">{item.body}</p>
            <div className="mt-6">
              <Button className={`rounded-full px-6 ${item.accent}`} asChild>
                <Link to={item.to}>{item.cta}</Link>
              </Button>
            </div>
          </div>
        ))}
      </div>
    </div>
  </section>
);

export default AddOnServicesSection;



