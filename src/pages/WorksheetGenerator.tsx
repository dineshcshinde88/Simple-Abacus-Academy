import { useEffect, useMemo, useState } from "react";
import Navbar from "@/components/layout/Navbar";
import Footer from "@/components/layout/Footer";
import { Button } from "@/components/ui/button";
import { Label } from "@/components/ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from "@/components/ui/accordion";
import { Download, FileText, Loader2, Settings2, Sparkles, Wand2, CheckCircle2, Users, GraduationCap, HeartHandshake } from "lucide-react";

const operations = ["Addition", "Subtraction", "Multiplication", "Division"] as const;
const rowsOptions = ["2", "3", "4", "5", "6", "7", "8", "9", "10"] as const;
const questionsOptions = ["10", "20", "30", "50", "100"] as const;
const digitOptions = ["1", "2", "3", "4", "5"] as const;

type Operation = (typeof operations)[number];
type RowOption = (typeof rowsOptions)[number];
type QuestionOption = (typeof questionsOptions)[number];
type WorksheetQuestion = { numbers: number[]; operator: string; answer: number };

const features = [
  { title: "Complete Customization", desc: "Pick operations, rows, and question counts to match each learner.", icon: Settings2 },
  { title: "Auto Answer Keys", desc: "Get instant answers with every worksheet for quick checking.", icon: CheckCircle2 },
  { title: "Unlimited Worksheets", desc: "Generate as many practice sets as you need, anytime.", icon: Sparkles },
  { title: "Free Access", desc: "Create worksheets without extra cost or hidden fees.", icon: HeartHandshake },
];

const benefits = [
  { title: "For Teachers", desc: "Save prep time and deliver differentiated practice instantly.", icon: GraduationCap },
  { title: "For Parents", desc: "Support daily practice at home with structured worksheets.", icon: Users },
  { title: "For Students", desc: "Build speed, accuracy, and confidence with consistent drills.", icon: FileText },
];

const faqs = [
  { q: "Is the worksheet generator free to use?", a: "Yes. You can generate unlimited worksheets without any fees." },
  { q: "Can I download worksheets as PDF?", a: "Yes. Use the Download PDF button to save and print worksheets." },
  { q: "Do worksheets include answer keys?", a: "Yes. Each worksheet can include an auto-generated answer key." },
  { q: "Can I change operations and difficulty?", a: "Yes. Select an operation and adjust rows and questions for the right level." },
  { q: "Will this work on mobile devices?", a: "Absolutely. The generator is fully responsive for phones and tablets." },
];

const WorksheetGenerator = () => {
  const [operation, setOperation] = useState<Operation>("Addition");
  const [rows, setRows] = useState<RowOption>("10");
  const [questions, setQuestions] = useState<QuestionOption>("20");
  const [digitsByRow, setDigitsByRow] = useState<string[]>(() =>
    Array.from({ length: Number(rowsOptions[rowsOptions.length - 1]) }, (_, index) => (index === 0 ? "3" : "2")),
  );
  const [isGenerating, setIsGenerating] = useState(false);
  const [previewVisible, setPreviewVisible] = useState(false);
  const [worksheetQuestions, setWorksheetQuestions] = useState<WorksheetQuestion[]>([]);

  const isTwoNumberOperation = operation === "Multiplication" || operation === "Division";
  const availableRowsOptions = useMemo(() => (isTwoNumberOperation ? ["2"] : rowsOptions), [isTwoNumberOperation]);

  useEffect(() => {
    if (isTwoNumberOperation && rows !== "2") setRows("2");
  }, [isTwoNumberOperation, rows]);

  const previewSummary = useMemo(() => `${operation} - ${rows} Rows - ${questions} Questions`, [operation, rows, questions]);
  const previewColumns = useMemo(() => Math.min(Number(questions), 2), [questions]);

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
    setWorksheetQuestions([]);
    setPreviewVisible(false);
  };

  const updateOperation = (value: Operation) => {
    setOperation(value);
    setWorksheetQuestions([]);
    setPreviewVisible(false);
  };

  const updateRows = (value: RowOption) => {
    setRows(value);
    setWorksheetQuestions([]);
    setPreviewVisible(false);
  };

  const updateQuestions = (value: QuestionOption) => {
    setQuestions(value);
    setWorksheetQuestions([]);
    setPreviewVisible(false);
  };

  const generateQuestions = () => {
    const total = Number(questions);
    const result: WorksheetQuestion[] = [];
    const digitLengths = isTwoNumberOperation ? getActiveDigitLengths().slice(0, 2) : getActiveDigitLengths();

    for (let i = 0; i < total; i += 1) {
      if (operation === "Addition") {
        const nums = digitLengths.map((digits) => randomNumber(digits));
        result.push({ numbers: nums, operator: "+", answer: nums.reduce((acc, val) => acc + val, 0) });
      } else if (operation === "Subtraction") {
        const nums = digitLengths.map((digits) => randomNumber(digits));
        const subtractorTotal = nums.slice(1).reduce((acc, val) => acc + val, 0);
        const first = Math.max(nums[0], subtractorTotal + randomNumber(digitLengths[0]));
        result.push({ numbers: [first, ...nums.slice(1)], operator: "-", answer: first - subtractorTotal });
      } else if (operation === "Multiplication") {
        const nums = digitLengths.slice(0, 2).map((digits) => randomNumber(digits));
        result.push({ numbers: nums, operator: "x", answer: nums[0] * nums[1] });
      } else {
        const divisor = Math.max(1, randomNumber(digitLengths[1] || 1));
        const quotient = randomNumber(digitLengths[0] || 1);
        result.push({ numbers: [divisor * quotient, divisor], operator: "÷", answer: quotient });
      }
    }

    return result;
  };

  const handleGenerate = () => {
    setIsGenerating(true);
    setPreviewVisible(false);
    window.setTimeout(() => {
      setWorksheetQuestions(generateQuestions());
      setIsGenerating(false);
      setPreviewVisible(true);
    }, 300);
  };

  const buildPrintHtml = (items: WorksheetQuestion[]) => {
    const questionsPerPage = Number(rows) >= 8 ? 6 : 8;
    const pages = Math.ceil(items.length / questionsPerPage);
    const title = `Worksheet Generator - ${previewSummary}`;
    const generated = new Date().toLocaleDateString();
    const renderQuestion = (item: WorksheetQuestion, idx: number) => `
      <div class="question">
        <div class="q-title">Q${idx + 1}.</div>
        <div class="nums">
          ${item.numbers.map((num, i) => `
            <div class="row">
              <span class="op">${i === item.numbers.length - 1 ? item.operator : ""}</span>
              <span class="num">${num}</span>
            </div>
          `).join("")}
          <div class="line"></div>
          <div class="answer-space"></div>
        </div>
      </div>
    `;
    const renderPage = (pageIndex: number) => {
      const slice = items.slice(pageIndex * questionsPerPage, pageIndex * questionsPerPage + questionsPerPage);
      return `
        <section class="page">
          <header class="page-header">
            <div>
              <div class="brand">SIMPLE ABACUS</div>
              <h1>${title}</h1>
            </div>
            <div class="meta">Generated: ${generated}</div>
          </header>
          <div class="grid">${slice.map(renderQuestion).join("")}</div>
        </section>
      `;
    };

    return `<!doctype html>
<html>
<head>
  <meta charset="UTF-8" />
  <title>${title}</title>
  <style>
    * { box-sizing: border-box; }
    @page { size: A4; margin: 10mm; }
    body { font-family: Arial, sans-serif; margin: 0; color: #111827; background: #fff; }
    .page { page-break-after: always; }
    .page:last-child { page-break-after: auto; }
    .page-header { display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; margin-bottom: 12px; border-bottom: 2px solid #f97316; padding-bottom: 8px; }
    .brand { color: #ef233c; font-size: 20px; font-weight: 900; letter-spacing: .5px; }
    h1 { font-size: 14px; margin: 4px 0 0; }
    .meta { color: #6b7280; font-size: 12px; white-space: nowrap; }
    .grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; align-items: start; }
    .question { min-height: ${Number(rows) >= 8 ? "196px" : "146px"}; border: 1px solid #d1d5db; border-radius: 8px; padding: 10px 12px; break-inside: avoid; page-break-inside: avoid; }
    .q-title { color: #6d28d9; font-size: 12px; font-weight: 700; margin-bottom: 8px; text-align: center; }
    .nums { width: 104px; margin-left: auto; font-family: "Courier New", monospace; font-size: 13px; line-height: 1.25; }
    .row { display: grid; grid-template-columns: 18px 1fr; gap: 8px; text-align: right; }
    .op { color: #059669; font-weight: 700; text-align: center; }
    .num { display: block; min-width: 64px; }
    .line { border-top: 1.5px solid #374151; margin-top: 6px; }
    .answer-space { width: 104px; height: 16px; border-bottom: 1px solid #9ca3af; margin-left: auto; margin-top: 4px; }
    @media print { body { print-color-adjust: exact; -webkit-print-color-adjust: exact; } }
  </style>
</head>
<body>${Array.from({ length: pages }, (_, i) => renderPage(i)).join("")}</body>
</html>`;
  };

  const handleDownloadPdf = () => {
    const items = worksheetQuestions.length ? worksheetQuestions : generateQuestions();
    if (!worksheetQuestions.length) {
      setWorksheetQuestions(items);
      setPreviewVisible(true);
    }
    const printWindow = window.open("", "_blank", "width=900,height=700");
    if (!printWindow) return;
    printWindow.document.open();
    printWindow.document.write(buildPrintHtml(items));
    printWindow.document.close();
    printWindow.focus();
    printWindow.onload = () => printWindow.print();
  };

  return (
    <div className="min-h-screen bg-background">
      <Navbar />
      <main className="pt-16">
        <section className="py-16">
          <div className="container mx-auto px-4">
            <div className="mx-auto max-w-4xl rounded-2xl border border-border bg-white p-6 shadow-card md:p-8">
              <div className="flex flex-wrap items-center justify-between gap-4">
                <h1 className="text-3xl font-heading font-bold text-foreground md:text-4xl">Worksheet Generator</h1>
                <div className="flex items-center gap-2 rounded-full bg-[#f97316]/10 px-4 py-2 text-sm text-[#f97316]">
                  <Sparkles className="h-4 w-4" />
                  <span>Instant worksheets</span>
                </div>
              </div>

              <div className="mt-6 grid gap-4 md:grid-cols-3">
                <div className="space-y-2">
                  <Label>Operation</Label>
                  <Select value={operation} onValueChange={(value) => updateOperation(value as Operation)}>
                    <SelectTrigger><SelectValue placeholder="Select operation" /></SelectTrigger>
                    <SelectContent>{operations.map((op) => <SelectItem key={op} value={op}>{op}</SelectItem>)}</SelectContent>
                  </Select>
                </div>
                <div className="space-y-2">
                  <Label>Total Number of Rows</Label>
                  <Select value={rows} onValueChange={(value) => updateRows(value as RowOption)}>
                    <SelectTrigger><SelectValue placeholder="Rows" /></SelectTrigger>
                    <SelectContent>{availableRowsOptions.map((option) => <SelectItem key={option} value={option}>{option}</SelectItem>)}</SelectContent>
                  </Select>
                  {isTwoNumberOperation ? <p className="text-xs text-muted-foreground">Multiplication and Division use 2 rows only.</p> : null}
                </div>
                <div className="space-y-2">
                  <Label>Total Questions</Label>
                  <Select value={questions} onValueChange={(value) => updateQuestions(value as QuestionOption)}>
                    <SelectTrigger><SelectValue placeholder="Questions" /></SelectTrigger>
                    <SelectContent>{questionsOptions.map((option) => <SelectItem key={option} value={option}>{option}</SelectItem>)}</SelectContent>
                  </Select>
                </div>
              </div>

              <div className="mt-4 grid gap-4 md:grid-cols-3">
                {digitsByRow.slice(0, Number(rows)).map((digitLength, index) => (
                  <div key={`number-${index + 1}`} className="space-y-2">
                    <Label>Number{index + 1} (Length Upto)</Label>
                    <Select value={digitLength} onValueChange={(value) => updateDigitLength(index, value)}>
                      <SelectTrigger><SelectValue placeholder="Upto No of Digits" /></SelectTrigger>
                      <SelectContent>{digitOptions.map((opt) => <SelectItem key={opt} value={opt}>{opt}</SelectItem>)}</SelectContent>
                    </Select>
                  </div>
                ))}
              </div>

              <div className="mt-6 grid gap-3 md:grid-cols-2">
                <Button className="w-full bg-[#f97316] hover:bg-[#ea580c]" onClick={handleGenerate}>
                  {isGenerating ? <Loader2 className="mr-2 h-4 w-4 animate-spin" /> : <Wand2 className="mr-2 h-4 w-4" />}
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
                    <Button size="sm" variant="outline" onClick={handleGenerate} disabled={isGenerating}>{isGenerating ? "Generating..." : "Refresh Preview"}</Button>
                  </div>
                  <div className="mt-4 grid gap-4" style={{ gridTemplateColumns: `repeat(${previewColumns}, minmax(0, 1fr))` }}>
                    {worksheetQuestions.map((item, index) => (
                      <div key={index} className="rounded-xl border border-border bg-white p-4 shadow-sm">
                        <p className="text-center text-sm font-semibold text-[#6d28d9]">Q{index + 1}.</p>
                        <div className="mt-3 space-y-2 font-mono text-sm">
                          {item.numbers.map((num, idx) => (
                            <div key={idx} className="flex justify-end gap-2">
                              <span className="w-4 text-center font-semibold text-emerald-600">{idx === item.numbers.length - 1 ? item.operator : ""}</span>
                              <span>{num}</span>
                            </div>
                          ))}
                          <div className="border-t border-slate-400 pt-2 text-right font-semibold text-slate-900">________</div>
                        </div>
                      </div>
                    ))}
                  </div>
                </div>
              ) : null}
            </div>
          </div>
        </section>

        <section className="bg-white py-16">
          <div className="container mx-auto px-4">
            <div className="max-w-3xl">
              <h2 className="text-3xl font-heading font-bold text-foreground md:text-4xl">Download Custom Abacus Worksheets</h2>
              <p className="mt-3 text-lg text-muted-foreground">Create worksheets tailored to your child or classroom. Choose operations, adjust the volume, and export as printable PDFs in seconds.</p>
            </div>
            <div className="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
              {features.map((feature) => (
                <div key={feature.title} className="rounded-2xl border border-border bg-white p-5 shadow-sm">
                  <div className="mb-4 flex h-11 w-11 items-center justify-center rounded-xl bg-[#f97316]/10 text-[#f97316]"><feature.icon className="h-5 w-5" /></div>
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
              <h2 className="text-3xl font-heading font-bold text-foreground md:text-4xl">How It Works</h2>
              <p className="mt-3 text-muted-foreground">Generate a personalized worksheet in just a few steps.</p>
            </div>
            <div className="mt-10 grid gap-6 md:grid-cols-3">
              {[
                { title: "Select Operation", desc: "Choose the math operation you want to practice.", icon: Settings2 },
                { title: "Set Questions & Rows", desc: "Pick rows and total questions. Multiplication and Division use 2 rows.", icon: FileText },
                { title: "Generate & Download PDF", desc: "Create instantly and download a printable worksheet.", icon: Download },
              ].map((step, index) => (
                <div key={step.title} className="rounded-2xl border border-border bg-white p-6 shadow-sm">
                  <div className="flex items-center gap-3">
                    <span className="flex h-9 w-9 items-center justify-center rounded-full bg-[#4B1E83] font-semibold text-white">{index + 1}</span>
                    <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-[#4B1E83]/10 text-[#4B1E83]"><step.icon className="h-5 w-5" /></div>
                  </div>
                  <h3 className="mt-4 text-lg font-semibold text-foreground">{step.title}</h3>
                  <p className="mt-2 text-sm text-muted-foreground">{step.desc}</p>
                </div>
              ))}
            </div>
          </div>
        </section>

        <section className="bg-white py-16">
          <div className="container mx-auto px-4">
            <div className="text-center">
              <h2 className="text-3xl font-heading font-bold text-foreground md:text-4xl">Benefits</h2>
              <p className="mt-3 text-muted-foreground">Designed for everyone supporting student success.</p>
            </div>
            <div className="mt-10 grid gap-6 md:grid-cols-3">
              {benefits.map((benefit) => (
                <div key={benefit.title} className="rounded-2xl border border-border bg-white p-6 shadow-sm">
                  <div className="mb-4 flex h-11 w-11 items-center justify-center rounded-xl bg-[#f97316]/10 text-[#f97316]"><benefit.icon className="h-5 w-5" /></div>
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
              <h2 className="text-3xl font-heading font-bold text-foreground md:text-4xl">FAQ</h2>
              <p className="mt-3 text-muted-foreground">Find quick answers to common questions.</p>
            </div>
            <div className="mt-8 max-w-3xl">
              <Accordion type="single" collapsible className="space-y-3">
                {faqs.map((item, index) => (
                  <AccordionItem key={item.q} value={`faq-${index}`} className="rounded-2xl border border-border bg-white px-4">
                    <AccordionTrigger className="text-left text-base font-medium text-foreground">{item.q}</AccordionTrigger>
                    <AccordionContent className="text-sm text-muted-foreground">{item.a}</AccordionContent>
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
