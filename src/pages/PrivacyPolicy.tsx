import Navbar from "@/components/layout/Navbar";
import Footer from "@/components/layout/Footer";

const PrivacyPolicy = () => {
  return (
    <div className="min-h-screen bg-background">
      <Navbar />
      <main className="pt-16">
        <section className="py-16">
          <div className="container mx-auto px-4">
            <div className="mx-auto max-w-4xl rounded-2xl border border-border bg-white p-6 shadow-card md:p-10">
              <h1 className="text-3xl font-heading font-bold text-foreground">Privacy Policy</h1>
              <p className="mt-2 text-sm text-muted-foreground">Simple Abacus</p>
              <p className="mt-1 text-sm text-muted-foreground">Effective Date: April 23, 2026</p>

              <div className="mt-8 space-y-6 text-sm leading-7 text-muted-foreground">
                <p>
                  Simple Abacus ("we", "our", "us") respects your privacy. This Privacy Policy explains how we
                  collect, use, store, and protect personal information when parents and students use our online and
                  offline abacus training services.
                </p>

                <div>
                  <h2 className="text-lg font-semibold text-foreground">1. Information We Collect</h2>
                  <ul className="mt-2 list-disc space-y-1 pl-6">
                    <li>Parent or guardian name</li>
                    <li>Student name and age/class details</li>
                    <li>Mobile number</li>
                    <li>Email address</li>
                    <li>Address (if required for offline classes)</li>
                    <li>Payment details through secure third-party gateways</li>
                    <li>Attendance, class performance, and communication records</li>
                    <li>Basic browser/device usage data (if applicable)</li>
                  </ul>
                </div>

                <div>
                  <h2 className="text-lg font-semibold text-foreground">2. How We Use Your Information</h2>
                  <ul className="mt-2 list-disc space-y-1 pl-6">
                    <li>Student enrollment and class administration</li>
                    <li>Communication for schedules, updates, and support</li>
                    <li>Attendance tracking and progress monitoring</li>
                    <li>Fee collection and payment record management</li>
                    <li>Important notices and service communication</li>
                    <li>Service quality improvement and operational analysis</li>
                    <li>Compliance with legal requirements</li>
                  </ul>
                </div>

                <div>
                  <h2 className="text-lg font-semibold text-foreground">3. Children&apos;s Data</h2>
                  <p className="mt-2">
                    Our services are for students (kids). Registration and payment must be completed by parents or legal
                    guardians. By enrolling a student, the parent/guardian confirms consent for use of relevant student data
                    for educational and administrative purposes.
                  </p>
                </div>

                <div>
                  <h2 className="text-lg font-semibold text-foreground">4. Third-Party Services and Payments</h2>
                  <p className="mt-2">
                    We may use trusted third-party providers such as Razorpay for payment processing. We do not store full
                    card or banking credentials on our own servers.
                  </p>
                </div>

                <div>
                  <h2 className="text-lg font-semibold text-foreground">5. Cookies</h2>
                  <p className="mt-2">
                    Our website may use cookies and similar technologies to improve user experience, understand traffic
                    patterns, and remember preferences. You can disable cookies in your browser settings if required.
                  </p>
                </div>

                <div>
                  <h2 className="text-lg font-semibold text-foreground">6. Data Protection and Security</h2>
                  <p className="mt-2">
                    We apply reasonable technical and organizational safeguards to protect personal information against
                    unauthorized access, loss, misuse, or disclosure. Access is limited to authorized staff.
                  </p>
                </div>

                <div>
                  <h2 className="text-lg font-semibold text-foreground">7. Data Sharing</h2>
                  <p className="mt-2">
                    We do not sell personal information. Limited data may be shared only with required service providers,
                    payment partners, or government/legal authorities when legally required.
                  </p>
                </div>

                <div>
                  <h2 className="text-lg font-semibold text-foreground">8. Data Retention</h2>
                  <p className="mt-2">
                    We retain personal information only for as long as required for educational operations, legal compliance,
                    and record-keeping.
                  </p>
                </div>

                <div>
                  <h2 className="text-lg font-semibold text-foreground">9. Contact Us</h2>
                  <p className="mt-2">For privacy-related requests, contact us:</p>
                  <p className="mt-2">
                    <span className="font-medium text-foreground">Simple Abacus</span>
                    <br />
                    Phone: +91 89991 64139
                    <br />
                    Email: simpleabacuspune@gmail.com
                    <br />
                    Address: Kunjir Public School, Manjari Budruk, Pune, Maharashtra 412307
                  </p>
                </div>
              </div>
            </div>
          </div>
        </section>
      </main>
      <Footer />
    </div>
  );
};

export default PrivacyPolicy;
