import { useEffect, useState } from "react";
import Navbar from "@/components/layout/Navbar";
import Footer from "@/components/layout/Footer";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { useTrainingAuth } from "@/context/TrainingAuthContext";
import { adminApproveTeacher, adminListRegisteredStudents, adminListStudents, adminListTeachers } from "@/services/trainingApi";
import {
  AdminPracticeOverview,
  getAdminPracticeOverview,
  importDefaultPracticeDocs,
  updatePracticeLevel,
  uploadPracticeDocx,
} from "@/services/practiceApi";

const AdminDashboard = () => {
  const { token, logout } = useTrainingAuth();
  const [teachers, setTeachers] = useState<any[]>([]);
  const [students, setStudents] = useState<any[]>([]);
  const [registeredStudents, setRegisteredStudents] = useState<any[]>([]);
  const [practice, setPractice] = useState<AdminPracticeOverview>({ levels: [], results: [] });
  const [busy, setBusy] = useState(false);


  const load = async () => {
    if (!token) return;
    const t = await adminListTeachers(token);
    const s = await adminListStudents(token);
    const registered = await adminListRegisteredStudents(token);
    const p = await getAdminPracticeOverview(token);
    setTeachers(t.teachers || []);
    setStudents(s.students || []);
    setRegisteredStudents(registered.students || []);
    setPractice(p);
  };

  useEffect(() => {
    void load();
  }, [token]);

  const handleApprove = async (id: string) => {
    if (!token) return;
    await adminApproveTeacher(token, id);
    await load();
  };


  const handleImportDefaults = async () => {
    if (!token) return;
    setBusy(true);
    try {
      await importDefaultPracticeDocs(token);
      await load();
    } finally {
      setBusy(false);
    }
  };

  const handleTimerChange = async (levelId: string, name: string, timerSeconds: number) => {
    if (!token) return;
    await updatePracticeLevel(token, levelId, { name, timerSeconds });
    await load();
  };

  const handleUpload = async (levelId: string, file?: File) => {
    if (!token || !file) return;
    setBusy(true);
    try {
      await uploadPracticeDocx(token, levelId, file);
      await load();
    } finally {
      setBusy(false);
    }
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
            <div className="mt-8 rounded-lg border border-border bg-white p-6 shadow-card">
              <div className="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div>
                  <h2 className="text-xl font-semibold">Practice Paper System</h2>
                  <p className="text-sm text-muted-foreground">Import DOCX papers, control timers, and review student results.</p>
                </div>
                <Button className="bg-orange-500 hover:bg-orange-600" onClick={handleImportDefaults} disabled={busy}>
                  Import Uploaded DOCX Files
                </Button>
              </div>

              <div className="mt-5 grid gap-4 lg:grid-cols-2">
                {practice.levels.map((level) => (
                  <div key={level.id} className="rounded-lg border border-border bg-slate-50 p-4">
                    <div className="flex items-start justify-between gap-4">
                      <div>
                        <h3 className="font-bold text-foreground">{level.name}</h3>
                        <p className="text-sm text-muted-foreground">
                          {level.paperCount} papers • {level.questionCount} questions
                        </p>
                      </div>
                      <span className="rounded-full bg-white px-3 py-1 text-xs font-semibold text-slate-700">
                        {Math.round(level.timerSeconds / 60)} min
                      </span>
                    </div>
                    <div className="mt-4 flex flex-col gap-3 sm:flex-row">
                      <Input
                        type="number"
                        min={30}
                        defaultValue={level.timerSeconds}
                        aria-label={`${level.name} timer seconds`}
                        onBlur={(event) => handleTimerChange(level.id, level.name, Number(event.currentTarget.value))}
                      />
                      <Input
                        type="file"
                        accept=".docx"
                        aria-label={`Upload DOCX for ${level.name}`}
                        onChange={(event) => handleUpload(level.id, event.currentTarget.files?.[0])}
                      />
                    </div>
                  </div>
                ))}
              </div>

              <div className="mt-6 overflow-x-auto">
                <table className="w-full min-w-[720px] text-left text-sm">
                  <thead className="text-xs uppercase text-muted-foreground">
                    <tr>
                      <th className="py-2">Student</th>
                      <th className="py-2">Level</th>
                      <th className="py-2">Paper</th>
                      <th className="py-2">Score</th>
                      <th className="py-2">Accuracy</th>
                      <th className="py-2">Time</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-border">
                    {practice.results.map((result) => (
                      <tr key={result.id}>
                        <td className="py-3">
                          <div className="font-semibold">{result.studentName}</div>
                          <div className="text-xs text-muted-foreground">{result.studentEmail}</div>
                        </td>
                        <td className="py-3">{result.levelName}</td>
                        <td className="py-3">{result.paperTitle}</td>
                        <td className="py-3 font-semibold">{result.score}/{result.totalQuestions}</td>
                        <td className="py-3">{result.accuracy}%</td>
                        <td className="py-3">{Math.round(result.timeTakenSeconds / 60)} min</td>
                      </tr>
                    ))}
                    {practice.results.length === 0 && (
                      <tr>
                        <td className="py-4 text-muted-foreground" colSpan={6}>No practice submissions yet.</td>
                      </tr>
                    )}
                  </tbody>
                </table>
              </div>
            </div>

            <div className="mt-8 grid gap-6 lg:grid-cols-2">
              <div className="rounded-2xl border border-border bg-white p-6 shadow-card">
                <h2 className="text-xl font-semibold">Teachers</h2>
                <ul className="mt-4 space-y-3 text-sm">
                  {teachers.map((t) => (
                    <li key={t.id} className="flex items-center justify-between">
                      <div>
                        <div className="font-semibold">{t.name}</div>
                        <div className="text-muted-foreground">{t.email}</div>
                      </div>
                      {t.approved ? (
                        <span className="text-emerald-600 font-semibold">Approved</span>
                      ) : (
                        <Button size="sm" onClick={() => handleApprove(t.id)}>Approve</Button>
                      )}
                    </li>
                  ))}
                  {teachers.length === 0 && <li className="text-muted-foreground">No teachers yet.</li>}
                </ul>
              </div>

              <div className="rounded-2xl border border-border bg-white p-6 shadow-card">
                <h2 className="text-xl font-semibold">Training Students</h2>
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

            <div className="mt-8 rounded-2xl border border-border bg-white p-6 shadow-card">
              <h2 className="text-xl font-semibold">Registered Students</h2>
              <p className="mt-1 text-sm text-muted-foreground">
                Students submitted from the website Student Registration form.
              </p>
              <div className="mt-5 overflow-x-auto">
                <table className="w-full min-w-[760px] text-left text-sm">
                  <thead className="text-xs uppercase text-muted-foreground">
                    <tr>
                      <th className="py-2">Student</th>
                      <th className="py-2">Course</th>
                      <th className="py-2">Mobile</th>
                      <th className="py-2">Gender</th>
                      <th className="py-2">Date of Birth</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-border">
                    {registeredStudents.map((student) => (
                      <tr key={student.id}>
                        <td className="py-3">
                          <div className="font-semibold">{student.user?.name || "-"}</div>
                          <div className="text-xs text-muted-foreground">{student.user?.email || "-"}</div>
                        </td>
                        <td className="py-3">{student.course || "-"}</td>
                        <td className="py-3">
                          {[student.phone_country, student.phone].filter(Boolean).join(" ") || "-"}
                        </td>
                        <td className="py-3">{student.gender || "-"}</td>
                        <td className="py-3">{student.dob || "-"}</td>
                      </tr>
                    ))}
                    {registeredStudents.length === 0 && (
                      <tr>
                        <td className="py-4 text-muted-foreground" colSpan={5}>No registered students yet.</td>
                      </tr>
                    )}
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </section>
      </main>
      <Footer />
    </div>
  );
};

const AdminMetric = ({ label, value }: { label: string; value: string | number }) => (
  <div className="rounded-lg border border-border bg-slate-50 p-4">
    <p className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">{label}</p>
    <p className="mt-3 text-2xl font-bold text-foreground">{value}</p>
  </div>
);

export default AdminDashboard;
