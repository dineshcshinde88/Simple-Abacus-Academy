import { useState } from "react";
import Navbar from "@/components/layout/Navbar";
import Footer from "@/components/layout/Footer";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Checkbox } from "@/components/ui/checkbox";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { RefreshCcw } from "lucide-react";
import { Link } from "react-router-dom";

const buildCaptcha = () => {
  const chars = "ABCDEFGHJKLMNPQRSTUVWXYZ23456789";
  let result = "";
  for (let i = 0; i < 6; i += 1) {
    result += chars[Math.floor(Math.random() * chars.length)];
  }
  return result;
};

const InstructorRegistration = () => {
  const [captcha, setCaptcha] = useState(buildCaptcha());
  const [captchaInput, setCaptchaInput] = useState("");
  const [address, setAddress] = useState("Gachibowli, Hyderabad");

  const refreshCaptcha = () => {
    setCaptcha(buildCaptcha());
    setCaptchaInput("");
  };

  return (
    <div className="min-h-screen bg-background">
      <Navbar />
      <main className="pt-16">
        <section className="py-16">
          <div className="container mx-auto px-4">
            <div className="mb-8">
              <h1 className="text-3xl md:text-4xl font-heading font-bold text-foreground">Instructor Registration</h1>
              <p className="mt-2 text-muted-foreground">ABACUS Register As Instructor</p>
            </div>

            <div className="max-w-4xl rounded-2xl border border-border bg-white shadow-card overflow-hidden">
              <div className="p-6 md:p-8">
                <div className="flex flex-wrap items-center gap-6 mb-6">
                  <div className="flex items-center gap-3">
                    <Checkbox id="program-abacus" defaultChecked />
                    <Label htmlFor="program-abacus" className="font-semibold">Abacus</Label>
                  </div>
                  <div className="flex items-center gap-3">
                    <Checkbox id="program-vedic" />
                    <Label htmlFor="program-vedic" className="font-semibold">Vedic Maths</Label>
                  </div>
                </div>

                <div className="grid gap-6 md:grid-cols-2">
                  <div className="space-y-2">
                    <Label htmlFor="full-name">Full Name</Label>
                    <Input id="full-name" placeholder="Full Name" required />
                  </div>
                  <div className="space-y-2">
                    <Label htmlFor="email">Email</Label>
                    <Input id="email" type="email" placeholder="Email" required />
                  </div>
                  <div className="space-y-2">
                    <Label>Mobile</Label>
                    <div className="flex gap-2">
                      <Select defaultValue="+91">
                        <SelectTrigger className="w-28">
                          <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectItem value="+91">IN +91</SelectItem>
                          <SelectItem value="+1">US +1</SelectItem>
                          <SelectItem value="+44">UK +44</SelectItem>
                          <SelectItem value="+971">UAE +971</SelectItem>
                        </SelectContent>
                      </Select>
                      <Input placeholder="Mobile" required />
                    </div>
                  </div>
                  <div className="space-y-2">
                    <Label>Gender</Label>
                    <Select>
                      <SelectTrigger>
                        <SelectValue placeholder="Select Gender" />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="male">Male</SelectItem>
                        <SelectItem value="female">Female</SelectItem>
                        <SelectItem value="other">Other</SelectItem>
                      </SelectContent>
                    </Select>
                  </div>
                  <div className="space-y-2">
                    <Label>Date of Birth</Label>
                    <Input type="date" />
                  </div>
                  <div className="space-y-2">
                    <Label htmlFor="qualification">Qualification</Label>
                    <Input id="qualification" placeholder="Qualification" />
                  </div>
                  <div className="space-y-2 md:col-span-2">
                    <Label htmlFor="address">Address</Label>
                    <Input
                      id="address"
                      placeholder="Enter your full address"
                      value={address}
                      onChange={(event) => setAddress(event.target.value)}
                      required
                    />
                    <div className="mt-3 overflow-hidden rounded-xl border border-border">
                      <iframe
                        title="Location Map"
                        src={`https://www.google.com/maps?q=${encodeURIComponent(address || "Gachibowli, Hyderabad")}&output=embed`}
                        className="h-64 w-full"
                        loading="lazy"
                        referrerPolicy="no-referrer-when-downgrade"
                      />
                    </div>
                  </div>

                  <div className="md:col-span-2 grid gap-4 md:grid-cols-[240px_1fr] items-center">
                    <div className="flex items-center gap-3">
                      <div className="flex-1 rounded-full border border-border bg-muted px-4 py-2 text-center text-sm font-semibold text-primary">
                        {captcha}
                      </div>
                      <button
                        type="button"
                        className="inline-flex h-10 w-10 items-center justify-center rounded-full border border-border text-muted-foreground hover:text-foreground"
                        onClick={refreshCaptcha}
                        aria-label="Refresh captcha"
                      >
                        <RefreshCcw className="h-4 w-4" />
                      </button>
                    </div>
                    <Input
                      placeholder="Enter Captcha Code"
                      value={captchaInput}
                      onChange={(event) => setCaptchaInput(event.target.value)}
                      required
                    />
                  </div>
                </div>

                <div className="mt-6 flex flex-wrap items-center justify-between gap-4">
                  <p className="text-sm text-muted-foreground">
                    If you are already registered, please {" "}
                    <Link to="/instructor-login" className="text-primary font-semibold hover:underline">
                      click here
                    </Link>
                    {" "}to login
                  </p>
                  <Button variant="hero">Registration</Button>
                </div>
              </div>
            </div>
          </div>
        </section>
      </main>
      <Footer />
    </div>
  );
};

export default InstructorRegistration;
