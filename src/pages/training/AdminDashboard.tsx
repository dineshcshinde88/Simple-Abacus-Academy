import { useEffect, useState } from "react";
import Navbar from "@/components/layout/Navbar";
import Footer from "@/components/layout/Footer";
import { Button } from "@/components/ui/button";
import { useTrainingAuth } from "@/context/TrainingAuthContext";
import { adminApproveTeacher, adminListStudents, adminListTeachers } from "@/services/trainingApi";

const AdminDashboard = () => {
  const { token, logout } = useTrainingAuth();
  const [teachers, setTeachers] = useState<any[]>([]);
  const [students, setStudents] = useState<any[]>([]);

  const load = async () => {
    if (!token) return;
    const t = await adminListTeachers(token);
    const s = await adminListStudents(token);
    setTeachers(t.teachers || []);
    setStudents(s.students || []);
  };

  useEffect(() => {
    void load();
  }, [token]);

  const handleApprove = async (id: string) => {
    if (!token) return;
    await adminApproveTeacher(token, id);
    await load();
  };

  return (
    <div className="min-h-screen bg-background">
      <Navbar />
      <main className="pt-16">
        <section className="py-10">
          <div className="container mx-auto px-4">
            <div className="flex items-center justify-between gap-4">
              <h1 className="text-3xl font-heading font-bold text-foreground">Admin Dashboard</h1>
              <Button variant="outline" onClick={logout}>Logout</Button>
            </div>

            <div className="mt-8 grid gap-6 lg:grid-cols-2">
              <div className="rounded-2xl border border-border bg-white p-6 shadow-card">
                <h2 className="text-xl font-semibold">Teachers</h2>
                <ul className="mt-4 space-y-3 text-sm">
                  {teachers.map((t) => (
                    <li key={t._id} className="flex items-center justify-between">
                      <div>
                        <div className="font-semibold">{t.name}</div>
                        <div className="text-muted-foreground">{t.email}</div>
                      </div>
                      {t.approved ? (
                        <span className="text-emerald-600 font-semibold">Approved</span>
                      ) : (
                        <Button size="sm" onClick={() => handleApprove(t._id)}>Approve</Button>
                      )}
                    </li>
                  ))}
                  {teachers.length === 0 && <li className="text-muted-foreground">No teachers yet.</li>}
                </ul>
              </div>

              <div className="rounded-2xl border border-border bg-white p-6 shadow-card">
                <h2 className="text-xl font-semibold">Students</h2>
                <ul className="mt-4 space-y-3 text-sm">
                  {students.map((s) => (
                    <li key={s._id} className="flex items-center justify-between">
                      <div>
                        <div className="font-semibold">{s.name}</div>
                        <div className="text-muted-foreground">Level: {s.level}</div>
                      </div>
                      <span className="text-muted-foreground">{s.progressPercent || 0}%</span>
                    </li>
                  ))}
                  {students.length === 0 && <li className="text-muted-foreground">No students yet.</li>}
                </ul>
              </div>
            </div>
          </div>
        </section>
      </main>
      <Footer />
    </div>
  );
};

export default AdminDashboard;
