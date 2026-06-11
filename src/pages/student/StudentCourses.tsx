import { useEffect, useState } from "react";
import { useNavigate } from "react-router-dom";
import { BookOpen, CalendarDays } from "lucide-react";
import StudentLayout from "@/layouts/StudentLayout";
import { Button } from "@/components/ui/button";
import { useToast } from "@/hooks/use-toast";
import { fetchStudentCourses, StudentCourseData } from "@/services/studentApi";

const TOKEN_KEY = "abacus_auth_token";

const formatDate = (value?: string | null) => {
  if (!value) return "-";
  const d = new Date(value);
  if (Number.isNaN(d.getTime())) return value;
  return d.toLocaleDateString();
};

const StudentCourses = () => {
  const navigate = useNavigate();
  const { toast } = useToast();
  const [courses, setCourses] = useState<StudentCourseData[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const token = localStorage.getItem(TOKEN_KEY);
    if (!token) {
      navigate("/student-login", { replace: true });
      return;
    }

    const loadCourses = async () => {
      try {
        const response = await fetchStudentCourses(token);
        setCourses(response.courses || []);
      } catch (error) {
        toast({
          title: "Unable to load courses",
          description: error instanceof Error ? error.message : "Please try again.",
        });
      } finally {
        setLoading(false);
      }
    };

    void loadCourses();
  }, [navigate, toast]);

  return (
    <StudentLayout
      header={(
        <div>
          <h1 className="text-2xl md:text-3xl font-heading font-bold text-slate-900">Allocated Courses</h1>
          <p className="text-sm text-slate-500 mt-1">Your assigned courses and active level access</p>
        </div>
      )}
    >
      {loading ? (
        <div className="bg-white rounded-2xl shadow-card p-6 text-slate-600">Loading courses...</div>
      ) : courses.length === 0 ? (
        <div className="bg-white rounded-2xl shadow-card p-8 text-center">
          <div className="mx-auto h-12 w-12 rounded-full bg-slate-100 flex items-center justify-center text-slate-500">
            <BookOpen size={22} />
          </div>
          <h2 className="mt-4 text-lg font-heading font-bold text-slate-900">No course assigned yet</h2>
          <p className="mt-2 text-sm text-slate-600">Purchase a level plan to unlock your worksheets.</p>
          <Button className="mt-5 bg-[#5b21b6] hover:bg-[#4c1d95]" onClick={() => navigate("/student/shop")}>
            Go to Shop
          </Button>
        </div>
      ) : (
        <div className="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
          {courses.map((course) => (
            <div key={course.id} className="bg-white rounded-2xl shadow-card border border-slate-100 p-5">
              <div className="flex items-start gap-3">
                <div className="h-11 w-11 rounded-xl bg-[#5b21b6]/10 text-[#5b21b6] flex items-center justify-center">
                  <BookOpen size={22} />
                </div>
                <div>
                  <p className="text-xs uppercase tracking-wide text-slate-500">{course.courseName}</p>
                  <h2 className="mt-1 text-lg font-heading font-bold text-slate-900">
                    {course.levelName || course.planName}
                  </h2>
                </div>
              </div>

              <div className="mt-5 space-y-3 text-sm">
                <div className="flex items-center justify-between gap-3">
                  <span className="text-slate-500">Plan</span>
                  <span className="font-semibold text-slate-900 text-right">{course.planName}</span>
                </div>
                <div className="flex items-center justify-between gap-3">
                  <span className="text-slate-500">Payment</span>
                  <span className="font-semibold text-emerald-600">
                    {course.currency} {Number(course.amount || 0).toFixed(2)}
                  </span>
                </div>
                <div className="flex items-center justify-between gap-3">
                  <span className="text-slate-500 flex items-center gap-1">
                    <CalendarDays size={15} /> Access
                  </span>
                  <span className="font-semibold text-slate-900 text-right">
                    {formatDate(course.accessStart)} - {formatDate(course.accessEnd)}
                  </span>
                </div>
              </div>

              <div className="mt-5">
                <Button variant="outline" onClick={() => navigate("/student/worksheets")}>
                  Worksheets
                </Button>
              </div>
            </div>
          ))}
        </div>
      )}
    </StudentLayout>
  );
};

export default StudentCourses;
