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
import { getApiBase } from "@/lib/apiBase";

const contactInfo = [
  { icon: Mail, title: "Email", value: "simpleabacuspune@gmail.com", href: "mailto:simpleabacuspune@gmail.com" },
  { icon: Phone, title: "Phone", value: "+91 89991 64139", href: "tel:+918999164139" },
  { icon: MessageCircle, title: "WhatsApp", value: "+91 89991 64139", href: "https://wa.me/918999164139" },
  { icon: MapPin, title: "Address", value: "Kunjir Public School, Manjari Budruk, Pune, Maharashtra 412307" },
  { icon: Clock, title: "Working Hours", value: "Mon-Sat: 9:00 AM - 7:00 PM" },
];

const fadeUp = { initial: { opacity: 0, y: 20 }, whileInView: { opacity: 1, y: 0 }, viewport: { once: true } };

const Contact = () => {
  const [loading, setLoading] = useState(false);

  const handleSubmit = async (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault();
    const form = e.currentTarget;
    const data = new FormData(form);
    setLoading(true);
    try {
      const response = await fetch(`${getApiBase()}/api/contact`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(Object.fromEntries(data.entries())),
      });
      const payload = await response.json().catch(() => ({}));
      if (!response.ok) throw new Error((payload as { message?: string }).message || "Unable to send message.");
      toast.success("Thank you! We'll get back to you shortly.");
      form.reset();
    } catch (error) {
      toast.error(error instanceof Error ? error.message : "Unable to send message.");
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-screen">
      <Navbar />
      <main className="pt-16">
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
                      <Input id="name" name="name" placeholder="Parent or student name" required />
                    </div>
                    <div className="space-y-2">
                      <Label htmlFor="email">Email</Label>
                      <Input id="email" name="email" type="email" placeholder="you@example.com" required />
                    </div>
                  </div>
                  <div className="space-y-2">
                    <Label htmlFor="phone">Phone Number</Label>
                    <Input id="phone" name="phone" type="tel" inputMode="numeric" maxLength={10} pattern="[0-9]{10}" placeholder="10-digit number" />
                  </div>
                  <div className="space-y-2">
                    <Label htmlFor="subject">Subject</Label>
                    <Input id="subject" name="subject" placeholder="Course details, fee, or demo class" required />
                  </div>
                  <div className="space-y-2">
                    <Label htmlFor="message">Message</Label>
                    <Textarea id="message" name="message" placeholder="Tell us about your child's age and current grade" rows={5} required />
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

                <div className="mt-8 h-64 overflow-hidden rounded-xl border border-border bg-muted">
                  <iframe
                    title="Simple Abacus location map"
                    src="https://www.google.com/maps?q=Kunjir%20Public%20School%2C%20Manjari%20Budruk%2C%20Pune%2C%20Maharashtra%20412307&output=embed"
                    className="h-full w-full"
                    loading="lazy"
                    referrerPolicy="no-referrer-when-downgrade"
                  />
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
