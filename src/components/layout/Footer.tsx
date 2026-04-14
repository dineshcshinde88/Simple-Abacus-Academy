import { Link } from "react-router-dom";
import {
  Facebook,
  Instagram,
  Mail,
  MapPin,
  Phone,
  ChevronRight,
  Youtube,
} from "lucide-react";

const quickLinks = [
  { label: "Home", to: "/" },
  { label: "About Us", to: "/about" },
  { label: "Pricing", to: "/shop" },
  { label: "Courses", to: "/programs" },
  { label: "Abacus Academy", to: "/about" },
  { label: "Syllabus", to: "/programs" },
  { label: "Blogs", to: "/blogs" },
  { label: "Contact Us", to: "/contact" },
  { label: "FAQ", to: "/contact" },
  { label: "Testimonial", to: "/testimonials" },
  { label: "Meet Our Instructors", to: "/teacher-training" },
  { label: "Abacus Training In Hyd", to: "/online-abacus-classes" },
  { label: "Vedic Maths Training In Hyd", to: "/vedic-maths-classes" },
];

const policyLinks = [
  { label: "Privacy Policy", to: "/" },
  { label: "Terms and Conditions", to: "/" },
  { label: "Refund Policy", to: "/" },
];

const loginLinks = [
  { label: "Instructor Login", to: "/instructor-login" },
  { label: "Instructor Registration", to: "/instructor-registration" },
  { label: "Student Login", to: "/student-login" },
  { label: "Student Registration", to: "/student-registration" },
  { label: "Franchise Registration", to: "/franchise" },
];

const affiliators = [
  { label: "Affiliator Registration", to: "/" },
  { label: "Affiliator Login", to: "/" },
];

const digitalTools = [
  { label: "Abacus Digital Frame", to: "/digital-frame" },
];

const Footer = () => (
  <footer className="relative bg-[#4B1E83] text-white">
    <div className="absolute inset-0 opacity-40 bg-[radial-gradient(circle_at_1px_1px,_rgba(255,255,255,0.12)_1px,_transparent_0)] [background-size:16px_16px]" />
    <div className="relative container mx-auto px-4 py-16">
      <div className="grid gap-12 lg:grid-cols-[1.1fr_1.4fr_0.8fr_1fr]">
        <div>
          <div className="inline-flex items-center rounded-md bg-white p-2">
            <img src="/abacus_logo.svg" alt="Abacus Trainer" className="h-10 w-auto" />
          </div>
          <p className="mt-4 text-sm text-white/85 leading-relaxed">
            Simple Abacus Academy brings 4+ years of expertise in Abacus and Vedic Maths, trusted by 5,000+ students and
            teachers. We offer online courses for children, school programs, competition conductor, teacher training,
            franchise support, and worksheet subscriptions, providing end-to-end solutions for both learning and teaching.
          </p>
          <div className="mt-6 flex items-center gap-3">
            <a
              href="https://www.facebook.com/share/1ZvbKHAAch/"
              className="inline-flex h-9 w-9 items-center justify-center rounded-full bg-white text-[#4B1E83]"
              aria-label="Facebook"
              target="_blank"
              rel="noopener noreferrer"
            >
              <Facebook className="h-4 w-4" />
            </a>
            <a
              href="https://www.instagram.com/simple_abacus?utm_source=qr&igsh=cXpjc3dnazdmeHRo"
              className="inline-flex h-9 w-9 items-center justify-center rounded-full bg-white text-[#4B1E83]"
              aria-label="Instagram"
              target="_blank"
              rel="noopener noreferrer"
            >
              <Instagram className="h-4 w-4" />
            </a>
            <a
              href="https://youtube.com/@varshashindeofficial6475?si=7SDTtM2vysfCtT6I"
              className="inline-flex h-9 w-9 items-center justify-center rounded-full bg-white text-[#4B1E83]"
              aria-label="YouTube"
              target="_blank"
              rel="noopener noreferrer"
            >
              <Youtube className="h-4 w-4" />
            </a>
          </div>
        </div>

        <div>
          <h3 className="text-xl font-heading font-bold">Quick Links</h3>
          <div className="mt-4 grid gap-2 sm:grid-cols-2">
            {quickLinks.map((link) => (
              <Link key={link.label} to={link.to} className="flex items-center gap-2 text-sm text-white/85 hover:text-white">
                <ChevronRight className="h-3.5 w-3.5 shrink-0" /><span>{link.label}</span>
              </Link>
            ))}
          </div>
          <div className="mt-6 grid gap-2">
            {policyLinks.map((link) => (
              <Link key={link.label} to={link.to} className="flex items-center gap-2 text-sm text-white/85 hover:text-white">
                <ChevronRight className="h-3.5 w-3.5 shrink-0" /><span>{link.label}</span>
              </Link>
            ))}
          </div>
        </div>

        <div className="space-y-8">
          <div>
            <h3 className="text-xl font-heading font-bold">Login</h3>
            <div className="mt-4 grid gap-2">
              {loginLinks.map((link) => (
                <Link key={link.label} to={link.to} className="flex items-center gap-2 text-sm text-white/85 hover:text-white">
                  <ChevronRight className="h-3.5 w-3.5 shrink-0" /><span>{link.label}</span>
                </Link>
              ))}
            </div>
          </div>
          <div>
            <h3 className="text-xl font-heading font-bold">Affiliators</h3>
            <div className="mt-4 grid gap-2">
              {affiliators.map((link) => (
                <Link key={link.label} to={link.to} className="flex items-center gap-2 text-sm text-white/85 hover:text-white">
                  <ChevronRight className="h-3.5 w-3.5 shrink-0" /><span>{link.label}</span>
                </Link>
              ))}
            </div>
          </div>
        </div>

        <div className="space-y-8">
          <div>
            <h3 className="text-xl font-heading font-bold">Find Us</h3>
            <div className="mt-4 space-y-3 text-sm text-white/85">
              <div className="flex items-center gap-2">
                <Phone className="h-4 w-4" />
                <span>+91 89991 64139</span>
              </div>
              <div className="flex items-center gap-2">
                <Phone className="h-4 w-4" />
                <span>+91 89991 64139</span>
              </div>
              <div className="flex items-center gap-2">
                <Mail className="h-4 w-4" />
                <span>simpleabacuspune@gmail.com</span>
              </div>
              <div className="flex items-start gap-2">
                <MapPin className="mt-1 h-4 w-4" />
                <span>
                  2-77/6, Narsingi, Gandipet Mandal, Ranga Reddy Dist, Behind Of Canara Bank, Hyderabad, Telangana,
                  INDIA - 500 089.
                </span>
              </div>
            </div>
          </div>
          <div>
            <h3 className="text-xl font-heading font-bold">Digital Tools</h3>
            <div className="mt-4 grid gap-2">
              {digitalTools.map((link) => (
                <Link key={link.label} to={link.to} className="flex items-center gap-2 text-sm text-white/85 hover:text-white">
                  <ChevronRight className="h-3.5 w-3.5 shrink-0" /><span>{link.label}</span>
                </Link>
              ))}
            </div>
          </div>
        </div>
      </div>

      <div className="mt-12 border-t border-white/10 pt-6 text-center text-sm text-white/70">
        Copyrights {new Date().getFullYear()}. All Rights Reserved By Simple Abacus Academy And Developed By{" "}
        <a
          href="https://webakoof.com"
          className="text-white/90 hover:text-white underline-offset-4 hover:underline"
          target="_blank"
          rel="noopener noreferrer"
        >
          Webakoof
        </a>
        .
      </div>
    </div>
  </footer>
);

export default Footer;



