import Navbar from "@/components/layout/Navbar";
import Footer from "@/components/layout/Footer";

const RefundCancellationPolicy = () => {
  return (
    <div className="min-h-screen bg-background">
      <Navbar />
      <main className="pt-16">
        <section className="py-16">
          <div className="container mx-auto px-4">
            <div className="mx-auto max-w-4xl rounded-2xl border border-border bg-white p-6 shadow-card md:p-10">
              <h1 className="text-3xl font-heading font-bold text-foreground">Refund &amp; Cancellation Policy</h1>
              <p className="mt-2 text-sm text-muted-foreground">Simple Abacus Academy</p>
              <p className="mt-1 text-sm text-muted-foreground">Effective Date: April 23, 2026</p>

              <div className="mt-8 space-y-6 text-sm leading-7 text-muted-foreground">
                <div>
                  <h2 className="text-lg font-semibold text-foreground">1. Refund Policy</h2>
                  <p className="mt-2">
                    Fees paid towards enrollment are generally non-refundable. Once a student is enrolled in a course or
                    batch, refund requests are normally not accepted.
                  </p>
                  <p className="mt-2">
                    In exceptional and genuine cases, management may consider a partial refund at its sole discretion.
                  </p>
                </div>

                <div>
                  <h2 className="text-lg font-semibold text-foreground">2. Cancellation by Parent/Student</h2>
                  <ul className="mt-2 list-disc space-y-1 pl-6">
                    <li>If a student discontinues classes, paid fees are not refundable.</li>
                    <li>Enrollment seats are non-transferable unless explicitly approved by the academy.</li>
                  </ul>
                </div>

                <div>
                  <h2 className="text-lg font-semibold text-foreground">3. Class Rescheduling</h2>
                  <ul className="mt-2 list-disc space-y-1 pl-6">
                    <li>Rescheduling requests should be made in advance.</li>
                    <li>Rescheduling depends on trainer and batch availability.</li>
                    <li>
                      For missed classes, makeup sessions or alternatives (if available) may be provided as per academy policy.
                    </li>
                  </ul>
                </div>

                <div>
                  <h2 className="text-lg font-semibold text-foreground">4. Cancellation by Academy</h2>
                  <p className="mt-2">
                    If a session is cancelled by the academy due to unavoidable reasons, a rescheduled class or appropriate
                    fee credit may be provided.
                  </p>
                </div>

                <div>
                  <h2 className="text-lg font-semibold text-foreground">5. Refund Processing Timeline</h2>
                  <p className="mt-2">
                    Any approved refund (if applicable) is generally processed within 7 to 10 working days to the original
                    payment method. Processing time may vary based on bank or payment gateway timelines.
                  </p>
                </div>

                <div>
                  <h2 className="text-lg font-semibold text-foreground">6. Contact for Refund/Cancellation Requests</h2>
                  <p className="mt-2">
                    <span className="font-medium text-foreground">Simple Abacus Academy</span>
                    <br />
                    Phone: +91 89991 64139
                    <br />
                    Email: simpleabacuspune@gmail.com
                    <br />
                    Address: Near Kunjir Public School, Manjri, Manjari Budruk, Pune, Maharashtra 412307
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

export default RefundCancellationPolicy;
