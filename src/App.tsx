import { Toaster } from "@/components/ui/toaster";
import { Toaster as Sonner } from "@/components/ui/sonner";
import { TooltipProvider } from "@/components/ui/tooltip";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { BrowserRouter, Routes, Route, Navigate } from "react-router-dom";
import Index from "./pages/Index";
import About from "./pages/About";
import Programs from "./pages/Programs";
import OnlineAbacusClasses from "./pages/OnlineAbacusClasses";
import TeacherTraining from "./pages/TeacherTraining";
import VedicMathsClasses from "./pages/VedicMathsClasses";
import Franchise from "./pages/Franchise";
import WorksheetsSubscription from "./pages/WorksheetsSubscription";
import AbacusWorksheetSubscription from "./pages/AbacusWorksheetSubscription";
import VedicMathsWorksheetSubscription from "./pages/VedicMathsWorksheetSubscription";
import WorksheetGenerator from "./pages/WorksheetGenerator";
import WorksheetDashboard from "./pages/WorksheetDashboard";
import RequireAuth from "./components/auth/RequireAuth";
import WhyAbacus from "./pages/WhyAbacus";
import Shop from "./pages/Shop";
import Contact from "./pages/Contact";
import NotFound from "./pages/NotFound";
import AIChatbot from "./components/chat/AIChatbot";
import { AuthProvider } from "./context/AuthContext";
import { TrainingAuthProvider } from "./context/TrainingAuthContext";
import StudentDashboard from "./pages/StudentDashboard";
import TeacherDashboard from "./pages/TeacherDashboard";
import StudentBatches from "./pages/student/StudentBatches";
import StudentWorksheets from "./pages/student/StudentWorksheets";
import StudentVideos from "./pages/student/StudentVideos";
import StudentCourses from "./pages/student/StudentCourses";
import StudentProfile from "./pages/student/StudentProfile";
import StudentOrders from "./pages/student/StudentOrders";
import StudentChangePassword from "./pages/student/StudentChangePassword";
import StudentCertificates from "./pages/student/StudentCertificates";
import RequireRole from "./components/auth/RequireRole";
import ProtectedRoute from "./components/auth/ProtectedRoute";
import ScrollToTop from "./components/layout/ScrollToTop";
import BookDemo from "./pages/BookDemo";
import Blogs from "./pages/Blogs";
import BlogDetail from "./pages/BlogDetail";
import DigitalFrame from "./pages/DigitalFrame";
import InstructorLogin from "./pages/InstructorLogin";
import InstructorRegistration from "./pages/InstructorRegistration";
import InstructorResetPassword from "./pages/InstructorResetPassword";
import StudentLogin from "./pages/StudentLogin";
import StudentRegistration from "./pages/StudentRegistration";
import StudentResetPassword from "./pages/StudentResetPassword";
import Testimonials from "./pages/Testimonials";
import Teachers from "./pages/Teachers";
import BreadcrumbBanner from "./components/layout/BreadcrumbBanner";
import PrivacyPolicy from "./pages/PrivacyPolicy";
import TermsAndConditions from "./pages/TermsAndConditions";
import RefundCancellationPolicy from "./pages/RefundCancellationPolicy";
import TrainingLogin from "./pages/training/TrainingLogin";
import TrainingRegister from "./pages/training/TrainingRegister";
import TrainingTeacherDashboard from "./pages/training/TeacherDashboard";
import TrainingAdminDashboard from "./pages/training/AdminDashboard";
import TrainingPaymentGateway from "./pages/training/TrainingPaymentGateway";
import TrainingProtectedRoute from "./components/training/TrainingProtectedRoute";

const queryClient = new QueryClient();

const App = () => (
  <QueryClientProvider client={queryClient}>
    <AuthProvider>
      <TrainingAuthProvider>
        <TooltipProvider>
          <Toaster />
          <Sonner />
          <BrowserRouter>
            <ScrollToTop />
            <BreadcrumbBanner />
            <Routes>
            <Route path="/" element={<Index />} />
            <Route path="/about" element={<About />} />
            <Route path="/programs" element={<Programs />} />
            <Route path="/online-abacus-classes" element={<OnlineAbacusClasses />} />
            <Route path="/teacher-training" element={<TeacherTraining />} />
            <Route path="/vedic-maths-classes" element={<VedicMathsClasses />} />
            <Route path="/franchise" element={<Franchise />} />
            <Route path="/worksheets-subscription" element={<WorksheetsSubscription />} />
            <Route path="/abacus-worksheet-subscription" element={<AbacusWorksheetSubscription />} />
            <Route path="/vedic-maths-worksheet-subscription" element={<VedicMathsWorksheetSubscription />} />
            <Route path="/worksheet-generator" element={<WorksheetGenerator />} />
            <Route
              path="/worksheet-dashboard"
              element={(
                <RequireAuth>
                  <WorksheetDashboard />
                </RequireAuth>
              )}
            />
            <Route path="/why-abacus" element={<WhyAbacus />} />
            <Route path="/blogs" element={<Blogs />} />
            <Route path="/blogs/:slug" element={<BlogDetail />} />
            <Route path="/digital-frame" element={<DigitalFrame />} />
            <Route path="/instructor-login" element={<InstructorLogin />} />
            <Route path="/instructor-registration" element={<InstructorRegistration />} />
            <Route path="/register-tutor" element={<InstructorRegistration />} />
            <Route path="/instructor-reset-password" element={<InstructorResetPassword />} />
            <Route path="/student-login" element={<StudentLogin />} />
            <Route path="/student-registration" element={<StudentRegistration />} />
            <Route path="/student-reset-password" element={<StudentResetPassword />} />
            <Route path="/privacy-policy" element={<PrivacyPolicy />} />
            <Route path="/terms-and-conditions" element={<TermsAndConditions />} />
            <Route path="/refund-cancellation-policy" element={<RefundCancellationPolicy />} />
            <Route path="/testimonials" element={<Testimonials />} />
            <Route path="/teachers" element={<Teachers />} />
            <Route path="/shop" element={<Shop />} />
            <Route path="/contact" element={<Contact />} />
            <Route path="/book-demo" element={<BookDemo />} />
            <Route
              path="/student/dashboard"
              element={(
                <ProtectedRoute role="student">
                  <StudentDashboard />
                </ProtectedRoute>
              )}
            />
            <Route path="/student-dashboard" element={<Navigate to="/student/dashboard" replace />} />
            <Route
              path="/student/batches"
              element={(
                <ProtectedRoute role="student">
                  <StudentBatches />
                </ProtectedRoute>
              )}
            />
            <Route
              path="/student/worksheets"
              element={(
                <ProtectedRoute role="student">
                  <StudentWorksheets />
                </ProtectedRoute>
              )}
            />
            <Route
              path="/student/worksheets/:topicId/:view"
              element={(
                <ProtectedRoute role="student">
                  <StudentWorksheets />
                </ProtectedRoute>
              )}
            />
            <Route
              path="/student/videos"
              element={(
                <ProtectedRoute role="student">
                  <StudentVideos />
                </ProtectedRoute>
              )}
            />
            <Route
              path="/student/courses"
              element={(
                <ProtectedRoute role="student">
                  <StudentCourses />
                </ProtectedRoute>
              )}
            />
            <Route
              path="/student/profile"
              element={(
                <ProtectedRoute role="student">
                  <StudentProfile />
                </ProtectedRoute>
              )}
            />
            <Route
              path="/student/orders"
              element={(
                <ProtectedRoute role="student">
                  <StudentOrders />
                </ProtectedRoute>
              )}
            />
            <Route
              path="/student/change-password"
              element={(
                <ProtectedRoute role="student">
                  <StudentChangePassword />
                </ProtectedRoute>
              )}
            />
            <Route
              path="/student/certificates"
              element={(
                <ProtectedRoute role="student">
                  <StudentCertificates />
                </ProtectedRoute>
              )}
            />
            <Route
              path="/teacher-dashboard"
              element={(
                <RequireRole role="tutor">
                  <TeacherDashboard />
                </RequireRole>
              )}
            />
            <Route
              path="/teacher-dashboard/payment-gateway"
              element={(
                <RequireRole role="tutor">
                  <TrainingPaymentGateway />
                </RequireRole>
              )}
            />
            <Route path="/training/login" element={<TrainingLogin />} />
            <Route path="/training/register" element={<TrainingRegister />} />
            <Route path="/admin/login.php" element={<TrainingLogin />} />
            <Route path="/admin/dashboard.php" element={<Navigate to="/training/admin" replace />} />
            <Route path="/admin" element={<Navigate to="/training/admin" replace />} />
            <Route
              path="/training/dashboard"
              element={(
                <TrainingProtectedRoute role="teacher">
                  <TrainingTeacherDashboard />
                </TrainingProtectedRoute>
              )}
            />
            <Route
              path="/training/admin"
              element={(
                <TrainingProtectedRoute role="admin">
                  <TrainingAdminDashboard />
                </TrainingProtectedRoute>
              )}
            />
            <Route
              path="/training/payment-gateway"
              element={(
                <TrainingProtectedRoute role="teacher">
                  <TrainingPaymentGateway />
                </TrainingProtectedRoute>
              )}
            />
            <Route path="*" element={<NotFound />} />
          </Routes>
          <AIChatbot />
          </BrowserRouter>
        </TooltipProvider>
      </TrainingAuthProvider>
    </AuthProvider>
  </QueryClientProvider>
);

export default App;
