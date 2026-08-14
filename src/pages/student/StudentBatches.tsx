import { useEffect, useState } from "react";
import { Calendar, ExternalLink, GraduationCap, Video } from "lucide-react";
import StudentLayout from "@/layouts/StudentLayout";
import { fetchStudentBatches, StudentBatch } from "@/services/batchApi";
import { Button } from "@/components/ui/button";

const formatDate = (value: string) => value ? new Date(`${value}T00:00:00`).toLocaleDateString() : "Date not set";

const StudentBatches = () => {
  const [batches, setBatches] = useState<StudentBatch[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");

  useEffect(() => {
    const token = localStorage.getItem("abacus_auth_token");
    if (!token) { setLoading(false); return; }
    fetchStudentBatches(token)
      .then((data) => setBatches(data.batches))
      .catch((reason) => setError(reason instanceof Error ? reason.message : "Unable to load batches"))
      .finally(() => setLoading(false));
  }, []);

  return (
    <StudentLayout header={<div><h1 className="text-2xl md:text-3xl font-heading font-bold text-slate-900">Batches</h1><p className="text-sm text-slate-500 mt-1">Your enrolled batches and upcoming classes</p></div>}>
      {loading && <div className="rounded-2xl bg-white p-6 shadow-card text-slate-500">Loading batch details...</div>}
      {error && <div className="rounded-2xl border border-red-200 bg-red-50 p-5 text-red-700">{error}</div>}
      {!loading && !error && batches.length === 0 && <div className="rounded-2xl bg-white p-6 shadow-card text-slate-600">You are not assigned to a batch yet. Please contact your instructor.</div>}
      <div className="grid gap-5 xl:grid-cols-2">
        {batches.map((batch) => (
          <section key={batch.id} className="rounded-2xl bg-white p-6 shadow-card">
            <div className="flex items-start justify-between gap-3">
              <div><h2 className="text-xl font-heading font-bold text-slate-900">{batch.name}</h2><p className="mt-1 text-sm text-slate-500">{batch.course} • {batch.level}</p></div>
              <div className="rounded-full bg-purple-100 p-3 text-purple-700"><GraduationCap className="h-5 w-5" /></div>
            </div>
            <h3 className="mt-6 mb-3 font-semibold text-slate-900">Upcoming Classes</h3>
            <div className="space-y-3">
              {batch.classes.length === 0 && <p className="rounded-xl bg-slate-50 p-4 text-sm text-slate-500">No classes scheduled yet.</p>}
              {batch.classes.map((session) => (
                <div key={session.id} className="rounded-xl border border-slate-200 p-4 sm:flex sm:items-center sm:justify-between">
                  <div><p className="font-semibold text-slate-900">{session.topic}</p><p className="mt-1 flex items-center gap-2 text-sm text-slate-500"><Calendar className="h-4 w-4" />{formatDate(session.date)} • {session.time || "Time not set"}</p></div>
                  {session.meetingLink && <Button className="mt-3 gap-2 sm:mt-0" asChild><a href={session.meetingLink} target="_blank" rel="noreferrer"><Video className="h-4 w-4" /> Join Class <ExternalLink className="h-3 w-3" /></a></Button>}
                </div>
              ))}
            </div>
          </section>
        ))}
      </div>
    </StudentLayout>
  );
};

export default StudentBatches;
