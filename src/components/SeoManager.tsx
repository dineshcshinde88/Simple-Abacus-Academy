import { useEffect } from "react";
import { useLocation } from "react-router-dom";

declare global {
  interface Window {
    gtag?: (...args: unknown[]) => void;
  }
}

const SITE_URL = "https://simpleabacus.com";
const DEFAULT_TITLE = "Simple Abacus - Abacus and Vedic Maths Classes for Kids";
const DEFAULT_DESCRIPTION =
  "Simple Abacus offers abacus classes, Vedic maths, worksheets, teacher training, online practice, exams, and certified mental math programs for kids.";

const routeSeo: Record<string, { title: string; description: string; keywords?: string }> = {
  "/": {
    title: DEFAULT_TITLE,
    description: DEFAULT_DESCRIPTION,
    keywords: "Simple Abacus, abacus classes, mental math for kids, online abacus classes",
  },
  "/about": {
    title: "About Simple Abacus - Mental Math Learning for Kids",
    description: "Learn about Simple Abacus, our mission, values, and child-friendly approach to abacus and mental maths education.",
  },
  "/programs": {
    title: "Abacus Programs - Beginner to Advanced Mental Math Courses",
    description: "Explore Simple Abacus programs for beginner, intermediate, and advanced learners with exams, certificates, and practice support.",
  },
  "/online-abacus-classes": {
    title: "Online Abacus Classes for Kids - Simple Abacus",
    description: "Join online abacus classes for kids with structured lessons, practice, exams, and certified trainers.",
  },
  "/vedic-maths-classes": {
    title: "Vedic Maths Classes for Students - Simple Abacus",
    description: "Learn Vedic maths tricks and calculation methods that help students improve speed, confidence, and number sense.",
  },
  "/teacher-training": {
    title: "Abacus Teacher Training and Certification - Simple Abacus",
    description: "Become a certified abacus teacher with Simple Abacus training, practicum support, teaching tools, and career guidance.",
  },
  "/teachers": {
    title: "Meet Our Abacus Teachers - Simple Abacus",
    description: "Meet Simple Abacus teachers and certified trainers who help children build focus, accuracy, and mental math confidence.",
  },
  "/worksheets-subscription": {
    title: "Worksheet Subscription - Abacus and Maths Practice",
    description: "Subscribe to Simple Abacus worksheets for regular abacus, mental math, and Vedic maths practice.",
  },
  "/abacus-worksheet-subscription": {
    title: "Abacus Worksheet Subscription - Simple Abacus",
    description: "Get abacus worksheets and structured practice material for children learning mental math.",
  },
  "/vedic-maths-worksheet-subscription": {
    title: "Vedic Maths Worksheet Subscription - Simple Abacus",
    description: "Practice Vedic maths with worksheet subscriptions built for speed, accuracy, and confidence.",
  },
  "/worksheet-generator": {
    title: "Worksheet Generator - Simple Abacus",
    description: "Generate practice worksheets for abacus and mental math learning with Simple Abacus.",
  },
  "/blogs": {
    title: "Simple Abacus Blog - Abacus, Vedic Maths and Learning Tips",
    description: "Read Simple Abacus blogs with learning tips, mental maths guidance, worksheets, and student resources.",
  },
  "/digital-frame": {
    title: "Digital Abacus Frame - Simple Abacus",
    description: "Use the Simple Abacus digital frame and learning tools for interactive mental math practice.",
  },
  "/franchise": {
    title: "Simple Abacus Franchise - Start an Education Business",
    description: "Start a Simple Abacus franchise and build an education business with abacus, Vedic maths, and teacher support.",
  },
  "/contact": {
    title: "Contact Simple Abacus - Abacus Classes in Pune and Online",
    description: "Contact Simple Abacus for abacus classes, Vedic maths classes, teacher training, worksheets, and demo sessions.",
  },
  "/book-demo": {
    title: "Book a Free Demo Class - Simple Abacus",
    description: "Book a free demo class for Simple Abacus programs and help your child experience mental maths learning.",
  },
  "/shop": {
    title: "Simple Abacus Shop - Abacus Kits and Learning Material",
    description: "Shop Simple Abacus kits, learning material, and teacher resources for abacus and mental maths practice.",
  },
  "/testimonials": {
    title: "Simple Abacus Testimonials - Parent and Student Reviews",
    description: "Read testimonials from Simple Abacus parents, students, and learners about abacus and mental maths programs.",
  },
};

function upsertMeta(selector: string, createAttrs: Record<string, string>, content: string): void {
  let element = document.head.querySelector<HTMLMetaElement>(selector);
  if (!element) {
    element = document.createElement("meta");
    Object.entries(createAttrs).forEach(([key, value]) => element?.setAttribute(key, value));
    document.head.appendChild(element);
  }
  element.setAttribute("content", content);
}

function upsertCanonical(href: string): void {
  let link = document.head.querySelector<HTMLLinkElement>('link[rel="canonical"]');
  if (!link) {
    link = document.createElement("link");
    link.rel = "canonical";
    document.head.appendChild(link);
  }
  link.href = href;
}

export default function SeoManager() {
  const location = useLocation();

  useEffect(() => {
    const path = location.pathname;
    const metadata = routeSeo[path] ?? routeSeo[path.replace(/\/$/, "")] ?? {
      title: DEFAULT_TITLE,
      description: DEFAULT_DESCRIPTION,
    };
    const canonical = `${SITE_URL}${path === "/" ? "/" : path}`;

    document.title = metadata.title;
    upsertMeta('meta[name="description"]', { name: "description" }, metadata.description);
    upsertMeta('meta[name="keywords"]', { name: "keywords" }, metadata.keywords ?? "Simple Abacus, abacus, Vedic maths, worksheets, teacher training");
    upsertMeta('meta[property="og:title"]', { property: "og:title" }, metadata.title);
    upsertMeta('meta[property="og:description"]', { property: "og:description" }, metadata.description);
    upsertMeta('meta[property="og:url"]', { property: "og:url" }, canonical);
    upsertMeta('meta[name="twitter:title"]', { name: "twitter:title" }, metadata.title);
    upsertMeta('meta[name="twitter:description"]', { name: "twitter:description" }, metadata.description);
    upsertCanonical(canonical);

    window.gtag?.("config", "G-C4GV81Q7WQ", {
      page_path: `${location.pathname}${location.search}`,
      page_title: metadata.title,
    });
  }, [location.pathname, location.search]);

  return null;
}
