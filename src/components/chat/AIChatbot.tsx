import { FormEvent, useEffect, useMemo, useRef, useState } from "react";
import { useLocation } from "react-router-dom";
import { Bot, MessageCircle, SendHorizontal, X } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { cn } from "@/lib/utils";
import { getApiBase } from "@/lib/apiBase";
import whatsappIcon from "@/assets/whatsapp-ic.png";

type ChatRole = "bot" | "user";

type ChatMessage = {
  id: number;
  role: ChatRole;
  text: string;
};

const quickPrompts = [
  "What programs do you offer?",
  "How do I book a demo class?",
  "Teacher training details",
  "Contact information",
];

const whatsappNumber = "918999164139";
const whatsappMessage = "Hi, I want to know more about your abacus program.";
const whatsappLink = `https://wa.me/${whatsappNumber}?text=${encodeURIComponent(whatsappMessage)}`;

const initialMessage: ChatMessage = {
  id: 1,
  role: "bot",
  text: "Hi! I am your Abacus AI assistant. Ask me about programs, demo class booking, timings, or fees.",
};

function getBotReply(input: string): string {
  const text = input.toLowerCase().replace(/[^a-z0-9\u0900-\u097f\s]/g, " ");

  if (/\b(hi|hello|hey|namaste)\b|नमस्कार|हाय/.test(text)) {
    return "Hello! I can help you with Abacus classes, Vedic Maths, teacher training, worksheets, demo booking, fees, timings, and contact details. What would you like to know?";
  }

  if (/demo|trial|book|डेमो/.test(text)) {
    return "You can book a free demo on the Book Demo page. Submit the student details and selected program there; our team will contact you to confirm the session.";
  }

  if (/teacher|instructor|training|certif|शिक्षक|ट्रेनिंग/.test(text)) {
    return "We offer Abacus Teacher Training and Vedic Maths Teacher Training with structured learning, practical guidance, and certification. Open the Teacher Training page to submit an enquiry.";
  }

  if (/vedic|वैदिक/.test(text)) {
    return "Our Vedic Maths program teaches calculation techniques that improve speed, accuracy, confidence, and number sense. Visit the Vedic Maths Classes page for course details.";
  }

  if (/worksheet|practice|सराव/.test(text)) {
    return "We provide Abacus and Vedic Maths worksheet subscriptions, timed practice, and level-based learning material. Visit the Worksheets section to view the available options.";
  }

  if (/program|course|level|abacus|कोर्स|अबॅकस/.test(text)) {
    return "Simple Abacus offers online Abacus classes, Vedic Maths classes, Foundation plus 7 Abacus levels, worksheets, teacher training, and a digital Abacus tool. Each Abacus level is designed as a structured progression with practice and assessment.";
  }

  if (/age|grade|वय/.test(text)) {
    return "Our classes are generally best for children aged 5 to 14, with level-based batches so each student learns at the right pace.";
  }

  if (/fee|price|cost|charges|फी|किंमत/.test(text)) {
    return "Fees depend on the selected program, level, and batch. For the current fee details, call or WhatsApp us on +91 89991 64139, or submit the Contact form.";
  }

  if (/time|timing|schedule|batch|वेळ|बॅच/.test(text)) {
    return "Batch availability can vary. Share your preferred day and time through the Contact form or WhatsApp +91 89991 64139, and our team will suggest an available batch.";
  }

  if (/contact|phone|mobile|call|whatsapp|email|address|location|पत्ता|फोन/.test(text)) {
    return "Contact Simple Abacus at +91 89991 64139 or simpleabacuspune@gmail.com. Our address is Kunjir Public School, Manjari Budruk, Pune, Maharashtra 412307. Working hours are Monday to Saturday, 9:00 AM to 7:00 PM.";
  }

  if (/thank|thanks|धन्यवाद/.test(text)) {
    return "You're welcome! If you need personal assistance, share your callback details above or contact us on +91 89991 64139.";
  }

  return "I don't have a verified answer for that yet. Please rephrase your question, or ask about programs, demo classes, teacher training, worksheets, fees, timings, age group, or contact details. You can also call or WhatsApp +91 89991 64139.";
}

const AIChatbot = () => {
  const { pathname } = useLocation();
  const [isOpen, setIsOpen] = useState(false);
  const [input, setInput] = useState("");
  const [isTyping, setIsTyping] = useState(false);
  const [messages, setMessages] = useState<ChatMessage[]>([initialMessage]);
  const [visitor, setVisitor] = useState({ name: "", phone: "", email: "" });
  const [visitorSaved, setVisitorSaved] = useState(false);
  const [visitorSaving, setVisitorSaving] = useState(false);
  const [visitorError, setVisitorError] = useState("");
  const viewportRef = useRef<HTMLDivElement | null>(null);

  const nextId = useMemo(() => messages.length + 1, [messages.length]);

  useEffect(() => {
    if (!viewportRef.current) {
      return;
    }

    viewportRef.current.scrollTo({
      top: viewportRef.current.scrollHeight,
      behavior: "smooth",
    });
  }, [messages, isTyping]);

  const sendMessage = (value: string) => {
    const content = value.trim();
    if (!content || isTyping) {
      return;
    }

    const userMessage: ChatMessage = {
      id: nextId,
      role: "user",
      text: content,
    };

    setMessages((prev) => [...prev, userMessage]);
    setInput("");
    setIsTyping(true);

    window.setTimeout(() => {
      const botMessage: ChatMessage = {
        id: nextId + 1,
        role: "bot",
        text: getBotReply(content),
      };
      setMessages((prev) => [...prev, botMessage]);
      setIsTyping(false);
    }, 700);
  };

  const handleSubmit = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    sendMessage(input);
  };

  const saveVisitor = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    setVisitorError("");
    if (!visitor.name.trim() || !/^\d{10}$/.test(visitor.phone)) {
      setVisitorError("Please enter your name and a valid 10-digit mobile number.");
      return;
    }

    setVisitorSaving(true);
    try {
      const response = await fetch(`${getApiBase()}/api/chatbot/enquiry`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(visitor),
      });
      const payload = await response.json().catch(() => ({}));
      if (!response.ok) throw new Error((payload as { message?: string }).message || "Unable to save your details.");
      setVisitorSaved(true);
      setMessages((previous) => [
        ...previous,
        { id: previous.length + 10, role: "bot", text: `Thank you, ${visitor.name.trim()}. Our team will contact you shortly.` },
      ]);
    } catch (error) {
      setVisitorError(error instanceof Error ? error.message : "Unable to save your details.");
    } finally {
      setVisitorSaving(false);
    }
  };

  const isDashboardRoute =
    pathname === "/teacher-dashboard" ||
    pathname === "/student/dashboard" ||
    pathname.startsWith("/student/");

  if (isDashboardRoute) {
    return null;
  }

  return (
    <>
      <a
        href={whatsappLink}
        target="_blank"
        rel="noopener noreferrer"
        aria-label="Chat on WhatsApp"
        className="fixed bottom-5 left-4 z-50 flex h-12 w-12 items-center justify-center overflow-hidden rounded-full shadow-glow transition hover:scale-105 md:bottom-6 md:left-6"
      >
        <img src={whatsappIcon} alt="WhatsApp" className="h-full w-full object-cover" />
      </a>

      <div className="fixed bottom-5 right-4 z-50 md:bottom-6 md:right-6">
        <div className="flex flex-col items-end gap-3">
          {isOpen && (
            <div className="relative w-[calc(100vw-2rem)] max-w-sm overflow-hidden rounded-3xl border border-emerald-200 bg-white shadow-card">
            <button
              type="button"
              aria-label="Close chatbot"
              className="absolute right-3 top-3 z-20 rounded-full bg-white/95 p-1.5 text-emerald-700 shadow-sm transition hover:bg-emerald-50"
              onClick={() => setIsOpen(false)}
            >
              <X className="h-4 w-4" />
            </button>
            <div className="bg-[#43B754] px-4 py-3 text-white">
              <div className="flex items-center justify-between">
                <div className="flex items-center gap-3">
                  <button
                    type="button"
                    className="rounded-full p-1 hover:bg-white/10"
                    aria-label="Restart conversation"
                    onClick={() => {
                      setMessages([initialMessage]);
                      setInput("");
                    }}
                  >
                    <MessageCircle className="h-4 w-4" />
                  </button>
                  <div>
                    <p className="text-sm font-semibold leading-none">Customer Support</p>
                    <p className="mt-1 text-[11px] text-white/80">We are here to help</p>
                  </div>
                </div>
                <div className="flex items-center gap-2">
                  <button
                    type="button"
                    aria-label="Close chatbot"
                    className="rounded-full p-1 hover:bg-white/10"
                    onClick={() => setIsOpen(false)}
                  >
                    <X className="h-4 w-4" />
                  </button>
                </div>
              </div>
            </div>

            <div ref={viewportRef} className="max-h-[420px] space-y-4 overflow-y-auto bg-white px-4 py-4">
              <div className="flex items-start gap-3">
                <div className="h-10 w-10 rounded-full bg-emerald-100 p-2">
                  <Bot className="h-6 w-6 text-emerald-600" />
                </div>
                <div className="space-y-2">
                  <div className="max-w-[85%] rounded-2xl rounded-tl-sm bg-[#43B754] px-4 py-3 text-sm text-white shadow-sm">
                    Welcome to our site. Please enter your name, phone number, and email-id. Our executive will reach you
                    shortly and help you with details. Feel free to reach us on +91 89991 64139 &amp;
                    simpleabacuspune@gmail.com!
                  </div>
                  <div className="max-w-[85%] rounded-2xl rounded-tl-sm bg-[#43B754] px-4 py-3 text-sm text-white shadow-sm">
                    Hi, how may I help you?
                  </div>
                </div>
              </div>

              {!visitorSaved && (
                <form onSubmit={saveVisitor} className="space-y-2 rounded-2xl border border-emerald-100 bg-emerald-50 p-3">
                  <p className="text-xs font-semibold text-emerald-900">Share your details for a callback</p>
                  <Input
                    value={visitor.name}
                    onChange={(event) => setVisitor((current) => ({ ...current, name: event.target.value }))}
                    placeholder="Your name"
                    className="h-9 bg-white"
                    required
                  />
                  <Input
                    value={visitor.phone}
                    onChange={(event) => setVisitor((current) => ({ ...current, phone: event.target.value.replace(/\D/g, "").slice(0, 10) }))}
                    placeholder="10-digit mobile number"
                    inputMode="numeric"
                    maxLength={10}
                    className="h-9 bg-white"
                    required
                  />
                  <Input
                    value={visitor.email}
                    onChange={(event) => setVisitor((current) => ({ ...current, email: event.target.value }))}
                    placeholder="Email (optional)"
                    type="email"
                    className="h-9 bg-white"
                  />
                  {visitorError && <p className="text-xs text-red-600">{visitorError}</p>}
                  <Button type="submit" size="sm" className="w-full bg-emerald-600 hover:bg-emerald-700" disabled={visitorSaving}>
                    {visitorSaving ? "Saving..." : "Request Callback"}
                  </Button>
                </form>
              )}

              {messages.map((message) => (
                <div
                  key={message.id}
                  className={cn("flex", {
                    "justify-start": message.role === "bot",
                    "justify-end": message.role === "user",
                  })}
                >
                  <div
                    className={cn("max-w-[80%] rounded-2xl px-4 py-2 text-sm leading-relaxed", {
                      "bg-emerald-50 text-emerald-900": message.role === "bot",
                      "bg-emerald-600 text-white": message.role === "user",
                    })}
                  >
                    {message.text}
                  </div>
                </div>
              ))}
              {isTyping && (
                <div className="max-w-[80%] rounded-2xl bg-emerald-50 px-4 py-2 text-sm text-emerald-900">
                  Typing...
                </div>
              )}
            </div>

            <div className="border-t border-emerald-100 bg-white p-3">
              <div className="mb-2 flex flex-wrap gap-2">
                {quickPrompts.map((prompt) => (
                  <button
                    key={prompt}
                    type="button"
                    className="rounded-full border border-emerald-100 bg-emerald-50 px-3 py-1 text-xs text-emerald-700 transition hover:bg-emerald-100"
                    onClick={() => sendMessage(prompt)}
                    disabled={isTyping}
                  >
                    {prompt}
                  </button>
                ))}
              </div>
              <form onSubmit={handleSubmit} className="flex items-center gap-2">
                <Input
                  value={input}
                  onChange={(event) => setInput(event.target.value)}
                  placeholder="Type here and press enter..."
                  className="h-11 border-emerald-200 bg-white focus-visible:ring-emerald-300"
                />
                <Button type="submit" size="icon" className="h-11 w-11 bg-emerald-600 hover:bg-emerald-700" disabled={isTyping}>
                  <SendHorizontal className="h-4 w-4" />
                  <span className="sr-only">Send</span>
                </Button>
              </form>
            </div>
          </div>
        )}

          <div className="relative flex flex-col items-center">
            <svg
              width="140"
              height="70"
              viewBox="0 0 140 70"
              className="absolute -top-10 overflow-visible"
            >
              <defs>
                <path id="arcText" d="M10,60 A60,60 0 0 1 130,60" />
              </defs>
              <text fill="#2bb673" fontSize="13" fontWeight="600">
                <textPath href="#arcText" startOffset="50%" textAnchor="middle">
                  We Are Here!
                </textPath>
              </text>
            </svg>
            <Button
              type="button"
              size="icon"
              onClick={() => setIsOpen((prev) => !prev)}
              className="h-14 w-14 rounded-full bg-[#43B754] text-white shadow-glow hover:bg-[#38a74a]"
              aria-label={isOpen ? "Close chatbot" : "Open chatbot"}
            >
              <MessageCircle className="h-6 w-6" />
            </Button>
          </div>
        </div>
      </div>
    </>
  );
};

export default AIChatbot;
