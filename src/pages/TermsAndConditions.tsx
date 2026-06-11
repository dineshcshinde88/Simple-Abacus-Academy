import Navbar from "@/components/layout/Navbar";
import Footer from "@/components/layout/Footer";

const TermsAndConditions = () => {
  return (
    <div className="min-h-screen bg-background">
      <Navbar />
      <main className="pt-16">
        <section className="py-16">
          <div className="container mx-auto px-4">
            <div className="mx-auto max-w-4xl rounded-2xl border border-border bg-white p-6 shadow-card md:p-10">
              <h1 className="text-3xl font-heading font-bold text-foreground">Terms &amp; Conditions</h1>
              <p className="mt-2 text-sm text-muted-foreground">Simple Abacus</p>
              <p className="mt-1 text-sm text-muted-foreground">Effective Date: April 23, 2026</p>

              <div className="mt-8 space-y-6 text-sm leading-7 text-muted-foreground">
                <p>
                  These Terms &amp; Conditions govern the use of services provided by Simple Abacus, including
                  online and offline abacus training classes for students.
                </p>

                <div>
                  <h2 className="text-lg font-semibold text-foreground">1. Use of Services and Enrollment</h2>
                  <ul className="mt-2 list-disc space-y-1 pl-6">
                    <li>Enrollment is confirmed only after successful registration and fee payment.</li>
                    <li>Class schedules, batch timings, and faculty may be updated as required.</li>
                    <li>Students must follow class rules, attendance requirements, and learning guidelines.</li>
                  </ul>
                </div>

                <div>
                  <h2 className="text-lg font-semibold text-foreground">2. User Responsibilities</h2>
                  <ul className="mt-2 list-disc space-y-1 pl-6">
                    <li>Parents/guardians must provide accurate and complete registration details.</li>
                    <li>Students are expected to maintain respectful behavior in class.</li>
                    <li>
                      For online sessions, parents are responsible for student device readiness and internet availability.
                    </li>
                    <li>Any misuse or disruptive conduct may lead to suspension of services.</li>
                  </ul>
                </div>

                <div>
                  <h2 className="text-lg font-semibold text-foreground">3. Payment Terms</h2>
                  <ul className="mt-2 list-disc space-y-1 pl-6">
                    <li>Fees are charged as per selected course plan or monthly subscription model.</li>
                    <li>Fees must be paid on or before due dates communicated by Simple Abacus.</li>
                    <li>Delayed or unpaid fees may result in temporary class access restrictions.</li>
                  </ul>
                </div>

                <div>
                  <h2 className="text-lg font-semibold text-foreground">4. Intellectual Property</h2>
                  <p className="mt-2">
                    All course materials including worksheets, videos, lesson plans, classroom content, and digital tools
                    are the intellectual property of Simple Abacus. Unauthorized copying, distribution, recording,
                    resale, or public sharing is prohibited.
                  </p>
                </div>

                <div>
                  <h2 className="text-lg font-semibold text-foreground">5. Limitation of Liability</h2>
                  <p className="mt-2">
                    While we strive to provide quality educational services, we are not liable for indirect loss, technical
                    interruptions, internet-related disruptions, or outcomes dependent on student practice and participation.
                  </p>
                </div>

                <div>
                  <h2 className="text-lg font-semibold text-foreground">6. Modification of Terms</h2>
                  <p className="mt-2">
                    Simple Abacus may update these Terms &amp; Conditions from time to time. Continued use of services
                    after updates implies acceptance of revised terms.
                  </p>
                </div>

                <div>
                  <h2 className="text-lg font-semibold text-foreground">7. Contact Us</h2>
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

export default TermsAndConditions;
