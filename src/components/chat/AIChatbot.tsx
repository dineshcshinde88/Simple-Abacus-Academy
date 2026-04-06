import { FormEvent, useEffect, useMemo, useRef, useState } from "react";
import { Bot, MessageCircle, SendHorizontal, X, Menu, ThumbsUp, Paperclip, Smile } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { cn } from "@/lib/utils";
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
  "What is the age group?",
];

const whatsappNumber = "919999999999";
const whatsappMessage = "Hi, I want to know more about your abacus program.";
const whatsappLink = `https://wa.me/${whatsappNumber}?text=${encodeURIComponent(whatsappMessage)}`;

const initialMessage: ChatMessage = {
  id: 1,
  role: "bot",
  text: "Hi! I am your Abacus AI assistant. Ask me about programs, demo class booking, timings, or fees.",
};

function getBotReply(input: string): string {
  const text = input.toLowerCase();

  if (text.includes("program") || text.includes("course") || text.includes("level")) {
    return "We provide structured abacus levels from beginner to advanced with practice sessions and progress tracking. You can explore full details on the Programs page.";
  }

  if (text.includes("demo") || text.includes("trial") || text.includes("book")) {
    return "You can book a free demo from the Contact page. Share your name, phone, and preferred time, and our team will confirm your slot.";
  }

  if (text.includes("age") || text.includes("class") || text.includes("grade")) {
    return "Our classes are generally best for children aged 5 to 14, with level-based batches so each student learns at the right pace.";
  }

  if (text.includes("fee") || text.includes("price") || text.includes("cost")) {
    return "Fees vary by level and batch format. Please use the Contact page and we will share the latest fee structure with available offers.";
  }

  if (text.includes("time") || text.includes("timing") || text.includes("schedule")) {
    return "We run weekday and weekend batches. Tell us your preferred time on the Contact page and we will recommend a suitable batch.";
  }

  return "I can help with programs, fees, demo classes, age groups, and schedules. Ask me any one of these to get started.";
}

const AIChatbot = () => {
  const [isOpen, setIsOpen] = useState(false);
  const [input, setInput] = useState("");
  const [isTyping, setIsTyping] = useState(false);
  const [messages, setMessages] = useState<ChatMessage[]>([initialMessage]);
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
            <div className="w-[calc(100vw-2rem)] max-w-sm overflow-hidden rounded-3xl border border-emerald-200 bg-white shadow-card">
            <div className="bg-[#43B754] px-4 py-3 text-white">
              <div className="flex items-center justify-between">
                <div className="flex items-center gap-3">
                  <button type="button" className="rounded-full p-1 hover:bg-white/10" aria-label="Back">
                    <MessageCircle className="h-4 w-4" />
                  </button>
                  <div>
                    <p className="text-sm font-semibold leading-none">Customer Support</p>
                    <p className="mt-1 text-[11px] text-white/80">We are here to help</p>
                  </div>
                </div>
                <div className="flex items-center gap-2">
                  <button type="button" className="rounded-full p-1 hover:bg-white/10" aria-label="Menu">
                    <Menu className="h-4 w-4" />
                  </button>
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
                    shortly and help you with details. Feel free to reach us on +91 7842 885 885 &amp;
                    info@abacustrainer.com!
                  </div>
                  <div className="max-w-[85%] rounded-2xl rounded-tl-sm bg-[#43B754] px-4 py-3 text-sm text-white shadow-sm">
                    Hi, how may I help you?
                  </div>
                </div>
              </div>

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
                <button type="button" className="rounded-full p-2 text-emerald-600 hover:bg-emerald-50" aria-label="Like">
                  <ThumbsUp className="h-4 w-4" />
                </button>
                <button type="button" className="rounded-full p-2 text-emerald-600 hover:bg-emerald-50" aria-label="Attach">
                  <Paperclip className="h-4 w-4" />
                </button>
                <button type="button" className="rounded-full p-2 text-emerald-600 hover:bg-emerald-50" aria-label="Emoji">
                  <Smile className="h-4 w-4" />
                </button>
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
