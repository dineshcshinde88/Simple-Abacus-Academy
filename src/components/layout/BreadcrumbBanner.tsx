import { useMemo } from "react";
import { useLocation } from "react-router-dom";
import breadcrumbImage from "@/assets/breadcrubimage.png";
import teacherDashboardImage from "@/assets/teacher dashboard page.png";
import { blogPosts } from "@/data/blogPosts";

const titleMap: Record<string, string> = {
  "/about": "About Us",
  "/programs": "Programs",
  "/online-abacus-classes": "Online Abacus Classes",
  "/teacher-training": "Teacher Training",
  "/vedic-maths-classes": "Vedic Maths Classes",
  "/teachers": "Teachers",
  "/franchise": "Franchise",
  "/worksheets-subscription": "Worksheets Subscription",
  "/abacus-worksheet-subscription": "Abacus Worksheet Subscription",
  "/vedic-maths-worksheet-subscription": "Vedic Maths Worksheet Subscription",
  "/worksheet-generator": "Worksheet Generator",
  "/worksheet-dashboard": "Worksheet Dashboard",
  "/why-abacus": "Why Abacus",
  "/shop": "Pricing",
  "/contact": "Contact Us",
  "/book-demo": "Free Demo",
  "/blogs": "Blogs",
  "/digital-frame": "Digital Frame",
  "/instructor-login": "Instructor Login",
  "/instructor-registration": "Instructor Registration",
  "/register-tutor": "Tutor Registration",
  "/student-login": "Student Login",
  "/student-registration": "Student Registration",
  "/privacy-policy": "Privacy Policy",
  "/terms-and-conditions": "Terms and Conditions",
  "/refund-cancellation-policy": "Refund and Cancellation Policy",
  "/testimonials": "Testimonials",
  "/student/dashboard": "Student Dashboard",
  "/teacher-dashboard": "Teacher Dashboard",
};

const getTitleFromPath = (pathname: string) => {
  if (pathname.startsWith("/blogs/")) {
    const slug = pathname.replace("/blogs/", "");
    return blogPosts.find((post) => post.slug === slug)?.title ?? "Blog Details";
  }
  return titleMap[pathname] ?? "Page";
};

const BreadcrumbBanner = () => {
  const { pathname } = useLocation();
  const title = useMemo(() => getTitleFromPath(pathname), [pathname]);

  if (
    pathname === "/" ||
    pathname.startsWith("/student/") ||
    pathname.startsWith("/tutor/") ||
    pathname === "/teacher-dashboard" ||
    pathname.startsWith("/teacher-dashboard/")
  ) {
    return null;
  }

  const offsetClass = pathname === "/teacher-dashboard" ? "mt-0 md:mt-0" : "mt-24 md:mt-32";
  const isTeacherDashboard = pathname === "/teacher-dashboard";
  const bannerImage = pathname === "/teacher-dashboard" ? teacherDashboardImage : breadcrumbImage;

  return (
    <section
      className={`relative overflow-hidden min-h-[220px] md:min-h-[260px] ${offsetClass} bg-white -mb-16`}
    >
      <div className="absolute inset-0">
        <img
          src={bannerImage}
          alt="Breadcrumb"
          className="h-full w-full object-cover object-center"
          loading="lazy"
        />
      </div>
      <div className="relative container mx-auto px-4 text-primary-foreground text-center min-h-[220px] md:min-h-[260px] flex flex-col items-center justify-center">
        <h1 className={`${isTeacherDashboard ? "text-xl md:text-2xl" : "text-2xl md:text-3xl"} font-heading font-bold`}>{title}</h1>
        <p className={`${isTeacherDashboard ? "text-xs md:text-sm" : "text-sm"} mt-1 text-primary-foreground/80`}>Home / {title}</p>
      </div>
    </section>
  );
};

export default BreadcrumbBanner;
