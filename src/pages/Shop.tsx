import Navbar from "@/components/layout/Navbar";
import Footer from "@/components/layout/Footer";
import { motion } from "framer-motion";
import { Button } from "@/components/ui/button";
import { ShoppingCart, Star, Package, BookOpen, Pencil } from "lucide-react";
import { useState } from "react";
import { toast } from "sonner";

const categories = ["All", "Monthly Plans", "Quarterly Plans", "Annual Plans"];

const products = [
  {
    id: 1,
    name: "Starter Monthly (Beginner)",
    category: "Monthly Plans",
    price: 1499,
    mrp: 1999,
    rating: 4.7,
    reviews: 180,
    icon: Package,
    desc: "1 class/week, practice app access, monthly speed test.",
  },
  {
    id: 2,
    name: "Focus Monthly (Intermediate)",
    category: "Monthly Plans",
    price: 1799,
    mrp: 2299,
    rating: 4.8,
    reviews: 210,
    icon: BookOpen,
    desc: "2 classes/week, worksheets, weekly mentor feedback.",
  },
  {
    id: 3,
    name: "Best Value Quarterly",
    category: "Quarterly Plans",
    price: 3999,
    mrp: 4999,
    rating: 4.9,
    reviews: 265,
    icon: BookOpen,
    desc: "Includes monthly assessments, report card, parent call.",
  },
  {
    id: 4,
    name: "Accelerate Quarterly",
    category: "Quarterly Plans",
    price: 4499,
    mrp: 5499,
    rating: 4.8,
    reviews: 190,
    icon: Pencil,
    desc: "Extra speed drills, exam practice, certificate eligibility.",
  },
  {
    id: 5,
    name: "Most Popular Annual",
    category: "Annual Plans",
    price: 13999,
    mrp: 17999,
    rating: 5.0,
    reviews: 320,
    icon: Package,
    desc: "Weekly classes, unlimited practice, quarterly exams.",
  },
  {
    id: 6,
    name: "Champion Annual",
    category: "Annual Plans",
    price: 15999,
    mrp: 19999,
    rating: 4.9,
    reviews: 145,
    icon: Pencil,
    desc: "Competition prep, personal mentoring, priority support.",
  },
];

const fadeUp = { initial: { opacity: 0, y: 20 }, whileInView: { opacity: 1, y: 0 }, viewport: { once: true } };

const Shop = () => {
  const [active, setActive] = useState("All");
  const filtered = active === "All" ? products : products.filter((p) => p.category === active);

  const addToCart = (name: string) => {
    toast.success(`${name} selected!`);
  };

  return (
    <div className="min-h-screen">
      <Navbar />
      <main className="pt-16">
        {/* Hero */}
        <section className="gradient-hero py-16 md:py-24">
          <div className="container mx-auto px-4 text-center">
            <motion.h1 {...fadeUp} className="text-4xl md:text-5xl font-heading font-bold text-primary-foreground mb-4">
              <span className="text-gradient">Pricing</span> Plans
            </motion.h1>
            <motion.p {...fadeUp} transition={{ delay: 0.1 }} className="text-primary-foreground/80 max-w-2xl mx-auto text-lg">
              Simple, transparent plans for every level. Choose a plan that fits your child's learning pace.
            </motion.p>
          </div>
        </section>

        {/* Filters + Products */}
        <section className="py-16">
          <div className="container mx-auto px-4">
            {/* Category Tabs */}
            <div className="flex flex-wrap justify-center gap-3 mb-10">
              {categories.map((c) => (
                <button
                  key={c}
                  onClick={() => setActive(c)}
                  className={`px-4 py-2 rounded-full text-sm font-semibold transition-all ${
                    active === c ? "gradient-accent text-accent-foreground shadow-glow" : "bg-muted text-muted-foreground hover:text-foreground"
                  }`}
                >
                  {c}
                </button>
              ))}
            </div>

            {/* Product Grid */}
            <div className="grid sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 max-w-6xl mx-auto">
              {filtered.map((p, i) => (
                <motion.div
                  key={p.id}
                  {...fadeUp}
                  transition={{ delay: i * 0.05 }}
                  className="bg-card rounded-xl border border-border shadow-card hover:shadow-card-hover transition-all overflow-hidden flex flex-col"
                >
                  <div className="bg-muted/50 p-8 flex items-center justify-center">
                    <p.icon className="w-16 h-16 text-muted-foreground/40" />
                  </div>
                  <div className="p-5 flex flex-col flex-1">
                    <span className="text-xs text-secondary font-semibold mb-1">{p.category}</span>
                    <h3 className="font-heading font-bold text-foreground mb-1">{p.name}</h3>
                    <p className="text-xs text-muted-foreground mb-3 flex-1">{p.desc}</p>
                    <div className="flex items-center gap-1 mb-3">
                      <Star className="w-3.5 h-3.5 text-secondary fill-secondary" />
                      <span className="text-sm font-medium text-foreground">{p.rating}</span>
                      <span className="text-xs text-muted-foreground">({p.reviews})</span>
                    </div>
                    <div className="flex items-center justify-between">
                      <div>
                        <span className="text-lg font-heading font-bold text-foreground">Rs. {p.price}</span>
                        <span className="text-sm text-muted-foreground line-through ml-2">Rs. {p.mrp}</span>
                      </div>
                      <Button size="sm" variant="hero" onClick={() => addToCart(p.name)}>
                        <ShoppingCart className="w-4 h-4" />
                      </Button>
                    </div>
                  </div>
                </motion.div>
              ))}
            </div>
          </div>
        </section>
      </main>
      <Footer />
    </div>
  );
};

export default Shop;
