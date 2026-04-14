import { useEffect, useMemo, useState } from "react";
import Navbar from "@/components/layout/Navbar";
import Footer from "@/components/layout/Footer";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { useTrainingAuth } from "@/context/TrainingAuthContext";
import { teacherAddStudent, teacherDashboard } from "@/services/trainingApi";
import { BarChart, Bar, XAxis, YAxis, Tooltip, ResponsiveContainer } from "recharts";

const TeacherDashboard = () => {
  const { token, user, logout } = useTrainingAuth();
  const [data, setData] = useState<any>(null);
  const [studentForm, setStudentForm] = useState({ name: "", age: "", parentContact: "", level: "Basic" });

  const load = async () => {
    if (!token) return;
    const res = await teacherDashboard(token);
    setData(res);
  };

  useEffect(() => {
    void load();
  }, [token]);

  const chartData = useMemo(() => {
    const students = data?.students || [];
    return students.map((s: any) => ({ name: s.name, progress: s.progressPercent || 0 }));
  }, [data]);

  const handleAddStudent = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!token) return;
    await teacherAddStudent(token, {
      name: studentForm.name,
      age: Number(studentForm.age),
      parentContact: studentForm.parentContact,
      level: studentForm.level,
    });
    setStudentForm({ name: "", age: "", parentContact: "", level: "Basic" });
    await load();
  };

  return (
    <div className="min-h-screen bg-background">
      <Navbar />
      <main className="pt-16">
        <section className="py-10">
          <div className="container mx-auto px-4">
            <div className="flex items-center justify-between gap-4 flex-wrap">
              <div>
                <h1 className="text-3xl font-heading font-bold text-foreground">Teacher Training Dashboard</h1>
                <p className="text-muted-foreground">Welcome, {user?.name}</p>
              </div>
              <Button variant="outline" onClick={logout}>Logout</Button>
            </div>

            <div className="mt-8 grid gap-6 lg:grid-cols-3">
              <div className="rounded-2xl border border-border bg-white p-5 shadow-card">
                <h3 className="font-semibold">Progress</h3>
                <p className="text-3xl font-bold mt-3">{data?.progress?.percent || 0}%</p>
                <p className="text-sm text-muted-foreground">
                  Completed modules: {data?.progress?.completedModules || 0}/{data?.progress?.totalModules || 0}
                </p>
              </div>
              <div className="rounded-2xl border border-border bg-white p-5 shadow-card">
                <h3 className="font-semibold">Students</h3>
                <p className="text-3xl font-bold mt-3">{data?.students?.length || 0}</p>
                <p className="text-sm text-muted-foreground">Assigned to you</p>
              </div>
              <div className="rounded-2xl border border-border bg-white p-5 shadow-card">
                <h3 className="font-semibold">Assignments</h3>
                <p className="text-3xl font-bold mt-3">{data?.assignments?.length || 0}</p>
                <p className="text-sm text-muted-foreground">Open assignments</p>
              </div>
            </div>

            <div className="mt-10 grid gap-6 lg:grid-cols-[1.2fr_0.8fr]">
              <div className="rounded-2xl border border-border bg-white p-6 shadow-card">
                <h2 className="text-xl font-semibold">Student Performance</h2>
                <div className="mt-4 h-64">
                  <ResponsiveContainer width="100%" height="100%">
                    <BarChart data={chartData}>
                      <XAxis dataKey="name" hide />
                      <YAxis />
                      <Tooltip />
                      <Bar dataKey="progress" fill="#f97316" radius={[6, 6, 0, 0]} />
                    </BarChart>
                  </ResponsiveContainer>
                </div>
              </div>

              <div className="rounded-2xl border border-border bg-white p-6 shadow-card">
                <h2 className="text-xl font-semibold">Add Student</h2>
                <form className="mt-4 space-y-3" onSubmit={handleAddStudent}>
                  <div className="space-y-1">
                    <Label>Name</Label>
                    <Input value={studentForm.name} onChange={(e) => setStudentForm({ ...studentForm, name: e.target.value })} required />
                  </div>
                  <div className="space-y-1">
                    <Label>Age</Label>
                    <Input value={studentForm.age} onChange={(e) => setStudentForm({ ...studentForm, age: e.target.value })} required />
                  </div>
                  <div className="space-y-1">
                    <Label>Parent Contact</Label>
                    <Input value={studentForm.parentContact} onChange={(e) => setStudentForm({ ...studentForm, parentContact: e.target.value })} required />
                  </div>
                  <div className="space-y-1">
                    <Label>Level</Label>
                    <Input value={studentForm.level} onChange={(e) => setStudentForm({ ...studentForm, level: e.target.value })} />
                  </div>
                  <Button type="submit" className="w-full">Add Student</Button>
                </form>
              </div>
            </div>

            <div className="mt-10 rounded-2xl border border-border bg-white p-6 shadow-card">
              <h2 className="text-xl font-semibold">Study Materials</h2>
              <ul className="mt-4 space-y-2 text-sm text-muted-foreground">
                {(data?.materials || []).map((m: any) => (
                  <li key={m._id} className="flex items-center justify-between">
                    <span>{m.title}</span>
                    <a className="text-primary font-semibold" href={m.fileUrl} target="_blank" rel="noreferrer">View</a>
                  </li>
                ))}
                {(!data?.materials || data.materials.length === 0) && <li>No materials uploaded yet.</li>}
              </ul>
            </div>
          </div>
        </section>
      </main>
      <Footer />
    </div>
  );
};

export default TeacherDashboard;
