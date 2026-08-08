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
  { label: "Simple Abacus", to: "/about" },
  { label: "Blogs", to: "/blogs" },
  { label: "Contact Us", to: "/contact" },
  { label: "Testimonial", to: "/testimonials" },
  { label: "Meet Our Instructor", to: "/teachers" },
  { label: "Abacus Training In Pune", to: "/online-abacus-classes" },
  { label: "Vedic Maths Training In Pune", to: "/vedic-maths-classes" },
];

const policyLinks = [
  { label: "Privacy Policy", to: "/privacy-policy" },
  { label: "Terms and Conditions", to: "/terms-and-conditions" },
  { label: "Refund Policy", to: "/refund-cancellation-policy" },
];

const loginLinks = [
  { label: "Instructor Login", to: "/instructor-login" },
  { label: "Instructor Registration", to: "/instructor-registration" },
  { label: "Student Login", to: "/student-login" },
  { label: "Student Registration", to: "/student-registration" },
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
            <img src="/abacus_logo.svg" alt="Simple Abacus" className="h-10 w-auto" />
          </div>
          <p className="mt-4 text-sm text-white/85 leading-relaxed">
            With over 4 years of expertise, Simple Abacus is a trusted leader in Abacus and Vedic Maths education,
            empowering more than 1,000 students and teachers. We offer a comprehensive suite of services-including
            student courses, school-integrated programs, certified teacher training, and
            premium worksheet subscriptions-delivering complete, end-to-end solutions for both learning and teaching.
          </p>
          <p className="mt-3 text-sm font-semibold text-white bg-gradient-to-r from-white/10 via-white/5 to-white/10 border border-white/20 px-4 py-3 rounded-2xl shadow-[0_16px_40px_rgba(0,0,0,0.12)] backdrop-blur-sm">
            Simple Abacus is a Division of Mangalvarsha Education Pvt. Ltd.
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
                <Mail className="h-4 w-4" />
                <span>simpleabacuspune@gmail.com</span>
              </div>
              <div className="flex items-center gap-2">
                <MapPin className="h-4 w-4" />
                <span>
                  Kunjir Public School, Manjari Budruk, Pune, Maharashtra 412307
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
        Copyrights {new Date().getFullYear()}. All Rights Reserved By Simple Abacus And Developed By{" "}
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
