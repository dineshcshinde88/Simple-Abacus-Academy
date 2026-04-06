import { useMemo } from "react";
import { useLocation } from "react-router-dom";
import breadcrumbImage from "@/assets/breadcrubimage.png";
import { blogPosts } from "@/data/blogPosts";

const titleMap: Record<string, string> = {
  "/about": "About Us",
  "/programs": "Programs",
  "/online-abacus-classes": "Online Abacus Classes",
  "/teacher-training": "Teacher Training",
  "/vedic-maths-classes": "Vedic Maths Classes",
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
  "/student-login": "Student Login",
  "/student-registration": "Student Registration",
  "/testimonials": "Testimonials",
  "/student-dashboard": "Student Dashboard",
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

  if (pathname === "/" || pathname.startsWith("/student/") || pathname.startsWith("/tutor/")) {
    return null;
  }

  return (
    <section className="relative overflow-hidden min-h-[220px] md:min-h-[260px] mt-24 md:mt-32 bg-white -mb-16">
      <div className="absolute inset-0">
        <img
          src={breadcrumbImage}
          alt="Breadcrumb"
          className="h-full w-full object-cover object-center"
          loading="lazy"
        />
      </div>
      <div className="relative container mx-auto px-4 text-primary-foreground text-center min-h-[220px] md:min-h-[260px] flex flex-col items-center justify-center">
        <h1 className="text-2xl md:text-3xl font-heading font-bold">{title}</h1>
        <p className="mt-1 text-sm text-primary-foreground/80">Home / {title}</p>
      </div>
    </section>
  );
};

export default BreadcrumbBanner;
