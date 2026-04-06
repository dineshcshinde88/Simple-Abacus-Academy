import { useState } from "react";
import { LayoutGrid, List } from "lucide-react";
import { Link } from "react-router-dom";
import Navbar from "@/components/layout/Navbar";
import Footer from "@/components/layout/Footer";
import { placeholderImages } from "@/data/placeholderImages";
import { Checkbox } from "@/components/ui/checkbox";
import { RadioGroup, RadioGroupItem } from "@/components/ui/radio-group";
import { Label } from "@/components/ui/label";
import { Button } from "@/components/ui/button";

const levels = ["Level 1", "Level 2", "Level 3", "Level 4", "Level 5", "Level 6"];

const AbacusWorksheetSubscription = () => {
  const [duration, setDuration] = useState("3-months");

  return (
    <div className="min-h-screen bg-background">
      <Navbar />
      <main className="pt-16">
        <section className="py-16">
          <div className="container mx-auto px-4">
            <div className="flex items-center justify-between gap-4">
              <h2 className="text-3xl md:text-4xl font-heading font-bold text-[#5a1c7a]">Choose Your Levels</h2>
              <div className="flex items-center gap-3">
                <Button size="icon" className="h-10 w-10 rounded-xl bg-orange-500 hover:bg-orange-600">
                  <List className="h-5 w-5 text-white" />
                </Button>
                <Button size="icon" variant="outline" className="h-10 w-10 rounded-xl border-purple-600 text-purple-600 hover:bg-purple-50">
                  <LayoutGrid className="h-5 w-5" />
                </Button>
              </div>
            </div>

            <div className="mt-8 grid items-start gap-6 lg:grid-cols-[420px_1fr]">
              <div className="w-full aspect-[4/3] overflow-hidden rounded-2xl bg-gradient-to-br from-amber-400 via-yellow-400 to-amber-300 shadow-card">
                <div className="relative h-full p-6">
                  <img
                    src={placeholderImages.moduleSection}
                    alt="Abacus Junior Worksheets"
                    className="h-full w-full rounded-xl object-cover"
                  />
                </div>
              </div>

              <div className="rounded-2xl border border-border bg-white p-6 shadow-card">
                <div className="flex flex-wrap items-center justify-between gap-4">
                  <h3 className="text-2xl font-heading font-bold text-orange-500">Abacus Junior</h3>
                  <div className="flex items-center gap-3 text-sm">
                    <span className="font-semibold text-purple-700">Select Duration:</span>
                    <RadioGroup value={duration} onValueChange={setDuration} className="flex items-center gap-4">
                      <div className="flex items-center gap-2">
                        <RadioGroupItem id="duration-3m" value="3-months" />
                        <Label htmlFor="duration-3m">3 Months</Label>
                      </div>
                      <div className="flex items-center gap-2">
                        <RadioGroupItem id="duration-1y" value="1-year" />
                        <Label htmlFor="duration-1y">1 Year</Label>
                      </div>
                    </RadioGroup>
                  </div>
                </div>

                <div className="mt-6 grid gap-4 md:grid-cols-2">
                  {levels.map((level) => (
                    <div
                      key={level}
                      className="flex items-center justify-between rounded-xl border border-orange-300 bg-white px-4 py-3 shadow-sm"
                    >
                      <div className="flex items-center gap-3">
                        <Checkbox id={level} />
                        <Label htmlFor={level} className="font-semibold text-purple-700">
                          {level}
                        </Label>
                      </div>
                      <div className="flex h-7 w-7 items-center justify-center rounded-md border border-orange-400 text-orange-500">
                        <List className="h-4 w-4" />
                      </div>
                    </div>
                  ))}
                </div>
              </div>
            </div>

            <div className="mt-10 grid items-start gap-6 lg:grid-cols-[420px_1fr]">
              <div className="w-full aspect-[4/3] overflow-hidden rounded-2xl bg-gradient-to-br from-purple-200 via-purple-300 to-purple-400 shadow-card">
                <div className="relative h-full p-6">
                  <div className="absolute left-6 top-6 flex flex-wrap gap-2">
                    {["Faster Maths", "Accurate Results", "Sharper Mind", "Boosts Confidence"].map((pill) => (
                      <span
                        key={pill}
                        className="rounded-full bg-orange-500 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-white shadow-sm"
                      >
                        {pill}
                      </span>
                    ))}
                  </div>
                  <img
                    src={placeholderImages.worksheetPractice}
                    alt="Abacus Senior Worksheets"
                    className="h-full w-full rounded-xl object-cover"
                  />
                </div>
              </div>

              <div className="rounded-2xl border border-border bg-white p-6 shadow-card">
                <div className="flex flex-wrap items-center justify-between gap-4">
                  <h3 className="text-2xl font-heading font-bold text-orange-500">Abacus Senior</h3>
                  <div className="flex items-center gap-3 text-sm">
                    <span className="font-semibold text-purple-700">Select Duration:</span>
                    <RadioGroup value={duration} onValueChange={setDuration} className="flex items-center gap-4">
                      <div className="flex items-center gap-2">
                        <RadioGroupItem id="duration-3m-senior" value="3-months" />
                        <Label htmlFor="duration-3m-senior">3 Months</Label>
                      </div>
                      <div className="flex items-center gap-2">
                        <RadioGroupItem id="duration-1y-senior" value="1-year" />
                        <Label htmlFor="duration-1y-senior">1 Year</Label>
                      </div>
                    </RadioGroup>
                  </div>
                </div>

                <div className="mt-6 grid gap-4 md:grid-cols-2">
                  {levels.map((level) => (
                    <div
                      key={`senior-${level}`}
                      className="flex items-center justify-between rounded-xl border border-orange-300 bg-white px-4 py-3 shadow-sm"
                    >
                      <div className="flex items-center gap-3">
                        <Checkbox id={`senior-${level}`} />
                        <Label htmlFor={`senior-${level}`} className="font-semibold text-purple-700">
                          {level}
                        </Label>
                      </div>
                      <div className="flex h-7 w-7 items-center justify-center rounded-md border border-orange-400 text-orange-500">
                        <List className="h-4 w-4" />
                      </div>
                    </div>
                  ))}
                </div>
              </div>
            </div>

            <div className="mt-8 flex flex-wrap items-center justify-between gap-4">
              <Button className="rounded-full bg-[#4B1E83] px-6 py-6 text-sm font-semibold text-white hover:bg-[#3c176a]" asChild>
                <Link to="/vedic-maths-worksheet-subscription">← Buy Vedic Maths Worksheets</Link>
              </Button>
              <Button className="rounded-full bg-[#4B1E83] px-6 py-6 text-sm font-semibold text-white hover:bg-[#3c176a]" asChild>
                <Link to="/shop">Proceed to Checkout →</Link>
              </Button>
            </div>
          </div>
        </section>
      </main>
      <Footer />
    </div>
  );
};

export default AbacusWorksheetSubscription;
