import Navbar from "@/components/layout/Navbar";
import Footer from "@/components/layout/Footer";
import HeroSection from "@/components/landing/HeroSection";
import VideoIntroSection from "@/components/landing/VideoIntroSection";
import WorksheetPracticeSection from "@/components/landing/WorksheetPracticeSection";
import ModuleSection from "@/components/landing/ModuleSection";
import OurCoursesSection from "@/components/landing/OurCoursesSection";
import AddOnServicesSection from "@/components/landing/AddOnServicesSection";
import BenefitsSection from "@/components/landing/BenefitsSection";
import TestimonialsSection from "@/components/landing/TestimonialsSection";
import FAQSection from "@/components/landing/FAQSection";
import CTASection from "@/components/landing/CTASection";

const Index = () => (
  <div className="min-h-screen">
    <Navbar />
    <main>
      <HeroSection />
      <VideoIntroSection />
      <WorksheetPracticeSection />
      <ModuleSection />
      <OurCoursesSection />
      <AddOnServicesSection />
      <BenefitsSection />
      <TestimonialsSection />
      <CTASection />
      <FAQSection />
    </main>
    <Footer />
  </div>
);

export default Index;
