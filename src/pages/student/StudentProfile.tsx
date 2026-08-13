import { useEffect, useMemo, useState } from "react";
import { useNavigate } from "react-router-dom";
import { CalendarDays, Mail, Pencil, Phone, Save, UserRound, X } from "lucide-react";
import StudentLayout from "@/layouts/StudentLayout";
import { fetchStudentProfile, StudentProfileData, updateStudentProfile } from "@/services/studentApi";
import { useToast } from "@/hooks/use-toast";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";

const TOKEN_KEY = "abacus_auth_token";

const formatDate = (value?: string | null) => {
  if (!value) return "-";
  const d = new Date(value);
  if (Number.isNaN(d.getTime())) return value;
  return d.toLocaleDateString();
};

const valueOrDash = (value?: string | null) => {
  const text = String(value || "").trim();
  return text || "-";
};

const formatPhone = (country?: string | null, phone?: string | null) => {
  const number = String(phone || "").trim();
  if (!number) return "-";
  return [country, number].map((item) => String(item || "").trim()).filter(Boolean).join(" ");
};

const InfoItem = ({ label, value }: { label: string; value?: string | null }) => (
  <div className="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
    <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">{label}</p>
    <p className="mt-1 font-semibold text-slate-900">{valueOrDash(value)}</p>
  </div>
);

const StudentProfile = () => {
  const navigate = useNavigate();
  const { toast } = useToast();
  const [profile, setProfile] = useState<StudentProfileData | null>(null);
  const [loading, setLoading] = useState(true);
  const [editing, setEditing] = useState(false);
  const [saving, setSaving] = useState(false);
  const [form, setForm] = useState({ name: "", course: "", phoneCountry: "+91", phone: "", gender: "", motherTongue: "", dob: "" });

  const populateForm = (value: StudentProfileData) => setForm({
    name: value.name || "",
    course: value.course || "",
    phoneCountry: value.phoneCountry || "+91",
    phone: value.phone || "",
    gender: value.gender || "",
    motherTongue: value.motherTongue || "",
    dob: value.dob ? value.dob.slice(0, 10) : "",
  });

  useEffect(() => {
    const token = localStorage.getItem(TOKEN_KEY);
    if (!token) {
      navigate("/student-login", { replace: true });
      return;
    }

    const loadProfile = async () => {
      try {
        const response = await fetchStudentProfile(token);
        setProfile(response.profile);
        populateForm(response.profile);
      } catch (error) {
        toast({
          title: "Profile error",
          description: error instanceof Error ? error.message : "Failed to load profile.",
        });
      } finally {
        setLoading(false);
      }
    };

    void loadProfile();
  }, [navigate, toast]);

  const activeSubscriptions = useMemo(
    () => (profile?.subscriptions || []).filter((item) => item.status === "active" && item.paymentStatus === "paid"),
    [profile?.subscriptions],
  );

  const saveProfile = async () => {
    const token = localStorage.getItem(TOKEN_KEY);
    if (!token) return;
    try {
      setSaving(true);
      const response = await updateStudentProfile(token, form);
      const refreshed = await fetchStudentProfile(token);
      setProfile(refreshed.profile);
      populateForm(refreshed.profile);
      setEditing(false);
      toast({ title: "Profile updated", description: response.message });
    } catch (error) {
      toast({ title: "Update failed", description: error instanceof Error ? error.message : "Please try again." });
    } finally {
      setSaving(false);
    }
  };

  return (
    <StudentLayout
      header={(
        <div>
          <h1 className="text-2xl md:text-3xl font-heading font-bold text-slate-900">Profile Details</h1>
          <p className="text-sm text-slate-500 mt-1">Update your profile</p>
        </div>
      )}
    >
      {loading ? (
        <div className="rounded-2xl bg-white p-6 shadow-card text-slate-600">Loading profile...</div>
      ) : (
        <div className="space-y-6">
          <section className="rounded-2xl bg-white p-6 shadow-card">
            <div className="flex flex-col gap-5 md:flex-row md:items-center md:justify-between">
              <div className="flex items-center gap-4">
                <div className="flex h-16 w-16 items-center justify-center rounded-full bg-[#5b21b6] text-2xl font-bold text-white">
                  {(profile?.name || "S").slice(0, 1).toUpperCase()}
                </div>
                <div>
                  <h2 className="text-2xl font-heading font-bold text-slate-900">{valueOrDash(profile?.name)}</h2>
                  <p className="mt-1 text-sm text-slate-500">{valueOrDash(profile?.course || profile?.courseName)}</p>
                </div>
              </div>
              <span
                className={`w-fit rounded-full px-4 py-2 text-sm font-bold ${
                  profile?.subscriptionStatus === "active"
                    ? "bg-emerald-100 text-emerald-700"
                    : "bg-red-100 text-red-700"
                }`}
              >
                {profile?.subscriptionStatus === "active" ? "Active Subscription" : "Subscription Expired"}
              </span>
            </div>

            <div className="mt-6 grid gap-4 md:grid-cols-3">
              <div className="flex items-center gap-3 rounded-xl border border-slate-200 p-4">
                <Mail className="h-5 w-5 text-[#5b21b6]" />
                <div>
                  <p className="text-xs uppercase text-slate-500">Email</p>
                  <p className="font-semibold text-slate-900">{valueOrDash(profile?.email)}</p>
                </div>
              </div>
              <div className="flex items-center gap-3 rounded-xl border border-slate-200 p-4">
                <Phone className="h-5 w-5 text-[#5b21b6]" />
                <div>
                  <p className="text-xs uppercase text-slate-500">Mobile Number</p>
                  <p className="font-semibold text-slate-900">{formatPhone(profile?.phoneCountry, profile?.phone)}</p>
                </div>
              </div>
              <div className="flex items-center gap-3 rounded-xl border border-slate-200 p-4">
                <CalendarDays className="h-5 w-5 text-[#5b21b6]" />
                <div>
                  <p className="text-xs uppercase text-slate-500">Joined On</p>
                  <p className="font-semibold text-slate-900">{formatDate(profile?.createdAt)}</p>
                </div>
              </div>
            </div>
          </section>

          <section className="rounded-2xl bg-white p-6 shadow-card">
            <div className="flex items-center justify-between gap-3">
              <div className="flex items-center gap-3">
                <UserRound className="h-5 w-5 text-[#5b21b6]" />
                <h3 className="text-lg font-heading font-bold text-slate-900">Student Information</h3>
              </div>
              {!editing && <Button type="button" size="sm" onClick={() => setEditing(true)}><Pencil className="mr-2 h-4 w-4" />Edit Profile</Button>}
            </div>
            {editing ? (
              <div className="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <div><Label htmlFor="profile-name">Full Name</Label><Input id="profile-name" className="mt-2" value={form.name} onChange={(e) => setForm((v) => ({ ...v, name: e.target.value }))} /></div>
                <div><Label>Email</Label><Input className="mt-2" value={profile?.email || ""} disabled /></div>
                <div><Label htmlFor="profile-course">Course</Label><Input id="profile-course" className="mt-2" value={form.course} onChange={(e) => setForm((v) => ({ ...v, course: e.target.value }))} /></div>
                <div><Label htmlFor="profile-phone">Mobile Number</Label><div className="mt-2 flex gap-2"><Input className="w-24" value={form.phoneCountry} onChange={(e) => setForm((v) => ({ ...v, phoneCountry: e.target.value }))} /><Input id="profile-phone" inputMode="numeric" value={form.phone} onChange={(e) => setForm((v) => ({ ...v, phone: e.target.value.replace(/\D/g, "").slice(0, 15) }))} /></div></div>
                <div><Label htmlFor="profile-gender">Gender</Label><select id="profile-gender" className="mt-2 h-10 w-full rounded-md border border-input bg-background px-3 text-sm" value={form.gender} onChange={(e) => setForm((v) => ({ ...v, gender: e.target.value }))}><option value="">Select gender</option><option value="male">Male</option><option value="female">Female</option><option value="other">Other</option></select></div>
                <div><Label htmlFor="profile-language">Mother Tongue</Label><Input id="profile-language" className="mt-2" value={form.motherTongue} onChange={(e) => setForm((v) => ({ ...v, motherTongue: e.target.value }))} /></div>
                <div><Label htmlFor="profile-dob">Date Of Birth</Label><Input id="profile-dob" type="date" className="mt-2" value={form.dob} onChange={(e) => setForm((v) => ({ ...v, dob: e.target.value }))} /></div>
                <div className="flex items-end gap-2 md:col-span-2 xl:col-span-2"><Button type="button" onClick={saveProfile} disabled={saving}><Save className="mr-2 h-4 w-4" />{saving ? "Saving..." : "Save Changes"}</Button><Button type="button" variant="outline" onClick={() => { if (profile) populateForm(profile); setEditing(false); }} disabled={saving}><X className="mr-2 h-4 w-4" />Cancel</Button></div>
              </div>
            ) : <div className="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
              <InfoItem label="Full Name" value={profile?.name} />
              <InfoItem label="Email" value={profile?.email} />
              <InfoItem label="Course" value={profile?.course || profile?.courseName} />
              <InfoItem label="Mobile Number" value={formatPhone(profile?.phoneCountry, profile?.phone)} />
              <InfoItem label="Gender" value={profile?.gender} />
              <InfoItem label="Mother Tongue" value={profile?.motherTongue} />
              <InfoItem label="Date Of Birth" value={formatDate(profile?.dob)} />
              <InfoItem label="Allocated Level" value={profile?.level} />
              <InfoItem label="Subscription Plan" value={profile?.subscriptionPlan} />
            </div>}
          </section>

          <section className="rounded-2xl bg-white p-6 shadow-card">
            <h3 className="text-lg font-heading font-bold text-slate-900">Active Subscriptions</h3>
            {activeSubscriptions.length === 0 ? (
              <p className="mt-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-4 text-sm text-slate-600">
                No active subscriptions found.
              </p>
            ) : (
              <div className="mt-4 overflow-x-auto">
                <table className="w-full text-sm">
                  <thead>
                    <tr className="border-b text-left text-slate-500">
                      <th className="py-3 pr-4">Plan</th>
                      <th className="py-3 pr-4">Level</th>
                      <th className="py-3 pr-4">Start</th>
                      <th className="py-3">End</th>
                    </tr>
                  </thead>
                  <tbody>
                    {activeSubscriptions.map((subscription) => (
                      <tr key={subscription.id} className="border-b last:border-0 text-slate-700">
                        <td className="py-3 pr-4 font-semibold text-slate-900">{subscription.planName}</td>
                        <td className="py-3 pr-4">{valueOrDash(subscription.levelName)}</td>
                        <td className="py-3 pr-4">{formatDate(subscription.startDate)}</td>
                        <td className="py-3">{formatDate(subscription.expiryDate)}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            )}
          </section>
        </div>
      )}
    </StudentLayout>
  );
};

export default StudentProfile;
