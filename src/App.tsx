import { Toaster } from "@/components/ui/toaster";
import { Toaster as Sonner } from "@/components/ui/sonner";
import { TooltipProvider } from "@/components/ui/tooltip";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { BrowserRouter, Routes, Route } from "react-router-dom";
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
import StudentLogin from "./pages/StudentLogin";
import StudentRegistration from "./pages/StudentRegistration";
import Testimonials from "./pages/Testimonials";
import BreadcrumbBanner from "./components/layout/BreadcrumbBanner";

const queryClient = new QueryClient();

const App = () => (
  <QueryClientProvider client={queryClient}>
    <AuthProvider>
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
            <Route path="/student-login" element={<StudentLogin />} />
            <Route path="/student-registration" element={<StudentRegistration />} />
            <Route path="/testimonials" element={<Testimonials />} />
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
              path="/tutor/dashboard"
              element={(
                <RequireRole role="tutor">
                  <TeacherDashboard />
                </RequireRole>
              )}
            />
            <Route path="*" element={<NotFound />} />
          </Routes>
        </BrowserRouter>
        <AIChatbot />
      </TooltipProvider>
    </AuthProvider>
  </QueryClientProvider>
);

export default App;
