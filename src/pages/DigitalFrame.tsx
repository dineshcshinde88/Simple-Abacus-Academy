import VirtualAbacusTool from "@/components/abacus/VirtualAbacusTool";
import Footer from "@/components/layout/Footer";
import Navbar from "@/components/layout/Navbar";

const DigitalFrame = () => {
  return (
    <div className="min-h-screen bg-background">
      <Navbar />
      <main className="pt-16">
        <VirtualAbacusTool />
      </main>
      <Footer />
    </div>
  );
};

export default DigitalFrame;
