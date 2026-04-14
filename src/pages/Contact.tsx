import Navbar from "@/components/layout/Navbar";
import Footer from "@/components/layout/Footer";
import { motion } from "framer-motion";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import { Label } from "@/components/ui/label";
import { Mail, Phone, MapPin, Clock, Send, MessageCircle } from "lucide-react";
import { useState } from "react";
import { toast } from "sonner";

const contactInfo = [
  { icon: Mail, title: "Email", value: "simpleabacuspune@gmail.com", href: "mailto:simpleabacuspune@gmail.com" },
  { icon: Phone, title: "Phone", value: "+91 89991 64139", href: "tel:+918999164139" },
  { icon: MessageCircle, title: "WhatsApp", value: "+91 89991 64139", href: "https://wa.me/918999164139" },
  { icon: MapPin, title: "Address", value: "2nd Floor, Maple Plaza, Kothrud, Pune, Maharashtra 411038" },
  { icon: Clock, title: "Working Hours", value: "Mon-Sat: 9:00 AM - 7:00 PM" },
];

const fadeUp = { initial: { opacity: 0, y: 20 }, whileInView: { opacity: 1, y: 0 }, viewport: { once: true } };

const Contact = () => {
  const [loading, setLoading] = useState(false);

  const handleSubmit = (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault();
    setLoading(true);
    setTimeout(() => {
      setLoading(false);
      toast.success("Thank you! We'll get back to you shortly.");
      (e.target as HTMLFormElement).reset();
    }, 1000);
  };

  return (
    <div className="min-h-screen">
      <Navbar />
      <main className="pt-16">
        {/* Hero */}
        <section className="gradient-hero py-16 md:py-24">
          <div className="container mx-auto px-4 text-center">
            <motion.h1 {...fadeUp} className="text-4xl md:text-5xl font-heading font-bold text-primary-foreground mb-4">
              Get in <span className="text-gradient">Touch</span>
            </motion.h1>
            <motion.p {...fadeUp} transition={{ delay: 0.1 }} className="text-primary-foreground/80 max-w-2xl mx-auto text-lg">
              Have questions about classes, timings, or levels? Send us a message and our team will guide you.
            </motion.p>
          </div>
        </section>

        <section className="py-20">
          <div className="container mx-auto px-4">
            <div className="grid lg:grid-cols-2 gap-12 max-w-5xl mx-auto">
              {/* Contact Form */}
              <motion.div {...fadeUp}>
                <h2 className="text-2xl font-heading font-bold text-foreground mb-6">Send us a Message</h2>
                <form onSubmit={handleSubmit} className="space-y-5">
                  <div className="grid sm:grid-cols-2 gap-4">
                    <div className="space-y-2">
                      <Label htmlFor="name">Full Name</Label>
                      <Input id="name" placeholder="Parent or student name" required />
                    </div>
                    <div className="space-y-2">
                      <Label htmlFor="email">Email</Label>
                      <Input id="email" type="email" placeholder="you@example.com" required />
                    </div>
                  </div>
                  <div className="space-y-2">
                    <Label htmlFor="phone">Phone Number</Label>
                    <Input id="phone" type="tel" placeholder="+91 XXXXX XXXXX" />
                  </div>
                  <div className="space-y-2">
                    <Label htmlFor="subject">Subject</Label>
                    <Input id="subject" placeholder="Course details, fee, or demo class" required />
                  </div>
                  <div className="space-y-2">
                    <Label htmlFor="message">Message</Label>
                    <Textarea id="message" placeholder="Tell us about your child's age and current grade" rows={5} required />
                  </div>
                  <Button type="submit" variant="hero" size="lg" disabled={loading} className="w-full sm:w-auto">
                    {loading ? "Sending..." : <>Send Message <Send className="w-4 h-4" /></>}
                  </Button>
                </form>
              </motion.div>

              {/* Contact Info */}
              <motion.div {...fadeUp} transition={{ delay: 0.15 }}>
                <h2 className="text-2xl font-heading font-bold text-foreground mb-6">Contact Information</h2>
                <div className="space-y-6">
                  {contactInfo.map((c) => (
                    <div key={c.title} className="flex items-start gap-4">
                      <div className="w-12 h-12 rounded-xl gradient-accent flex items-center justify-center shrink-0">
                        <c.icon className="w-5 h-5 text-accent-foreground" />
                      </div>
                      <div>
                        <h4 className="font-heading font-semibold text-foreground">{c.title}</h4>
                        {c.href ? (
                          <a href={c.href} className="text-sm text-muted-foreground hover:text-secondary transition-colors">{c.value}</a>
                        ) : (
                          <p className="text-sm text-muted-foreground">{c.value}</p>
                        )}
                      </div>
                    </div>
                  ))}
                </div>

                {/* Map placeholder */}
                <div className="mt-8 bg-muted rounded-xl h-64 flex items-center justify-center border border-border">
                  <div className="text-center text-muted-foreground">
                    <MapPin className="w-8 h-8 mx-auto mb-2" />
                    <p className="text-sm">Map integration coming soon</p>
                  </div>
                </div>
              </motion.div>
            </div>
          </div>
        </section>
      </main>
      <Footer />
    </div>
  );
};

export default Contact;
