import { useEffect, useRef, useState } from "react";
import { Link } from "react-router-dom";
import { Button } from "@/components/ui/button";
import { Facebook, Instagram, Mail, Menu, Phone, X, Youtube } from "lucide-react";
import { motion, AnimatePresence } from "framer-motion";
import LoginDialog from "@/components/auth/LoginDialog";
import { useAuth } from "@/context/AuthContext";

const navLinks = [
  { label: "Home", to: "/" },
  {
    label: "Courses",
    children: [
      { label: "Online Abacus Classes", to: "/online-abacus-classes" },
      { label: "Teacher Training", to: "/teacher-training" },
      { label: "Vedic Maths Classes", to: "/vedic-maths-classes" },
    ],
  },
  { label: "Franchise", to: "/franchise" },
  {
    label: "Worksheets",
    children: [
      { label: "Worksheets Subscription", to: "/worksheets-subscription" },
      { label: "Abacus Worksheet Subscription", to: "/abacus-worksheet-subscription" },
      { label: "Vedic Maths Worksheet Subscription", to: "/vedic-maths-worksheet-subscription" },
      { label: "Worksheet Generator", to: "/worksheet-generator" },
    ],
  },
  { label: "Blogs", to: "/blogs" },
  { label: "Digital Frame", to: "/digital-frame" },
  {
    label: "Login",
    children: [
      { label: "Franchise Registration", to: "/franchise" },
      { label: "Instructor Login", to: "/instructor-login" },
      { label: "Instructor Registration", to: "/instructor-registration" },
      { label: "Student Login", to: "/student-login" },
      { label: "Student Registration", to: "/student-registration" },
    ],
  },
];

const Navbar = () => {
  const { user, logout } = useAuth();
  const [isOpen, setIsOpen] = useState(false);
  const [loginOpen, setLoginOpen] = useState(false);
  const [isScrolled, setIsScrolled] = useState(false);
  const [openMenu, setOpenMenu] = useState<string | null>(null);
  const navRef = useRef<HTMLDivElement>(null);
  const closeTimeoutRef = useRef<ReturnType<typeof setTimeout> | null>(null);

  useEffect(() => {
    const onScroll = () => setIsScrolled(window.scrollY > 40);
    onScroll();
    window.addEventListener("scroll", onScroll, { passive: true });
    return () => window.removeEventListener("scroll", onScroll);
  }, []);

  useEffect(() => {
    const onClickOutside = (event: MouseEvent) => {
      if (!navRef.current || navRef.current.contains(event.target as Node)) return;
      setOpenMenu(null);
    };
    const onEscape = (event: KeyboardEvent) => {
      if (event.key === "Escape") setOpenMenu(null);
    };
    document.addEventListener("mousedown", onClickOutside);
    document.addEventListener("keydown", onEscape);
    return () => {
      document.removeEventListener("mousedown", onClickOutside);
      document.removeEventListener("keydown", onEscape);
    };
  }, []);

  useEffect(() => {
    return () => {
      if (closeTimeoutRef.current) clearTimeout(closeTimeoutRef.current);
    };
  }, []);

  const openDropdown = (label: string) => {
    if (closeTimeoutRef.current) {
      clearTimeout(closeTimeoutRef.current);
      closeTimeoutRef.current = null;
    }
    setOpenMenu(label);
  };

  const closeDropdownWithDelay = () => {
    if (closeTimeoutRef.current) clearTimeout(closeTimeoutRef.current);
    closeTimeoutRef.current = setTimeout(() => {
      setOpenMenu(null);
    }, 200);
  };

  return (
    <nav className="fixed top-0 left-0 right-0 z-50">
      {/* Top Bar */}
      <div
        className={`bg-[#4c1d95] text-white transition-all duration-300 ${
          isScrolled ? "h-0 opacity-0 overflow-hidden" : "opacity-100"
        }`}
      >
        <div className="container mx-auto px-4 py-2 md:py-0 md:h-16 flex flex-col md:flex-row md:items-center md:justify-between gap-2">
          <div className="flex flex-col md:flex-row md:items-center gap-2 md:gap-6 text-xs md:text-sm">
            <span className="flex items-center gap-2">
              <Phone className="w-4 h-4" />
              Free Call +91 7842 885 885
            </span>
            <span className="flex items-center gap-2">
              <Mail className="w-4 h-4" />
              info@abacustrainer.com
            </span>
          </div>
          <div className="hidden md:flex items-center gap-2">
            <a
              className="w-9 h-9 rounded-md bg-white text-black grid place-items-center"
              href="https://www.facebook.com/share/1ZvbKHAAch/"
              aria-label="Facebook"
              target="_blank"
              rel="noopener noreferrer"
            >
              <Facebook className="w-4 h-4" />
            </a>
            <a
              className="w-9 h-9 rounded-md bg-white text-black grid place-items-center"
              href="https://www.instagram.com/simple_abacus?utm_source=qr&igsh=cXpjc3dnazdmeHRo"
              aria-label="Instagram"
              target="_blank"
              rel="noopener noreferrer"
            >
              <Instagram className="w-4 h-4" />
            </a>
            <a
              className="w-9 h-9 rounded-md bg-white text-black grid place-items-center"
              href="https://youtube.com/@varshashindeofficial6475?si=7SDTtM2vysfCtT6I"
              aria-label="YouTube"
              target="_blank"
              rel="noopener noreferrer"
            >
              <Youtube className="w-4 h-4" />
            </a>
          </div>
        </div>
      </div>

      {/* Main Bar */}
      <div className="bg-white transition-all duration-300 relative z-20 overflow-visible">
        <div className="container mx-auto flex items-center justify-between px-4 h-16">
          <Link to="/" className="flex items-center">
            <img
              src="/abacus_logo.svg"
              alt="Simple Abacus Academy logo"
              className={`w-auto object-contain transition-all duration-300 ${
                isScrolled ? "h-10" : "h-12 md:h-14"
              }`}
            />
          </Link>

          {/* Desktop Nav */}
          <div
            ref={navRef}
            className={`hidden md:flex items-center gap-8 text-sm font-semibold text-[#0f5132] relative z-20 overflow-visible ${
              isScrolled
                ? ""
                : "bg-white px-10 py-4 rounded-md -mb-2"
            }`}
          >
            {navLinks.map((link) => {
              const hasChildren = !!link.children?.length;
              const isOpenMenu = openMenu === link.label;
              return (
                <div
                  key={link.label}
                  className="relative"
                  onMouseEnter={() => openDropdown(link.label)}
                  onMouseLeave={closeDropdownWithDelay}
                >
                  {hasChildren ? (
                    <button
                      type="button"
                      onClick={() => {
                        if (closeTimeoutRef.current) {
                          clearTimeout(closeTimeoutRef.current);
                          closeTimeoutRef.current = null;
                        }
                        setOpenMenu(isOpenMenu ? null : link.label);
                      }}
                      aria-expanded={isOpenMenu}
                      aria-haspopup="menu"
                      className="relative group transition-colors hover:text-[#0a3d25] pb-1"
                    >
                      {link.label}
                      <span
                        className={`absolute left-0 -bottom-2 h-0.5 w-6 bg-[#f97316] transition-opacity ${
                          isOpenMenu ? "opacity-100" : "opacity-0 group-hover:opacity-100"
                        }`}
                      />
                    </button>
                  ) : (
                    <Link
                      to={link.to || "#"}
                      className="relative group transition-colors hover:text-[#0a3d25]"
                      onClick={() => setOpenMenu(null)}
                    >
                      {link.label}
                      <span className="absolute left-0 -bottom-2 h-0.5 w-5 bg-[#f97316] opacity-0 transition-opacity group-hover:opacity-100" />
                    </Link>
                  )}

                  {hasChildren && isOpenMenu && (
                    <div
                      className="absolute left-0 top-full mt-0 w-64 bg-white border border-border shadow-sm z-50"
                      onMouseEnter={() => openDropdown(link.label)}
                      onMouseLeave={closeDropdownWithDelay}
                    >
                      <div className="h-1 bg-[#f97316]" />
                      <div className="py-2">
                        {link.children?.map((child, index) => {
                          const isLast = index === link.children.length - 1;
                          if (child.action === "login") {
                            return (
                              <button
                                key={child.label}
                                type="button"
                                onClick={() => {
                                  setLoginOpen(true);
                                  setOpenMenu(null);
                                }}
                                className={`w-full text-left px-4 py-2 text-sm text-slate-700 hover:text-[#0f5132] ${
                                  isLast ? "" : "border-b border-[#f97316]/40"
                                }`}
                              >
                                {child.label}
                              </button>
                            );
                          }
                          return (
                            <Link
                              key={child.label}
                              to={child.to || "#"}
                              className={`block px-4 py-2 text-sm text-slate-700 hover:text-[#0f5132] ${
                                isLast ? "" : "border-b border-[#f97316]/40"
                              }`}
                              onClick={() => setOpenMenu(null)}
                            >
                              {child.label}
                            </Link>
                          );
                        })}
                      </div>
                    </div>
                  )}
                </div>
              );
            })}
            {user ? (
              <Button variant="ghost" size="sm" asChild>
                <Link to={user.role === "teacher" ? "/teacher-dashboard" : "/student-dashboard"}>Dashboard</Link>
              </Button>
            ) : null}
          </div>

          {/* Desktop Right */}
          <div className="hidden md:flex items-center gap-3">
            {user ? (
              <Button variant="ghost" size="sm" onClick={logout}>
                Logout
              </Button>
            ) : null}
            <Button
              size="sm"
              className="bg-[#dc2626] hover:bg-[#b91c1c] text-white font-semibold rounded-none px-6 h-10"
              asChild
            >
              <Link to="/book-demo">Free Demo</Link>
            </Button>
          </div>

          {/* Mobile toggle */}
          <button className="md:hidden p-2" onClick={() => setIsOpen(!isOpen)}>
            {isOpen ? <X className="w-6 h-6" /> : <Menu className="w-6 h-6" />}
          </button>
        </div>
      </div>

      {/* Mobile Nav */}
      <AnimatePresence>
        {isOpen && (
          <motion.div
            initial={{ opacity: 0, height: 0 }}
            animate={{ opacity: 1, height: "auto" }}
            exit={{ opacity: 0, height: 0 }}
            className="md:hidden bg-white border-b border-border overflow-hidden"
          >
            <div className="flex flex-col p-4 gap-3">
              {navLinks.map((link) => (
                <div key={link.label} className="flex flex-col">
                  {link.to ? (
                    <Link
                      to={link.to}
                      onClick={() => setIsOpen(false)}
                      className="text-sm font-medium text-black hover:text-black/80 py-2"
                    >
                      {link.label}
                    </Link>
                  ) : (
                    <button
                      type="button"
                      onClick={() => setOpenMenu(openMenu === link.label ? null : link.label)}
                      className="text-left text-sm font-medium text-black hover:text-black/80 py-2"
                    >
                      {link.label}
                    </button>
                  )}
                  {link.children?.length && openMenu === link.label ? (
                    <div className="pl-4 space-y-1">
                      {link.children.map((child) => {
                        if (child.action === "login") {
                          return (
                            <button
                              key={child.label}
                              type="button"
                              className="w-full text-left text-sm text-slate-600 py-2 leading-snug whitespace-normal"
                              onClick={() => {
                                setLoginOpen(true);
                                setIsOpen(false);
                                setOpenMenu(null);
                              }}
                            >
                              {child.label}
                            </button>
                          );
                        }
                        return (
                          <Link
                            key={child.label}
                            to={child.to || "#"}
                            className="block text-left text-sm text-slate-600 py-2 leading-snug whitespace-normal"
                            onClick={() => {
                              setIsOpen(false);
                              setOpenMenu(null);
                            }}
                          >
                            {child.label}
                          </Link>
                        );
                      })}
                    </div>
                  ) : null}
                </div>
              ))}
              <div className="flex gap-3 pt-3 border-t border-border">
                {user ? (
                  <>
                    <Button
                      variant="outline"
                      size="sm"
                      className="flex-1"
                      asChild
                      onClick={() => setIsOpen(false)}
                    >
                      <Link to={user.role === "teacher" ? "/teacher-dashboard" : "/student-dashboard"}>
                        Dashboard
                      </Link>
                    </Button>
                    <Button
                      variant="outline"
                      size="sm"
                      className="flex-1"
                      onClick={() => {
                        logout();
                        setIsOpen(false);
                      }}
                    >
                      Logout
                    </Button>
                  </>
                ) : (
                  <Button
                    variant="outline"
                    size="sm"
                    className="flex-1"
                    onClick={() => {
                      setIsOpen(false);
                      setLoginOpen(true);
                    }}
                  >
                    Login
                  </Button>
                )}
                <Button size="sm" className="flex-1 gradient-accent text-accent-foreground font-semibold" asChild>
                  <Link to="/book-demo">Free Demo</Link>
                </Button>
              </div>
            </div>
          </motion.div>
        )}
      </AnimatePresence>
      <LoginDialog open={loginOpen} onOpenChange={setLoginOpen} />
    </nav>
  );
};

export default Navbar;
