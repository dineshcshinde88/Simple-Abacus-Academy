import { useMemo, useState } from "react";
import Navbar from "@/components/layout/Navbar";
import Footer from "@/components/layout/Footer";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import {
  Accordion,
  AccordionContent,
  AccordionItem,
  AccordionTrigger,
} from "@/components/ui/accordion";
import {
  Download,
  FileText,
  Loader2,
  PlayCircle,
  Settings2,
  Sparkles,
  Wand2,
  CheckCircle2,
  Users,
  GraduationCap,
  HeartHandshake,
} from "lucide-react";

const operations = ["Addition", "Subtraction", "Multiplication"] as const;
const rowsOptions = ["2", "3", "4", "5", "6", "7", "8", "9", "10"] as const;
const questionsOptions = ["10", "20", "30", "50", "100"] as const;
const digitOptions = ["1", "2", "3", "4", "5"] as const;

const features = [
  {
    title: "Complete Customization",
    desc: "Pick operations, rows, and question counts to match each learner.",
    icon: Settings2,
  },
  {
    title: "Auto Answer Keys",
    desc: "Get instant answers with every worksheet for quick checking.",
    icon: CheckCircle2,
  },
  {
    title: "Unlimited Worksheets",
    desc: "Generate as many practice sets as you need, anytime.",
    icon: Sparkles,
  },
  {
    title: "Free Access",
    desc: "Create worksheets without extra cost or hidden fees.",
    icon: HeartHandshake,
  },
];

const benefits = [
  {
    title: "For Teachers",
    desc: "Save prep time and deliver differentiated practice instantly.",
    icon: GraduationCap,
  },
  {
    title: "For Parents",
    desc: "Support daily practice at home with structured worksheets.",
    icon: Users,
  },
  {
    title: "For Students",
    desc: "Build speed, accuracy, and confidence with consistent drills.",
    icon: FileText,
  },
];

const faqs = [
  {
    q: "Is the worksheet generator free to use?",
    a: "Yes. You can generate unlimited worksheets without any fees.",
  },
  {
    q: "Can I download worksheets as PDF?",
    a: "Yes. Use the Download PDF button to save and print worksheets.",
  },
  {
    q: "Do worksheets include answer keys?",
    a: "Yes. Each worksheet can include an auto-generated answer key.",
  },
  {
    q: "Can I change operations and difficulty?",
    a: "Yes. Select an operation and adjust rows and questions for the right level.",
  },
  {
    q: "Will this work on mobile devices?",
    a: "Absolutely. The generator is fully responsive for phones and tablets.",
  },
];

const WorksheetGenerator = () => {
  const [operation, setOperation] = useState<typeof operations[number]>("Addition");
  const [rows, setRows] = useState<typeof rowsOptions[number]>("10");
  const [questions, setQuestions] = useState<typeof questionsOptions[number]>("20");
  const [digitsByRow, setDigitsByRow] = useState<string[]>(() =>
    Array.from({ length: Number(rowsOptions[rowsOptions.length - 1]) }, (_, index) => (index === 0 ? "3" : "2")),
  );
  const [isGenerating, setIsGenerating] = useState(false);
  const [previewVisible, setPreviewVisible] = useState(false);
  const [worksheetQuestions, setWorksheetQuestions] = useState<
    { numbers: number[]; operator: string; answer: number }[]
  >([]);

  const previewSummary = useMemo(
    () => `${operation} • ${rows} Rows • ${questions} Questions`,
    [operation, rows, questions],
  );

  const previewColumns = useMemo(
    () => Math.min(Math.ceil(Number(questions) / Number(rows)), 4),
    [questions, rows],
  );

  const randomNumber = (digits: number) => {
    if (digits <= 1) return Math.floor(Math.random() * 9) + 1;
    const min = Math.pow(10, digits - 1);
    const max = Math.pow(10, digits) - 1;
    return Math.floor(Math.random() * (max - min + 1)) + min;
  };

  const getActiveDigitLengths = () =>
    digitsByRow.slice(0, Number(rows)).map((value) => {
      const parsed = Number(value);
      return Number.isFinite(parsed) && parsed > 0 ? parsed : 1;
    });

  const updateDigitLength = (index: number, value: string) => {
    setDigitsByRow((current) => current.map((item, itemIndex) => (itemIndex === index ? value : item)));
  };

  const generateQuestions = () => {
    const total = Number(questions);
    const result: { numbers: number[]; operator: string; answer: number }[] = [];
    const digitLengths = getActiveDigitLengths();

    for (let i = 0; i < total; i += 1) {
      if (operation === "Addition") {
        const nums = digitLengths.map((digits) => randomNumber(digits));
        const sum = nums.reduce((acc, val) => acc + val, 0);
        result.push({ numbers: nums, operator: "+", answer: sum });
      } else if (operation === "Subtraction") {
        const nums = digitLengths.map((digits) => randomNumber(digits));
        const subtractors = nums.slice(1);
        const subtractorTotal = subtractors.reduce((acc, val) => acc + val, 0);
        const first = Math.max(nums[0], subtractorTotal + randomNumber(digitLengths[0]));
        result.push({ numbers: [first, ...subtractors], operator: "-", answer: first - subtractorTotal });
      } else if (operation === "Multiplication") {
        const nums = digitLengths.map((digits) => randomNumber(Math.min(digits, 2)));
        const product = nums.reduce((acc, val) => acc * val, 1);
        result.push({ numbers: nums, operator: "×", answer: product });
      } else {
        const divisorDigits = digitLengths.slice(1);
        const divisors = divisorDigits.map((digits) => Math.max(1, randomNumber(Math.min(digits, 2))));
        const divisorProduct = divisors.reduce((acc, val) => acc * val, 1);
        const quotient = randomNumber(Math.min(digitLengths[0], 3));
        result.push({ numbers: [divisorProduct * quotient, ...divisors], operator: "÷", answer: quotient });
      }
    }

    return result;
  };

  const handleGenerate = () => {
    setIsGenerating(true);
    setPreviewVisible(false);
    window.setTimeout(() => {
      const result = generateQuestions();
      setWorksheetQuestions(result);
      setIsGenerating(false);
      setPreviewVisible(true);
    }, 300);
  };

  const buildPrintHtml = (items: { numbers: number[]; operator: string; answer: number }[]) => {
    const rowsPerPage = Math.max(1, Number(rows));
    const pages = Math.ceil(items.length / rowsPerPage);
    const title = `Worksheet Generator - ${previewSummary}`;
    const renderQuestion = (item: { numbers: number[]; operator: string; answer: number }, idx: number) => `
      <div class="question">
        <div class="q-title">Q${idx + 1}.</div>
        <div class="nums">
          ${item.numbers
            .map((num, i) => `
              <div class="row">
                <span class="op">${i === item.numbers.length - 1 ? item.operator : ""}</span>
                <span class="num">${num}</span>
              </div>
            `)
            .join("")}
          <div class="line"></div>
        </div>
      </div>
    `;
    const renderPage = (pageIndex: number) => {
      const start = pageIndex * rowsPerPage;
      const slice = items.slice(start, start + rowsPerPage);
      const pageColumns = Math.min(Math.ceil(slice.length / rowsPerPage), 4);
      return `
        <div class="page">
          <div class="page-header">
            <h1>${title}</h1>
            <div class="meta">Generated: ${new Date().toLocaleDateString()}</div>
          </div>
          <div class="grid" style="grid-template-columns: repeat(${pageColumns}, minmax(0, 1fr)); gap: 16px;">
            ${slice.map(renderQuestion).join("")}
          </div>
        </div>
      `;
    };
    return `
      <!doctype html>
      <html>
        <head>
          <meta charset="UTF-8" />
          <title>${title}</title>
          <style>
            * { box-sizing: border-box; }
            body { font-family: Arial, sans-serif; margin: 24px; color: #111827; }
            .page { page-break-after: always; }
            .page:last-child { page-break-after: auto; }
            .page-header { display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 16px; }
            h1 { font-size: 18px; margin: 0; }
            .meta { font-size: 12px; color: #6b7280; }
            .grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
            .question { border: 1px solid #e5e7eb; border-radius: 8px; padding: 12px; }
            .q-title { font-weight: 700; font-size: 12px; color: #6d28d9; margin-bottom: 8px; text-align: center; }
            .nums { font-family: "Courier New", monospace; font-size: 14px; }
            .row { display: flex; justify-content: flex-end; gap: 8px; }
            .op { width: 12px; text-align: center; color: #10b981; font-weight: 700; }
            .line { border-top: 1px solid #374151; margin-top: 8px; height: 12px; }
            @media print { body { margin: 8mm; } }
          </style>
        </head>
        <body>
          ${Array.from({ length: pages }, (_, i) => renderPage(i)).join("")}
        </body>
      </html>
    `;
  };

  const handleDownloadPdf = () => {
    const items = worksheetQuestions.length ? worksheetQuestions : generateQuestions();
    if (!worksheetQuestions.length) {
      setWorksheetQuestions(items);
      setPreviewVisible(true);
    }
    const html = buildPrintHtml(items);
    const printWindow = window.open("", "_blank", "width=900,height=700");
    if (!printWindow) return;
    printWindow.document.open();
    printWindow.document.write(html);
    printWindow.document.close();
    printWindow.focus();
    printWindow.onload = () => {
      printWindow.print();
    };
  };

  return (
    <div className="min-h-screen bg-background">
      <Navbar />
      <main className="pt-16">
        <section className="py-16">
          <div className="container mx-auto px-4">
            <div className="max-w-4xl mx-auto rounded-2xl border border-border bg-white p-6 md:p-8 shadow-card">
              <div className="flex items-center justify-between gap-4 flex-wrap">
                <h1 className="text-3xl md:text-4xl font-heading font-bold text-foreground">Worksheet Generator</h1>
                <div className="flex items-center gap-2 rounded-full bg-[#f97316]/10 px-4 py-2 text-sm text-[#f97316]">
                  <Sparkles className="h-4 w-4" />
                  <span>Instant worksheets</span>
                </div>
              </div>

              <div className="mt-6 grid gap-4 md:grid-cols-3">
                <div className="space-y-2">
                  <Label>Operation</Label>
                  <Select value={operation} onValueChange={(value) => setOperation(value as typeof operation)}>
                    <SelectTrigger>
                      <SelectValue placeholder="Select operation" />
                    </SelectTrigger>
                    <SelectContent>
                      {operations.map((op) => (
                        <SelectItem key={op} value={op}>
                          {op}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                </div>
                <div className="space-y-2">
                  <Label>Total Number of Rows</Label>
                  <Select value={rows} onValueChange={(value) => setRows(value as typeof rows)}>
                    <SelectTrigger>
                      <SelectValue placeholder="Rows" />
                    </SelectTrigger>
                    <SelectContent>
                      {rowsOptions.map((option) => (
                        <SelectItem key={option} value={option}>
                          {option}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                </div>
                <div className="space-y-2">
                  <Label>Total Questions</Label>
                  <Select value={questions} onValueChange={(value) => setQuestions(value as typeof questions)}>
                    <SelectTrigger>
                      <SelectValue placeholder="Questions" />
                    </SelectTrigger>
                    <SelectContent>
                      {questionsOptions.map((option) => (
                        <SelectItem key={option} value={option}>
                          {option}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                </div>
              </div>

            <div className="mt-4 grid gap-4 md:grid-cols-3">
              {digitsByRow.slice(0, Number(rows)).map((digitLength, index) => (
                <div key={`number-${index + 1}`} className="space-y-2">
                  <Label>Number{index + 1} (Length Upto)</Label>
                  <Select value={digitLength} onValueChange={(value) => updateDigitLength(index, value)}>
                    <SelectTrigger>
                      <SelectValue placeholder="Upto No of Digits" />
                    </SelectTrigger>
                    <SelectContent>
                      {digitOptions.map((opt) => (
                        <SelectItem key={opt} value={opt}>
                          {opt}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                </div>
              ))}
            </div>

            <div className="mt-6 grid gap-3 md:grid-cols-2">
              <Button className="w-full bg-[#f97316] hover:bg-[#ea580c]" onClick={handleGenerate}>
                {isGenerating ? (
                  <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                ) : (
                  <Wand2 className="mr-2 h-4 w-4" />
                )}
                Generate Question
              </Button>
              <Button variant="secondary" className="w-full" onClick={handleDownloadPdf}>
                <Download className="mr-2 h-4 w-4" />
                Download PDF
              </Button>
            </div>

              {previewVisible && worksheetQuestions.length > 0 ? (
                <div className="mt-6 rounded-xl border border-dashed border-border bg-muted/40 p-4">
                  <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                      <p className="text-sm font-medium text-foreground">Preview</p>
                      <p className="text-xs text-muted-foreground">{previewSummary}</p>
                    </div>
                    <Button size="sm" variant="outline" onClick={handleGenerate} disabled={isGenerating}>
                      {isGenerating ? "Generating..." : "Refresh Preview"}
                    </Button>
                  </div>
                  <div
                    className="mt-4 grid gap-4"
                    style={{ gridTemplateColumns: `repeat(${previewColumns}, minmax(0, 1fr))` }}
                  >
                    {worksheetQuestions.map((item, index) => (
                      <div key={index} className="rounded-xl border border-border bg-white p-4 shadow-sm">
                        <p className="text-sm font-semibold text-[#6d28d9] text-center">Q{index + 1}.</p>
                        <div className="mt-3 space-y-2 font-mono text-sm">
                          {item.numbers.map((num, idx) => (
                            <div key={idx} className="flex justify-end gap-2">
                              <span className="w-4 text-center text-emerald-600 font-semibold">
                                {idx === item.numbers.length - 1 ? item.operator : ""}
                              </span>
                              <span>{num}</span>
                            </div>
                          ))}
                          <div className="border-t border-slate-400 pt-2 text-right font-semibold text-slate-900">
                            {"________"}
                          </div>
                        </div>
                      </div>
                    ))}
                  </div>
                </div>
              ) : null}
            </div>

          </div>
        </section>

        <section className="py-16 bg-white">
          <div className="container mx-auto px-4">
            <div className="max-w-3xl">
              <h2 className="text-3xl md:text-4xl font-heading font-bold text-foreground">
                Download Custom Abacus Worksheets
              </h2>
              <p className="mt-3 text-muted-foreground text-lg">
                Create worksheets tailored to your child or classroom. Choose operations, adjust the volume, and export
                as printable PDFs in seconds.
              </p>
            </div>
            <div className="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
              {features.map((feature) => (
                <div key={feature.title} className="rounded-2xl border border-border bg-white p-5 shadow-sm">
                  <div className="mb-4 flex h-11 w-11 items-center justify-center rounded-xl bg-[#f97316]/10 text-[#f97316]">
                    <feature.icon className="h-5 w-5" />
                  </div>
                  <h3 className="text-lg font-semibold text-foreground">{feature.title}</h3>
                  <p className="mt-2 text-sm text-muted-foreground">{feature.desc}</p>
                </div>
              ))}
            </div>
          </div>
        </section>

        <section className="py-16">
          <div className="container mx-auto px-4">
            <div className="text-center">
              <h2 className="text-3xl md:text-4xl font-heading font-bold text-foreground">How It Works</h2>
              <p className="mt-3 text-muted-foreground">
                Generate a personalized worksheet in just a few steps.
              </p>
            </div>
            <div className="mt-10 grid gap-6 md:grid-cols-3">
              {[
                {
                  title: "Select Operation",
                  desc: "Choose the math operation you want to practice.",
                  icon: Settings2,
                },
                {
                  title: "Set Questions & Rows",
                  desc: "Pick how many rows and total questions you need.",
                  icon: FileText,
                },
                {
                  title: "Generate & Download PDF",
                  desc: "Create instantly and download a printable worksheet.",
                  icon: Download,
                },
              ].map((step, index) => (
                <div key={step.title} className="rounded-2xl border border-border bg-white p-6 shadow-sm">
                  <div className="flex items-center gap-3">
                    <span className="flex h-9 w-9 items-center justify-center rounded-full bg-[#4B1E83] text-white font-semibold">
                      {index + 1}
                    </span>
                    <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-[#4B1E83]/10 text-[#4B1E83]">
                      <step.icon className="h-5 w-5" />
                    </div>
                  </div>
                  <h3 className="mt-4 text-lg font-semibold text-foreground">{step.title}</h3>
                  <p className="mt-2 text-sm text-muted-foreground">{step.desc}</p>
                </div>
              ))}
            </div>
          </div>
        </section>

        <section className="py-16 bg-white">
          <div className="container mx-auto px-4">
            <div className="text-center">
              <h2 className="text-3xl md:text-4xl font-heading font-bold text-foreground">Benefits</h2>
              <p className="mt-3 text-muted-foreground">Designed for everyone supporting student success.</p>
            </div>
            <div className="mt-10 grid gap-6 md:grid-cols-3">
              {benefits.map((benefit) => (
                <div key={benefit.title} className="rounded-2xl border border-border bg-white p-6 shadow-sm">
                  <div className="mb-4 flex h-11 w-11 items-center justify-center rounded-xl bg-[#f97316]/10 text-[#f97316]">
                    <benefit.icon className="h-5 w-5" />
                  </div>
                  <h3 className="text-lg font-semibold text-foreground">{benefit.title}</h3>
                  <p className="mt-2 text-sm text-muted-foreground">{benefit.desc}</p>
                </div>
              ))}
            </div>
          </div>
        </section>

        <section className="py-16">
          <div className="container mx-auto px-4">
            <div className="max-w-3xl">
              <h2 className="text-3xl md:text-4xl font-heading font-bold text-foreground">FAQ</h2>
              <p className="mt-3 text-muted-foreground">Find quick answers to common questions.</p>
            </div>
            <div className="mt-8 max-w-3xl">
              <Accordion type="single" collapsible className="space-y-3">
                {faqs.map((item, index) => (
                  <AccordionItem key={item.q} value={`faq-${index}`} className="rounded-2xl border border-border bg-white px-4">
                    <AccordionTrigger className="text-left text-base font-medium text-foreground">
                      {item.q}
                    </AccordionTrigger>
                    <AccordionContent className="text-sm text-muted-foreground">
                      {item.a}
                    </AccordionContent>
                  </AccordionItem>
                ))}
              </Accordion>
            </div>
          </div>
        </section>
      </main>
      <Footer />
    </div>
  );
};

export default WorksheetGenerator;
