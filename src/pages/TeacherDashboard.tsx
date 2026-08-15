
import { useEffect, useMemo, useRef, useState } from "react";
import { useNavigate } from "react-router-dom";
import {
  Bell,
  BookOpen,
  Calendar,
  CheckCircle2,
  ClipboardList,
  Eye,
  Filter,
  GraduationCap,
  LayoutDashboard,
  Link2,
  Lock,
  LogOut,
  Mail,
  MessageCircle,
  Menu,
  Pencil,
  PlayCircle,
  Plus,
  Search,
  Settings,
  Trash2,
  Trophy,
  Upload,
  UserPlus,
  Users,
  Wallet,
} from "lucide-react";
import { Button } from "@/components/ui/button";
import { Card } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Badge } from "@/components/ui/badge";
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from "@/components/ui/dialog";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Textarea } from "@/components/ui/textarea";
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from "@/components/ui/dropdown-menu";
import { Progress } from "@/components/ui/progress";
import { Sheet, SheetContent, SheetTrigger } from "@/components/ui/sheet";
import { toast } from "sonner";
import {
  Assignment,
  CourseType,
  FeesStatus,
  InstructorDashboardProvider,
  Student,
  useInstructorDashboard,
} from "@/context/InstructorDashboardContext";
import { useAuth } from "@/context/AuthContext";
import TeacherShopSection from "@/components/training/TeacherShopSection";
import {
  getInstructorVideoDashboard,
  InstructorTrainingVideo,
  InstructorVideoDashboard,
  requestInstructorVideoPlayback,
  saveInstructorVideoProgress,
} from "@/services/instructorVideoApi";

const navItems = [
  { key: "overview", label: "Dashboard", icon: LayoutDashboard },
  { key: "topics", label: "Topics", icon: ClipboardList },
  { key: "students", label: "Students", icon: Users },
  { key: "shop", label: "Shop", icon: Wallet },
  { key: "trainingVideos", label: "Training Videos", icon: PlayCircle },
  { key: "batches", label: "Batches", icon: Calendar },
  { key: "enquiries", label: "Enquiries", icon: MessageCircle },
  { key: "settings", label: "Settings", icon: Settings },
] as const;

type NavKey = (typeof navItems)[number]["key"] | "courses" | "materials";

type StudentFormState = {
  name: string;
  parentEmail: string;
  password: string;
  parentMobile: string;
  whatsappNumber: string;
  dateOfBirth: string;
  gender: string;
  course: "Abacus" | "Vedic Maths";
  joiningDate: string;
  avatarUrl: string | null;
  level: string;
  batchId: string | "none";
  levelStartDate: string;
  levelEndDate: string;
  feesStatus: FeesStatus;
};

type FeeFilterValue = "all" | FeesStatus | "pending";

const studentCourseLevels: Record<StudentFormState["course"], string[]> = {
  Abacus: ["Level 0", "Level 1", "Level 2", "Level 3", "Level 4", "Level 5", "Level 6", "Level 7"],
  "Vedic Maths": ["Level 1", "Level 2", "Level 3", "Level 4"],
};

type BatchFormState = {
  name: string;
  course: CourseType;
  level: string;
};

type ClassFormState = {
  batchId: string;
  topic: string;
  date: string;
  time: string;
  meetingLink: string;
};

type AssignmentFormState = {
  title: string;
  course: CourseType;
  level: string;
  dueDate: string;
  targetType: "student" | "batch";
  targetId: string;
  questions: string;
  autoGenerate: boolean;
};

type PaymentFormState = {
  studentId: string;
  amount: string;
  method: string;
  status: FeesStatus;
};

type MaterialFormState = {
  title: string;
  course: CourseType;
  level: string;
  type: "pdf" | "video";
  url: string;
  batchId: string;
};

type AnnouncementFormState = {
  title: string;
  message: string;
};

const feeBadge = (status: FeesStatus) =>
  status === "paid" ? "bg-emerald-100 text-emerald-700" : "bg-rose-100 text-rose-700";

const feeStatusLabel = (status: FeesStatus) => (status === "paid" ? "Paid" : "Unpaid");

const formatDate = (value: string) => {
  if (!value) return "-";
  return new Date(value).toLocaleDateString();
};

const dashboardId = () =>
  typeof crypto !== "undefined" && "randomUUID" in crypto ? crypto.randomUUID() : Math.random().toString(36).slice(2);

const usePersistentState = <T,>(key: string, initialValue: T) => {
  const [value, setValue] = useState<T>(() => {
    if (typeof window === "undefined") return initialValue;
    const stored = window.localStorage.getItem(key);
    if (!stored) return initialValue;
    try {
      return JSON.parse(stored) as T;
    } catch {
      window.localStorage.removeItem(key);
      return initialValue;
    }
  });

  useEffect(() => {
    if (typeof window !== "undefined") {
      window.localStorage.setItem(key, JSON.stringify(value));
    }
  }, [key, value]);

  return [value, setValue] as const;
};

const SectionTitle = ({ title, subtitle }: { title: string; subtitle?: string }) => (
  <div className="flex flex-col gap-1">
    <h2 className="text-2xl font-semibold text-foreground">{title}</h2>
    {subtitle ? <p className="text-sm text-muted-foreground">{subtitle}</p> : null}
  </div>
);

const OverviewSection = ({ onNavigate }: { onNavigate: (tab: NavKey) => void }) => {
  const { students, batches, classes } = useInstructorDashboard();
  const today = new Date();
  const todayIso = today.toISOString().slice(0, 10);
  const monthStart = new Date(today.getFullYear(), today.getMonth(), 1);
  const daysInMonth = new Date(today.getFullYear(), today.getMonth() + 1, 0).getDate();
  const monthCells = Array.from({ length: monthStart.getDay() + daysInMonth }, (_, index) => {
    const day = index - monthStart.getDay() + 1;
    return day > 0 ? day : null;
  });
  const todayClasses = classes.filter((session) => session.date === todayIso);
  const levels = new Set([...students.map((student) => student.level), ...batches.map((batch) => batch.level)]);

  const cards = [
    { title: "Total Course Types", value: 3, caption: "All Course Types", icon: BookOpen, color: "bg-cyan-500", tab: "courses" as const },
    { title: "Total Course Levels", value: levels.size, caption: "All Levels", icon: ClipboardList, color: "bg-emerald-500", tab: "courses" as const },
    { title: "Total Students", value: students.length, caption: "All Students", icon: Users, color: "bg-indigo-600", tab: "students" as const },
    { title: "Total Batches", value: batches.length, caption: "All Batches", icon: Calendar, color: "bg-emerald-500", tab: "batches" as const },
  ];

  return (
    <div className="space-y-8">
      <div className="grid gap-5 md:grid-cols-2 xl:grid-cols-4">
        {cards.map((card) => (
          <button
            key={card.title}
            type="button"
            onClick={() => onNavigate(card.tab)}
            className="relative min-h-[94px] bg-white p-5 text-left shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
          >
            <p className="text-sm font-semibold text-slate-950">{card.title}</p>
            <p className="mt-2 text-base text-slate-900">{card.value}</p>
            <div className="mt-2 h-px w-2/3 bg-slate-200" />
            <p className="mt-2 text-xs text-slate-400">{card.caption}</p>
            <span className={`absolute right-[-10px] top-6 flex h-11 w-11 items-center justify-center text-white shadow-lg ${card.color}`}>
              <card.icon className="h-5 w-5" />
            </span>
          </button>
        ))}
      </div>

      <div className="bg-white p-4 shadow-sm">
        <div className="mb-6 flex items-center justify-center gap-6 bg-[#303030] py-3 text-xl font-semibold text-white">
          <span>‹</span>
          <span>{today.toLocaleString("en-US", { month: "long" })} - {today.getFullYear()}</span>
          <span>›</span>
        </div>
        <div className="grid gap-8 xl:grid-cols-[1fr_1fr]">
          <div className="overflow-hidden border border-slate-300">
            <div className="grid grid-cols-7 bg-gradient-to-b from-stone-200 to-stone-100 text-sm font-semibold">
              {["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"].map((day) => (
                <div key={day} className="border-r border-slate-500 px-3 py-3 text-center last:border-r-0">{day}</div>
              ))}
            </div>
            <div className="grid grid-cols-7">
              {monthCells.map((day, index) => {
                const isToday = day === today.getDate();
                return (
                  <div
                    key={`${day || "empty"}-${index}`}
                    className={`min-h-[52px] border-r border-t border-slate-200 p-2 text-center text-sm font-semibold last:border-r-0 ${
                      isToday ? "bg-yellow-50 text-slate-950" : day ? "bg-white" : "bg-slate-100"
                    }`}
                  >
                    {day}
                  </div>
                );
              })}
            </div>
          </div>

          <div>
            <h3 className="mb-2 text-base font-semibold">
              Today Time Table : ( {today.toLocaleDateString("en-GB").replace(/\//g, " - ")} )
            </h3>
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>#</TableHead>
                  <TableHead>Batch Name</TableHead>
                  <TableHead>Timing</TableHead>
                  <TableHead>Action</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {todayClasses.map((session, index) => {
                  const batch = batches.find((item) => item.id === session.batchId);
                  return (
                    <TableRow key={session.id}>
                      <TableCell>{index + 1}</TableCell>
                      <TableCell>{batch?.name || "Unassigned"}</TableCell>
                      <TableCell>{session.time}</TableCell>
                      <TableCell>
                        <Button size="sm" variant="outline" onClick={() => onNavigate("batches")}>Open</Button>
                      </TableCell>
                    </TableRow>
                  );
                })}
                {!todayClasses.length && (
                  <TableRow>
                    <TableCell colSpan={4} className="py-8 text-center text-sm text-slate-500">No Data Found</TableCell>
                  </TableRow>
                )}
              </TableBody>
            </Table>
          </div>
        </div>
      </div>
    </div>
  );
};
const StudentsSection = () => {
  const { students, batches, addStudent, updateStudent, deleteStudent } = useInstructorDashboard();
  const [search, setSearch] = useState("");
  const [feeFilter, setFeeFilter] = useState<FeeFilterValue>("all");
  const [editingStudent, setEditingStudent] = useState<Student | null>(null);
  const [viewStudent, setViewStudent] = useState<Student | null>(null);
  const [isStudentFormOpen, setIsStudentFormOpen] = useState(false);
  const [isSavingStudent, setIsSavingStudent] = useState(false);

  const [formState, setFormState] = useState<StudentFormState>({
    name: "",
    parentEmail: "",
    password: "",
    parentMobile: "",
    whatsappNumber: "",
    dateOfBirth: "",
    gender: "",
    course: "Abacus",
    joiningDate: "",
    avatarUrl: null,
    level: "Level 0",
    batchId: "none",
    levelStartDate: "",
    levelEndDate: "",
    feesStatus: "unpaid",
  });

  const filteredStudents = students.filter((student) => {
    const matchesSearch = student.name.toLowerCase().includes(search.toLowerCase());
    const matchesFee =
      feeFilter === "all" ||
      (feeFilter === "paid" ? student.feesStatus === "paid" : student.feesStatus !== "paid");
    return matchesSearch && matchesFee;
  });
  const isStudentFormValid = Boolean(
    formState.name.trim() &&
    formState.parentEmail.trim() &&
    (editingStudent || formState.password.length >= 6) &&
    /^\d{10}$/.test(formState.parentMobile) &&
    /^\d{10}$/.test(formState.whatsappNumber) &&
    formState.course &&
    formState.level,
  );

  const resetForm = () =>
    setFormState({
      name: "",
      parentEmail: "",
      password: "",
      parentMobile: "",
      whatsappNumber: "",
      dateOfBirth: "",
      gender: "",
      course: "Abacus",
      joiningDate: "",
      avatarUrl: null,
      level: "Level 0",
      batchId: "none",
      levelStartDate: "",
      levelEndDate: "",
      feesStatus: "unpaid",
    });

  const handleStudentPhotoChange = (event: React.ChangeEvent<HTMLInputElement>) => {
    const file = event.target.files?.[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = () => {
      setFormState((prev) => ({
        ...prev,
        avatarUrl: typeof reader.result === "string" ? reader.result : null,
      }));
    };
    reader.readAsDataURL(file);
  };

  const handleSaveStudent = async () => {
    if (isSavingStudent) return;
    if (!formState.name.trim() || !formState.parentEmail.trim() || !formState.parentMobile.trim()) {
      toast.error("Please enter student name, parent email, and parent mobile.");
      return;
    }
    if (!/^\d{10}$/.test(formState.parentMobile.trim()) || !/^\d{10}$/.test(formState.whatsappNumber.trim())) {
      toast.error("Mobile and WhatsApp numbers must contain exactly 10 digits.");
      return;
    }
    const studentEmail = formState.parentEmail.trim();
    if (editingStudent) {
      updateStudent(editingStudent.id, {
        name: formState.name.trim(),
        email: studentEmail,
        parentEmail: studentEmail,
        parentMobile: formState.parentMobile.trim(),
        whatsappNumber: formState.whatsappNumber.trim(),
        dateOfBirth: formState.dateOfBirth,
        gender: formState.gender,
        course: formState.course,
        avatarUrl: formState.avatarUrl,
        level: formState.level,
        batchId: formState.batchId === "none" ? null : formState.batchId,
        levelStartDate: formState.levelStartDate,
        levelEndDate: formState.levelEndDate,
        feesStatus: formState.feesStatus,
        joinedAt: formState.joiningDate || editingStudent.joinedAt,
      });
      setEditingStudent(null);
      toast.success("Student updated.");
    } else {
      try {
        setIsSavingStudent(true);
        await addStudent({
          name: formState.name.trim(),
          email: studentEmail,
          password: formState.password,
          parentEmail: studentEmail,
          parentMobile: formState.parentMobile.trim(),
          whatsappNumber: formState.whatsappNumber.trim(),
          dateOfBirth: formState.dateOfBirth,
          gender: formState.gender,
          course: formState.course,
          avatarUrl: formState.avatarUrl,
          level: formState.level,
          batchId: formState.batchId === "none" ? null : formState.batchId,
          levelStartDate: formState.levelStartDate,
          levelEndDate: formState.levelEndDate,
          feesStatus: formState.feesStatus,
          joinedAt: formState.joiningDate || new Date().toISOString().slice(0, 10),
        });
        toast.success("Student added and synced with the admin panel.");
      } catch (error) {
        toast.error(error instanceof Error ? error.message : "Student could not be added.");
        return;
      } finally {
        setIsSavingStudent(false);
      }
    }
    resetForm();
    setIsStudentFormOpen(false);
  };

  const startEdit = (student: Student) => {
    setEditingStudent(student);
    setFormState({
      name: student.name,
      parentEmail: student.parentEmail || student.email,
      password: "",
      parentMobile: student.parentMobile || "",
      whatsappNumber: student.whatsappNumber || student.motherTongue || "",
      dateOfBirth: student.dateOfBirth || "",
      gender: student.gender || "",
      course: student.course || "Abacus",
      joiningDate: student.joinedAt || "",
      avatarUrl: student.avatarUrl || null,
      level: student.level,
      batchId: student.batchId ?? "none",
      levelStartDate: student.levelStartDate || "",
      levelEndDate: student.levelEndDate || "",
      feesStatus: student.feesStatus,
    });
    setIsStudentFormOpen(true);
  };

  return (
    <div className="space-y-6">
      <div className="flex flex-wrap items-center justify-between gap-4">
        <SectionTitle title="My Students" subtitle="Manage all enrolled learners" />
        <Dialog open={isStudentFormOpen} onOpenChange={setIsStudentFormOpen}>
          <DialogTrigger asChild>
            <Button
              className="gap-2"
              onClick={() => {
                setEditingStudent(null);
                resetForm();
              }}
            >
              <Plus className="h-4 w-4" /> Add Student
            </Button>
          </DialogTrigger>
          <DialogContent className="max-w-6xl">
            <DialogHeader className="border-b pb-3">
              <div className="flex items-center justify-between gap-3">
                <DialogTitle>{editingStudent ? "Edit Student Form" : "Student Form"}</DialogTitle>
                <Button
                  type="button"
                  variant="outline"
                  size="sm"
                  className="h-8 rounded-full border-[#465b91] px-5 text-xs text-[#24366f]"
                  onClick={() => setIsStudentFormOpen(false)}
                >
                  Students List
                </Button>
              </div>
            </DialogHeader>
            <div className="grid gap-x-6 gap-y-3 md:grid-cols-3">
              <div className="space-y-1.5">
                <Label htmlFor="studentName" className="text-xs font-normal text-slate-500">Full Name</Label>
                <Input
                  id="studentName"
                  value={formState.name}
                  onChange={(e) => setFormState((prev) => ({ ...prev, name: e.target.value }))}
                />
              </div>
              <div className="space-y-1.5">
                <Label htmlFor="parentEmail" className="text-xs font-normal text-slate-500">Parent Email Id</Label>
                <Input
                  id="parentEmail"
                  type="email"
                  value={formState.parentEmail}
                  onChange={(e) => setFormState((prev) => ({ ...prev, parentEmail: e.target.value }))}
                />
              </div>
              {!editingStudent ? (
                <div className="space-y-1.5">
                  <Label htmlFor="studentPassword" className="text-xs font-normal text-slate-500">Student Login Password</Label>
                  <Input
                    id="studentPassword"
                    type="password"
                    minLength={6}
                    autoComplete="new-password"
                    value={formState.password}
                    onChange={(e) => setFormState((prev) => ({ ...prev, password: e.target.value }))}
                    placeholder="Minimum 6 characters"
                  />
                </div>
              ) : null}
              <div className="space-y-1.5">
                <Label htmlFor="parentMobile" className="text-xs font-normal text-slate-500">Parent Mobile</Label>
                <Input
                  id="parentMobile"
                  type="tel"
                  inputMode="numeric"
                  maxLength={10}
                  pattern="[0-9]{10}"
                  value={formState.parentMobile}
                  onChange={(e) => setFormState((prev) => ({ ...prev, parentMobile: e.target.value.replace(/\D/g, "").slice(0, 10) }))}
                />
              </div>
              <div className="space-y-1.5">
                <Label htmlFor="dateOfBirth" className="text-xs font-normal text-slate-500">Date Of Birth</Label>
                <Input
                  id="dateOfBirth"
                  type="date"
                  value={formState.dateOfBirth}
                  onChange={(e) => setFormState((prev) => ({ ...prev, dateOfBirth: e.target.value }))}
                />
              </div>
              <div className="space-y-1.5">
                <Label className="text-xs font-normal text-slate-500">Gender</Label>
                <Select value={formState.gender} onValueChange={(value) => setFormState((prev) => ({ ...prev, gender: value }))}>
                  <SelectTrigger>
                    <SelectValue placeholder="Select Gender" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="Male">Male</SelectItem>
                    <SelectItem value="Female">Female</SelectItem>
                    <SelectItem value="Other">Other</SelectItem>
                  </SelectContent>
                </Select>
              </div>
              <div className="space-y-1.5">
                <Label htmlFor="whatsappNumber" className="text-xs font-normal text-slate-500">WhatsApp Number</Label>
                <Input
                  id="whatsappNumber"
                  type="tel"
                  inputMode="numeric"
                  maxLength={10}
                  pattern="[0-9]{10}"
                  value={formState.whatsappNumber}
                  onChange={(e) => setFormState((prev) => ({ ...prev, whatsappNumber: e.target.value.replace(/\D/g, "").slice(0, 10) }))}
                />
              </div>
              <div className="space-y-1.5">
                <Label className="text-xs font-normal text-slate-500">Course</Label>
                <Select
                  value={formState.course}
                  onValueChange={(course: StudentFormState["course"]) =>
                    setFormState((prev) => ({ ...prev, course, level: studentCourseLevels[course][0] }))
                  }
                >
                  <SelectTrigger><SelectValue placeholder="Select Course" /></SelectTrigger>
                  <SelectContent>
                    <SelectItem value="Abacus">Abacus</SelectItem>
                    <SelectItem value="Vedic Maths">Vedic Maths</SelectItem>
                  </SelectContent>
                </Select>
              </div>
              <div className="space-y-1.5">
                <Label className="text-xs font-normal text-slate-500">Level</Label>
                <Select value={formState.level} onValueChange={(level) => setFormState((prev) => ({ ...prev, level }))}>
                  <SelectTrigger><SelectValue placeholder="Select Level" /></SelectTrigger>
                  <SelectContent>
                    {studentCourseLevels[formState.course].map((level) => <SelectItem key={level} value={level}>{level}</SelectItem>)}
                  </SelectContent>
                </Select>
              </div>
              <div className="space-y-1.5">
                <Label className="text-xs font-normal text-slate-500">Fees Status</Label>
                <Select value={formState.feesStatus} onValueChange={(feesStatus: FeesStatus) => setFormState((prev) => ({ ...prev, feesStatus }))}>
                  <SelectTrigger><SelectValue placeholder="Select Fees Status" /></SelectTrigger>
                  <SelectContent>
                    <SelectItem value="unpaid">Unpaid</SelectItem>
                    <SelectItem value="paid">Paid</SelectItem>
                  </SelectContent>
                </Select>
              </div>
              <div className="space-y-1.5">
                <Label htmlFor="joiningDate" className="text-xs font-normal text-slate-500">Joining/Joined Date</Label>
                <Input
                  id="joiningDate"
                  type="date"
                  value={formState.joiningDate}
                  onChange={(e) => setFormState((prev) => ({ ...prev, joiningDate: e.target.value }))}
                />
              </div>
              <div className="space-y-1.5">
                <Label htmlFor="levelStartDate" className="text-xs font-normal text-slate-500">Level Start Date</Label>
                <Input
                  id="levelStartDate"
                  type="date"
                  value={formState.levelStartDate}
                  onChange={(e) => setFormState((prev) => ({ ...prev, levelStartDate: e.target.value }))}
                />
              </div>
              <div className="space-y-1.5">
                <Label htmlFor="levelEndDate" className="text-xs font-normal text-slate-500">Level End Date</Label>
                <Input
                  id="levelEndDate"
                  type="date"
                  value={formState.levelEndDate}
                  onChange={(e) => setFormState((prev) => ({ ...prev, levelEndDate: e.target.value }))}
                />
              </div>
              <div className="space-y-1.5">
                <Label htmlFor="profilePic" className="text-xs font-normal text-slate-500">Profile Pic</Label>
                <Input id="profilePic" type="file" accept="image/*" onChange={handleStudentPhotoChange} />
              </div>
              <div className="flex items-end justify-end">
                <Button disabled={!isStudentFormValid || isSavingStudent} className="rounded-full bg-[#465b91] px-6 hover:bg-[#384979]" onClick={handleSaveStudent}>
                  {isSavingStudent ? "Saving..." : "Submit"}
                </Button>
              </div>
            </div>
          </DialogContent>
        </Dialog>
      </div>

      <div className="flex flex-wrap items-center gap-3">
        <div className="relative w-full max-w-sm">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
          <Input
            placeholder="Search students"
            className="pl-10"
            value={search}
            onChange={(e) => setSearch(e.target.value)}
          />
        </div>
        <Select value={feeFilter} onValueChange={(value: "all" | FeesStatus) => setFeeFilter(value)}>
          <SelectTrigger className="w-[160px]">
            <Filter className="h-4 w-4 mr-2" />
            <SelectValue placeholder="Fees" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="all">All Fees</SelectItem>
            <SelectItem value="paid">Paid</SelectItem>
            <SelectItem value="unpaid">Unpaid</SelectItem>
          </SelectContent>
        </Select>
      </div>

      <Card className="shadow-card">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Name</TableHead>
              <TableHead>Course</TableHead>
              <TableHead>Level</TableHead>
              <TableHead>Start</TableHead>
              <TableHead>End</TableHead>
              <TableHead>Batch</TableHead>
              <TableHead>Fees</TableHead>
              <TableHead className="text-right">Actions</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {filteredStudents.map((student) => {
              const batch = batches.find((item) => item.id === student.batchId);
              return (
                <TableRow key={student.id}>
                  <TableCell className="font-medium">{student.name}</TableCell>
                  <TableCell>{student.course || "Abacus"}</TableCell>
                  <TableCell>{student.level}</TableCell>
                  <TableCell>{formatDate(student.levelStartDate || "")}</TableCell>
                  <TableCell>{formatDate(student.levelEndDate || "")}</TableCell>
                  <TableCell>{batch?.name || "Unassigned"}</TableCell>
                  <TableCell>
                    <Select
                      value={student.feesStatus}
                      onValueChange={(feesStatus: FeesStatus) => {
                        updateStudent(student.id, { feesStatus });
                        toast.success(`Fees marked as ${feeStatusLabel(feesStatus)}.`);
                      }}
                    >
                      <SelectTrigger className={`h-8 w-[105px] border-0 text-xs font-medium ${feeBadge(student.feesStatus)}`}>
                        <SelectValue />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="unpaid">Unpaid</SelectItem>
                        <SelectItem value="paid">Paid</SelectItem>
                      </SelectContent>
                    </Select>
                  </TableCell>
                  <TableCell className="text-right">
                    <div className="flex items-center justify-end gap-2">
                      <Button variant="ghost" size="icon" onClick={() => setViewStudent(student)}>
                        <Eye className="h-4 w-4" />
                      </Button>
                      <Button variant="ghost" size="icon" onClick={() => startEdit(student)}>
                        <Pencil className="h-4 w-4" />
                      </Button>
                      <Button
                        variant="ghost"
                        size="icon"
                        onClick={() => {
                          if (window.confirm("Delete this student?")) deleteStudent(student.id);
                        }}
                      >
                        <Trash2 className="h-4 w-4" />
                      </Button>
                    </div>
                  </TableCell>
                </TableRow>
              );
            })}
          </TableBody>
        </Table>
      </Card>

      <StudentDetailPanel student={viewStudent} onClose={() => setViewStudent(null)} />
    </div>
  );
};

const StudentDetailPanel = ({ student, onClose }: { student: Student | null; onClose: () => void }) => {
  const { batches, classes, assignments } = useInstructorDashboard();
  if (!student) return null;
  const batch = batches.find((item) => item.id === student.batchId);
  const classList = classes.filter((session) => session.batchId === student.batchId);
  const assignmentList = assignments.filter((assignment) => {
    if (assignment.assignedTo.type === "student") return assignment.assignedTo.id === student.id;
    return assignment.assignedTo.id === student.batchId;
  });

  return (
    <Dialog open={!!student} onOpenChange={(open) => !open && onClose()}>
      <DialogContent className="max-w-3xl">
        <DialogHeader>
          <DialogTitle>Student Profile</DialogTitle>
        </DialogHeader>
        <div className="grid gap-6 lg:grid-cols-[1.1fr_0.9fr]">
          <div className="space-y-4">
            <Card className="p-4">
              <p className="text-sm text-muted-foreground">Name</p>
              <p className="text-lg font-semibold">{student.name}</p>
              <div className="mt-3 grid grid-cols-2 gap-3 text-sm">
                <div>
                  <p className="text-muted-foreground">Level</p>
                  <p className="font-medium">{student.level}</p>
                </div>
                <div>
                  <p className="text-muted-foreground">Batch</p>
                  <p className="font-medium">{batch?.name || "Unassigned"}</p>
                </div>
                <div>
                  <p className="text-muted-foreground">Fees</p>
                  <p className="font-medium">{feeStatusLabel(student.feesStatus)}</p>
                </div>
                <div>
                  <p className="text-muted-foreground">Level Start</p>
                  <p className="font-medium">{formatDate(student.levelStartDate || "")}</p>
                </div>
                <div>
                  <p className="text-muted-foreground">Level End</p>
                  <p className="font-medium">{formatDate(student.levelEndDate || "")}</p>
                </div>
                <div>
                  <p className="text-muted-foreground">Level Status</p>
                  <p className="font-medium">
                    {student.levelEndDate
                      ? new Date(student.levelEndDate) >= new Date()
                        ? "Active"
                        : "Completed"
                      : "Not set"}
                  </p>
                </div>
                <div>
                  <p className="text-muted-foreground">Joined</p>
                  <p className="font-medium">{formatDate(student.joinedAt)}</p>
                </div>
              </div>
            </Card>
            <Card className="p-4">
              <p className="text-sm text-muted-foreground">Assignments</p>
              <ul className="mt-3 space-y-2 text-sm">
                {assignmentList.map((assignment) => (
                  <li key={assignment.id} className="flex items-center justify-between">
                    <span>{assignment.title}</span>
                    <Badge variant="outline">Due {formatDate(assignment.dueDate)}</Badge>
                  </li>
                ))}
                {!assignmentList.length && <li className="text-muted-foreground">No assignments yet.</li>}
              </ul>
            </Card>
          </div>
          <div className="space-y-4">
            <Card className="p-4">
              <p className="text-sm text-muted-foreground">Upcoming Classes</p>
              <ul className="mt-3 space-y-3 text-sm">
                {classList.map((session) => (
                  <li key={session.id} className="flex items-start justify-between gap-3">
                    <div>
                      <p className="font-medium">{session.topic}</p>
                      <p className="text-xs text-muted-foreground">
                        {formatDate(session.date)} • {session.time}
                      </p>
                    </div>
                    <a href={session.meetingLink} className="text-xs text-primary hover:underline">
                      Join
                    </a>
                  </li>
                ))}
                {!classList.length && <li className="text-muted-foreground">No classes scheduled.</li>}
              </ul>
            </Card>
          </div>
        </div>
      </DialogContent>
    </Dialog>
  );
};
const BatchesSection = () => {
  const { batches, students, classes, addBatch, deleteBatch, assignStudentToBatch, scheduleClass, toggleAttendance } =
    useInstructorDashboard();
  const [batchForm, setBatchForm] = useState<BatchFormState>({ name: "", course: "Abacus", level: "" });
  const [classForm, setClassForm] = useState<ClassFormState>({
    batchId: batches[0]?.id || "",
    topic: "",
    date: "",
    time: "",
    meetingLink: "",
  });
  const [selectedStudents, setSelectedStudents] = useState<Record<string, string>>({});
  const [assigningBatchId, setAssigningBatchId] = useState<string | null>(null);

  const handleCreateBatch = () => {
    if (!batchForm.name.trim()) return;
    addBatch({ name: batchForm.name, course: batchForm.course, level: batchForm.level || "Mixed" });
    setBatchForm({ name: "", course: "Abacus", level: "" });
  };

  const handleScheduleClass = () => {
    if (!classForm.batchId || !classForm.topic.trim()) return;
    scheduleClass({
      batchId: classForm.batchId,
      topic: classForm.topic,
      date: classForm.date,
      time: classForm.time,
      meetingLink: classForm.meetingLink,
    });
    setClassForm({ batchId: batches[0]?.id || "", topic: "", date: "", time: "", meetingLink: "" });
  };

  const handleDeleteBatch = (batchId: string, batchName: string) => {
    const confirmed = window.confirm(`Delete "${batchName}"? Students will be unassigned and scheduled classes for this batch will be removed.`);
    if (!confirmed) return;
    deleteBatch(batchId);
    setClassForm((prev) => ({ ...prev, batchId: prev.batchId === batchId ? "" : prev.batchId }));
    toast.success("Batch removed");
  };

  const handleAssignStudent = async (batchId: string) => {
    const studentId = selectedStudents[batchId];
    if (!studentId) {
      toast.error("Please select a student first.");
      return;
    }

    try {
      setAssigningBatchId(batchId);
      await assignStudentToBatch(studentId, batchId);
      setSelectedStudents((current) => ({ ...current, [batchId]: "" }));
      toast.success("Student assigned to batch. The scheduled class will now appear in the student's Batches tab.");
    } catch (error) {
      toast.error(error instanceof Error ? error.message : "Could not assign student to this batch.");
    } finally {
      setAssigningBatchId(null);
    }
  };

  return (
    <div className="space-y-6">
      <div className="flex flex-wrap items-center justify-between gap-4">
        <SectionTitle title="Batches & Classes" subtitle="Create batches and manage schedules" />
        <div className="flex gap-3">
          <Dialog>
            <DialogTrigger asChild>
              <Button variant="outline" className="gap-2">
                <Plus className="h-4 w-4" /> Create Batch
              </Button>
            </DialogTrigger>
            <DialogContent>
              <DialogHeader>
                <DialogTitle>Create Batch</DialogTitle>
              </DialogHeader>
              <div className="space-y-3">
                <Input
                  placeholder="Batch name"
                  value={batchForm.name}
                  onChange={(e) => setBatchForm((prev) => ({ ...prev, name: e.target.value }))}
                />
                <Select value={batchForm.course} onValueChange={(course: CourseType) => setBatchForm((prev) => ({ ...prev, course }))}>
                  <SelectTrigger><SelectValue placeholder="Select course" /></SelectTrigger>
                  <SelectContent>
                    <SelectItem value="Abacus">Abacus</SelectItem>
                    <SelectItem value="Vedic Maths">Vedic Maths</SelectItem>
                    <SelectItem value="Worksheet Practice">Worksheet Practice</SelectItem>
                  </SelectContent>
                </Select>
                <Input
                  placeholder="Level (e.g. Level 3)"
                  value={batchForm.level}
                  onChange={(e) => setBatchForm((prev) => ({ ...prev, level: e.target.value }))}
                />
                <Button onClick={handleCreateBatch}>Save Batch</Button>
              </div>
            </DialogContent>
          </Dialog>

          <Dialog>
            <DialogTrigger asChild>
              <Button className="gap-2">
                <Calendar className="h-4 w-4" /> Schedule Class
              </Button>
            </DialogTrigger>
            <DialogContent>
              <DialogHeader>
                <DialogTitle>Schedule Class</DialogTitle>
              </DialogHeader>
              <div className="space-y-3">
                <Select value={classForm.batchId} onValueChange={(value) => setClassForm((prev) => ({ ...prev, batchId: value }))}>
                  <SelectTrigger>
                    <SelectValue placeholder="Select batch" />
                  </SelectTrigger>
                  <SelectContent>
                    {batches.map((batch) => (
                      <SelectItem key={batch.id} value={batch.id}>
                        {batch.name}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
                <Input
                  placeholder="Topic"
                  value={classForm.topic}
                  onChange={(e) => setClassForm((prev) => ({ ...prev, topic: e.target.value }))}
                />
                <div className="grid grid-cols-2 gap-3">
                  <Input
                    type="date"
                    value={classForm.date}
                    onChange={(e) => setClassForm((prev) => ({ ...prev, date: e.target.value }))}
                  />
                  <Input
                    type="time"
                    value={classForm.time}
                    onChange={(e) => setClassForm((prev) => ({ ...prev, time: e.target.value }))}
                  />
                </div>
                <Input
                  placeholder="Meeting link"
                  value={classForm.meetingLink}
                  onChange={(e) => setClassForm((prev) => ({ ...prev, meetingLink: e.target.value }))}
                />
                <Button onClick={handleScheduleClass}>Add Class</Button>
              </div>
            </DialogContent>
          </Dialog>
        </div>
      </div>

      <div className="grid gap-4 lg:grid-cols-2">
        {batches.map((batch) => {
          const batchStudents = students.filter((student) => student.batchId === batch.id);
          const batchClasses = classes.filter((session) => session.batchId === batch.id);
          const upcoming = batchClasses.slice(0, 2);
          const latestClass = batchClasses[0];
          return (
            <Card key={batch.id} className="p-5 shadow-card space-y-4">
              <div className="flex items-start justify-between">
                <div>
                  <p className="text-lg font-semibold">{batch.name}</p>
                  <p className="text-sm text-muted-foreground">
                    {batch.level} • {batchStudents.length} students
                  </p>
                </div>
                <div className="flex items-center gap-2">
                  <Badge variant="outline">Active</Badge>
                  <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    className="gap-2 border-rose-200 text-rose-600 hover:bg-rose-50 hover:text-rose-700"
                    onClick={() => handleDeleteBatch(batch.id, batch.name)}
                  >
                    <Trash2 className="h-4 w-4" />
                    Delete
                  </Button>
                </div>
              </div>
              <div className="space-y-2">
                <p className="text-sm font-medium">Assign students</p>
                <div className="flex gap-2">
                  <Select
                    value={selectedStudents[batch.id] || ""}
                    onValueChange={(value) => setSelectedStudents((current) => ({ ...current, [batch.id]: value }))}
                  >
                    <SelectTrigger className="flex-1">
                      <SelectValue placeholder="Select student" />
                    </SelectTrigger>
                    <SelectContent>
                      {students
                        .filter((student) => student.batchId !== batch.id)
                        .map((student) => (
                          <SelectItem key={student.id} value={student.id}>
                            {student.name}
                          </SelectItem>
                        ))}
                    </SelectContent>
                  </Select>
                  <Button
                    type="button"
                    variant="outline"
                    disabled={!selectedStudents[batch.id] || assigningBatchId === batch.id}
                    onClick={() => void handleAssignStudent(batch.id)}
                  >
                    {assigningBatchId === batch.id ? "Assigning..." : "Assign"}
                  </Button>
                </div>
              </div>
              <div className="space-y-2">
                <p className="text-sm font-medium">Upcoming Classes</p>
                {upcoming.map((session) => (
                  <div key={session.id} className="flex items-center justify-between rounded-lg border border-border p-3 text-sm">
                    <div>
                      <p className="font-medium">{session.topic}</p>
                      <p className="text-xs text-muted-foreground">
                        {formatDate(session.date)} • {session.time}
                      </p>
                    </div>
                    <Button variant="ghost" size="sm" className="gap-2">
                      <Link2 className="h-4 w-4" /> Link
                    </Button>
                  </div>
                ))}
                {!upcoming.length && <p className="text-sm text-muted-foreground">No classes scheduled.</p>}
              </div>
              <div>
                <div className="mb-2 flex flex-wrap items-center justify-between gap-2">
                  <p className="text-sm font-medium">Attendance</p>
                  {latestClass ? (
                    <span className="text-xs text-muted-foreground">
                      For {latestClass.topic} {latestClass.date ? `on ${formatDate(latestClass.date)}` : ""}
                    </span>
                  ) : null}
                </div>
                {!latestClass && <p className="mb-2 text-xs text-amber-700">Schedule a class first to mark attendance.</p>}
                <div className="space-y-2">
                  {batchStudents.map((student) => {
                    const isPresent = latestClass ? Boolean(latestClass.attendance[student.id]) : false;
                    return (
                      <div key={student.id} className="flex items-center justify-between text-sm">
                        <span>{student.name}</span>
                        <div className="flex items-center gap-2">
                          {latestClass ? (
                            <Badge variant="outline" className={isPresent ? "border-emerald-200 bg-emerald-50 text-emerald-700" : "border-slate-200 bg-slate-50 text-slate-600"}>
                              {isPresent ? "Present" : "Absent"}
                            </Badge>
                          ) : null}
                          <Button
                            variant="outline"
                            size="sm"
                            disabled={!latestClass}
                            onClick={() => {
                              if (!latestClass) {
                                toast.error("Schedule a class before marking attendance.");
                                return;
                              }
                              toggleAttendance(latestClass.id, student.id);
                              toast.success(`${student.name} marked ${isPresent ? "absent" : "present"}`);
                            }}
                          >
                            {isPresent ? "Unmark" : "Mark"}
                          </Button>
                        </div>
                      </div>
                    );
                  })}
                </div>
              </div>
            </Card>
          );
        })}
      </div>
    </div>
  );
};
const AssignmentsSection = () => {
  const { assignments, batches, students, addAssignment, addSubmission } = useInstructorDashboard();
  const [form, setForm] = useState<AssignmentFormState>({
    title: "",
    course: "Abacus",
    level: "Level 1",
    dueDate: "",
    targetType: "batch",
    targetId: batches[0]?.id || "",
    questions: "",
    autoGenerate: false,
  });
  const [recording, setRecording] = useState<{ assignmentId: string; studentId: string; score: string } | null>(null);

  const handleAddAssignment = () => {
    if (!form.title.trim() || !form.targetId) return;
    const questions = form.autoGenerate
      ? [
          "Solve 25 addition sums",
          "3-digit subtraction drill",
          "Speed round: 10 sums in 3 min",
          "2-digit multiplication",
          "Abacus visualization worksheet",
        ]
      : form.questions.split("\n").filter(Boolean);
    addAssignment({
      title: form.title,
      course: form.course,
      level: form.level,
      dueDate: form.dueDate,
      assignedTo: { type: form.targetType, id: form.targetId },
      questions,
    });
    setForm({ title: "", course: "Abacus", level: "Level 1", dueDate: "", targetType: "batch", targetId: batches[0]?.id || "", questions: "", autoGenerate: false });
  };

  const assignmentTargetLabel = (assignment: Assignment) => {
    if (assignment.assignedTo.type === "student") {
      return students.find((student) => student.id === assignment.assignedTo.id)?.name || "Student";
    }
    return batches.find((batch) => batch.id === assignment.assignedTo.id)?.name || "Batch";
  };

  return (
    <div className="space-y-6">
      <div className="flex flex-wrap items-center justify-between gap-4">
        <SectionTitle title="Assignments & Worksheets" subtitle="Create and track worksheets" />
        <Dialog>
          <DialogTrigger asChild>
            <Button className="gap-2">
              <ClipboardList className="h-4 w-4" /> Create Worksheet
            </Button>
          </DialogTrigger>
          <DialogContent className="max-w-xl">
            <DialogHeader>
              <DialogTitle>Create Worksheet</DialogTitle>
            </DialogHeader>
            <div className="space-y-3">
              <Input
                placeholder="Worksheet title"
                value={form.title}
                onChange={(e) => setForm((prev) => ({ ...prev, title: e.target.value }))}
              />
              <div className="grid grid-cols-2 gap-3">
                <Select value={form.course} onValueChange={(course: CourseType) => setForm((prev) => ({ ...prev, course }))}>
                  <SelectTrigger><SelectValue placeholder="Course" /></SelectTrigger>
                  <SelectContent>
                    <SelectItem value="Abacus">Abacus</SelectItem>
                    <SelectItem value="Vedic Maths">Vedic Maths</SelectItem>
                    <SelectItem value="Worksheet Practice">Worksheet Practice</SelectItem>
                  </SelectContent>
                </Select>
                <Input placeholder="Level (e.g. Level 1)" value={form.level} onChange={(e) => setForm((prev) => ({ ...prev, level: e.target.value }))} />
              </div>
              <Input type="date" value={form.dueDate} onChange={(e) => setForm((prev) => ({ ...prev, dueDate: e.target.value }))} />
              <Select value={form.targetType} onValueChange={(value: "student" | "batch") => setForm((prev) => ({ ...prev, targetType: value }))}>
                <SelectTrigger>
                  <SelectValue placeholder="Assign to" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="batch">Batch</SelectItem>
                  <SelectItem value="student">Student</SelectItem>
                </SelectContent>
              </Select>
              <Select value={form.targetId} onValueChange={(value) => setForm((prev) => ({ ...prev, targetId: value }))}>
                <SelectTrigger>
                  <SelectValue placeholder="Select target" />
                </SelectTrigger>
                <SelectContent>
                  {(form.targetType === "batch" ? batches : students).map((item) => (
                    <SelectItem key={item.id} value={item.id}>
                      {"name" in item ? item.name : item.name}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
              <div className="rounded-lg border border-dashed border-border p-3 text-sm text-muted-foreground">
                Auto-generate questions toggles a ready-made worksheet.
              </div>
              <div className="flex items-center gap-2">
                <input
                  type="checkbox"
                  checked={form.autoGenerate}
                  onChange={(e) => setForm((prev) => ({ ...prev, autoGenerate: e.target.checked }))}
                />
                <span className="text-sm">Auto-generate questions</span>
              </div>
              {!form.autoGenerate && (
                <Textarea
                  rows={4}
                  placeholder="Add questions (one per line)"
                  value={form.questions}
                  onChange={(e) => setForm((prev) => ({ ...prev, questions: e.target.value }))}
                />
              )}
              <Button onClick={handleAddAssignment}>Create Worksheet</Button>
            </div>
          </DialogContent>
        </Dialog>
      </div>

      <Card className="shadow-card">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Worksheet</TableHead>
              <TableHead>Course / Level</TableHead>
              <TableHead>Assigned To</TableHead>
              <TableHead>Due Date</TableHead>
              <TableHead>Submissions</TableHead>
              <TableHead className="text-right">Actions</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {assignments.map((assignment) => (
              <TableRow key={assignment.id}>
                <TableCell className="font-medium">{assignment.title}</TableCell>
                <TableCell>{assignment.course || "Abacus"} / {assignment.level || "Level 1"}</TableCell>
                <TableCell>{assignmentTargetLabel(assignment)}</TableCell>
                <TableCell>{formatDate(assignment.dueDate)}</TableCell>
                <TableCell>{assignment.submissions.length}</TableCell>
                <TableCell className="text-right">
                  <Button
                    variant="outline"
                    size="sm"
                    onClick={() => setRecording({ assignmentId: assignment.id, studentId: "", score: "" })}
                  >
                    Record Submission
                  </Button>
                </TableCell>
              </TableRow>
            ))}
          </TableBody>
        </Table>
      </Card>

      <Dialog open={!!recording} onOpenChange={(open) => !open && setRecording(null)}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Record Submission</DialogTitle>
          </DialogHeader>
          {recording && (
            <div className="space-y-3">
              <Select value={recording.studentId} onValueChange={(value) => setRecording({ ...recording, studentId: value })}>
                <SelectTrigger>
                  <SelectValue placeholder="Select student" />
                </SelectTrigger>
                <SelectContent>
                  {students.map((student) => (
                    <SelectItem key={student.id} value={student.id}>
                      {student.name}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
              <Input
                placeholder="Score"
                value={recording.score}
                onChange={(e) => setRecording({ ...recording, score: e.target.value })}
              />
              <Button
                onClick={() => {
                  if (!recording.studentId || !recording.score) return;
                  addSubmission(recording.assignmentId, recording.studentId, Number(recording.score));
                  setRecording(null);
                }}
              >
                Save Submission
              </Button>
            </div>
          )}
        </DialogContent>
      </Dialog>
    </div>
  );
};

const FeesSection = () => {
  const { payments, students, addPayment } = useInstructorDashboard();
  const [form, setForm] = useState<PaymentFormState>({
    studentId: students[0]?.id || "",
    amount: "",
    method: "UPI",
    status: "paid",
  });

  const handleAddPayment = () => {
    if (!form.studentId || !form.amount) return;
    addPayment({
      studentId: form.studentId,
      amount: Number(form.amount),
      date: new Date().toISOString().slice(0, 10),
      method: form.method,
      status: form.status,
    });
    setForm({ studentId: students[0]?.id || "", amount: "", method: "UPI", status: "paid" });
  };

  return (
    <div className="space-y-6">
      <div className="flex flex-wrap items-center justify-between gap-4">
        <SectionTitle title="Fees Management" subtitle="Track paid and unpaid fees" />
        <Dialog>
          <DialogTrigger asChild>
            <Button className="gap-2">
              <Wallet className="h-4 w-4" /> Add Payment
            </Button>
          </DialogTrigger>
          <DialogContent>
            <DialogHeader>
              <DialogTitle>Add Payment Entry</DialogTitle>
            </DialogHeader>
            <div className="space-y-3">
              <Select value={form.studentId} onValueChange={(value) => setForm((prev) => ({ ...prev, studentId: value }))}>
                <SelectTrigger>
                  <SelectValue placeholder="Select student" />
                </SelectTrigger>
                <SelectContent>
                  {students.map((student) => (
                    <SelectItem key={student.id} value={student.id}>
                      {student.name}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
              <Input
                placeholder="Amount"
                value={form.amount}
                onChange={(e) => setForm((prev) => ({ ...prev, amount: e.target.value }))}
              />
              <Input
                placeholder="Payment method"
                value={form.method}
                onChange={(e) => setForm((prev) => ({ ...prev, method: e.target.value }))}
              />
              <Select value={form.status} onValueChange={(value: FeesStatus) => setForm((prev) => ({ ...prev, status: value }))}>
                <SelectTrigger>
                  <SelectValue placeholder="Status" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="paid">Paid</SelectItem>
                  <SelectItem value="unpaid">Unpaid</SelectItem>
                </SelectContent>
              </Select>
              <Button onClick={handleAddPayment}>Save Payment</Button>
            </div>
          </DialogContent>
        </Dialog>
      </div>

      <Card className="shadow-card">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Student</TableHead>
              <TableHead>Amount</TableHead>
              <TableHead>Date</TableHead>
              <TableHead>Status</TableHead>
              <TableHead>Method</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {payments.map((payment) => (
              <TableRow key={payment.id}>
                <TableCell>{students.find((student) => student.id === payment.studentId)?.name}</TableCell>
                <TableCell>?{payment.amount}</TableCell>
                <TableCell>{formatDate(payment.date)}</TableCell>
                <TableCell>
                  <span className={`px-2 py-1 rounded-full text-xs font-medium ${feeBadge(payment.status)}`}>
                    {feeStatusLabel(payment.status)}
                  </span>
                </TableCell>
                <TableCell>{payment.method}</TableCell>
              </TableRow>
            ))}
          </TableBody>
        </Table>
      </Card>
    </div>
  );
};

const MaterialsSection = () => {
  const { materials, batches, addMaterial } = useInstructorDashboard();
  const [form, setForm] = useState<MaterialFormState>({ title: "", course: "Abacus", level: "Level 1", type: "pdf", url: "", batchId: batches[0]?.id || "" });
  const [materialFileName, setMaterialFileName] = useState("");
  const [materialError, setMaterialError] = useState("");
  const [isPdfLoading, setIsPdfLoading] = useState(false);
  const [isDialogOpen, setIsDialogOpen] = useState(false);

  const handleUpload = () => {
    setMaterialError("");
    if (!form.title.trim()) {
      setMaterialError("Please enter a title.");
      return;
    }
    if (form.type === "pdf" && !form.url) {
      setMaterialError("Please upload a PDF file.");
      return;
    }
    if (form.type === "video" && !form.url.trim()) {
      setMaterialError("Please enter a video URL.");
      return;
    }
    addMaterial({ title: form.title, course: form.course, level: form.level, type: form.type, url: form.url || "https://example.com/material", batchId: form.batchId });
    setForm({ title: "", course: "Abacus", level: "Level 1", type: "pdf", url: "", batchId: batches[0]?.id || "" });
    setMaterialFileName("");
    setMaterialError("");
    setIsDialogOpen(false);
  };
 
  const handlePdfUpload = (event: React.ChangeEvent<HTMLInputElement>) => {
    const file = event.target.files?.[0];
    if (!file) return;
    setMaterialFileName(file.name);
    setIsPdfLoading(true);
    setMaterialError("");
    const reader = new FileReader();
    reader.onload = () => {
      const result = typeof reader.result === "string" ? reader.result : "";
      setForm((prev) => ({ ...prev, url: result }));
      setIsPdfLoading(false);
    };
    reader.onerror = () => {
      setMaterialError("Failed to read the PDF. Please try another file.");
      setIsPdfLoading(false);
    };
    reader.readAsDataURL(file);
  };

  return (
    <div className="space-y-6">
      <div className="flex flex-wrap items-center justify-between gap-4">
        <SectionTitle title="Study Material" subtitle="Upload and assign learning resources" />
        <Dialog open={isDialogOpen} onOpenChange={setIsDialogOpen}>
          <DialogTrigger asChild>
            <Button className="gap-2">
              <Upload className="h-4 w-4" /> Upload Material
            </Button>
          </DialogTrigger>
          <DialogContent>
            <DialogHeader>
              <DialogTitle>Upload Material</DialogTitle>
            </DialogHeader>
            <div className="space-y-3">
              <Input placeholder="Title" value={form.title} onChange={(e) => setForm((prev) => ({ ...prev, title: e.target.value }))} />
              <div className="grid grid-cols-2 gap-3">
                <Select value={form.course} onValueChange={(course: CourseType) => setForm((prev) => ({ ...prev, course }))}>
                  <SelectTrigger><SelectValue placeholder="Course" /></SelectTrigger>
                  <SelectContent>
                    <SelectItem value="Abacus">Abacus</SelectItem>
                    <SelectItem value="Vedic Maths">Vedic Maths</SelectItem>
                    <SelectItem value="Worksheet Practice">Worksheet Practice</SelectItem>
                  </SelectContent>
                </Select>
                <Input placeholder="Level (e.g. Level 1)" value={form.level} onChange={(e) => setForm((prev) => ({ ...prev, level: e.target.value }))} />
              </div>
              <Select
                value={form.type}
                onValueChange={(value: "pdf" | "video") =>
                  setForm((prev) => ({ ...prev, type: value, url: "" }))
                }
              >
                <SelectTrigger>
                  <SelectValue placeholder="Type" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="pdf">PDF</SelectItem>
                  <SelectItem value="video">Video</SelectItem>
                </SelectContent>
              </Select>
              {form.type === "pdf" ? (
                <div className="space-y-2">
                  <Input type="file" accept="application/pdf" onChange={handlePdfUpload} />
                  {materialFileName ? (
                    <p className="text-xs text-muted-foreground">Selected: {materialFileName}</p>
                  ) : (
                    <p className="text-xs text-muted-foreground">Upload a PDF file (max ~5MB recommended).</p>
                  )}
                  {isPdfLoading ? <p className="text-xs text-muted-foreground">Loading PDF...</p> : null}
                </div>
              ) : (
                <Input
                  placeholder="Video URL"
                  value={form.url}
                  onChange={(e) => setForm((prev) => ({ ...prev, url: e.target.value }))}
                />
              )}
              {materialError ? <p className="text-sm text-rose-600">{materialError}</p> : null}
              <Select value={form.batchId} onValueChange={(value) => setForm((prev) => ({ ...prev, batchId: value }))}>
                <SelectTrigger>
                  <SelectValue placeholder="Assign batch" />
                </SelectTrigger>
                <SelectContent>
                  {batches.map((batch) => (
                    <SelectItem key={batch.id} value={batch.id}>
                      {batch.name}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
              <Button onClick={handleUpload} disabled={isPdfLoading}>
                Upload
              </Button>
            </div>
          </DialogContent>
        </Dialog>
      </div>

      <div className="grid gap-4 md:grid-cols-2">
        {materials.map((material) => (
          <Card key={material.id} className="p-5 shadow-card space-y-3">
            <div className="flex items-center justify-between">
              <p className="font-semibold">{material.title}</p>
              <Badge variant="outline">{material.type.toUpperCase()}</Badge>
            </div>
            <p className="text-sm text-muted-foreground">
              Batch: {batches.find((batch) => batch.id === material.batchId)?.name}
            </p>
            <div className="flex items-center justify-between text-sm">
              <span className="text-muted-foreground">Uploaded {formatDate(material.uploadedAt)}</span>
              <Button
                variant="ghost"
                size="sm"
                className="gap-2"
                onClick={() => {
                  if (material.url) window.open(material.url, "_blank", "noopener,noreferrer");
                }}
              >
                <Link2 className="h-4 w-4" /> Open
              </Button>
            </div>
          </Card>
        ))}
      </div>
    </div>
  );
};
const CommunicationSection = () => {
  const { announcements, addAnnouncement, students } = useInstructorDashboard();
  const [form, setForm] = useState<AnnouncementFormState>({ title: "", message: "" });
  const [message, setMessage] = useState({ studentId: students[0]?.id || "", text: "" });

  const handleSendAnnouncement = () => {
    if (!form.title.trim() || !form.message.trim()) return;
    addAnnouncement({ title: form.title, message: form.message });
    setForm({ title: "", message: "" });
  };

  return (
    <div className="grid gap-6 lg:grid-cols-[1.2fr_0.8fr]">
      <div className="space-y-6">
        <SectionTitle title="Communication" subtitle="Announcements and student messages" />
        <Card className="p-6 shadow-card space-y-4">
          <h3 className="text-lg font-semibold">Send Announcement</h3>
          <Input placeholder="Title" value={form.title} onChange={(e) => setForm((prev) => ({ ...prev, title: e.target.value }))} />
          <Textarea
            placeholder="Message"
            rows={4}
            value={form.message}
            onChange={(e) => setForm((prev) => ({ ...prev, message: e.target.value }))}
          />
          <Button className="gap-2" onClick={handleSendAnnouncement}>
            <Bell className="h-4 w-4" /> Send Announcement
          </Button>
        </Card>

        <Card className="p-6 shadow-card space-y-4">
          <h3 className="text-lg font-semibold">Message Students</h3>
          <Select value={message.studentId} onValueChange={(value) => setMessage((prev) => ({ ...prev, studentId: value }))}>
            <SelectTrigger>
              <SelectValue placeholder="Select student" />
            </SelectTrigger>
            <SelectContent>
              {students.map((student) => (
                <SelectItem key={student.id} value={student.id}>
                  {student.name}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
          <Textarea
            placeholder="Type your message"
            rows={3}
            value={message.text}
            onChange={(e) => setMessage((prev) => ({ ...prev, text: e.target.value }))}
          />
          <Button variant="outline" className="gap-2">
            <Mail className="h-4 w-4" /> Send Message
          </Button>
        </Card>
      </div>

      <Card className="p-6 shadow-card space-y-4">
        <div className="flex items-center justify-between">
          <h3 className="text-lg font-semibold">Notification Panel</h3>
          <Badge variant="outline">{announcements.length}</Badge>
        </div>
        <div className="space-y-4">
          {announcements.map((announcement) => (
            <div key={announcement.id} className="rounded-lg border border-border p-3">
              <p className="font-medium">{announcement.title}</p>
              <p className="text-sm text-muted-foreground mt-1">{announcement.message}</p>
              <p className="text-xs text-muted-foreground mt-2">{formatDate(announcement.sentAt)}</p>
            </div>
          ))}
        </div>
      </Card>
    </div>
  );
};

const CoursesSection = () => {
  const { batches, assignments, materials } = useInstructorDashboard();
  const rows = [
    { name: "Abacus", levels: "Level 1 to Level 7" },
    { name: "Vedic Maths", levels: "Level 1 to Level 4" },
    { name: "Worksheet Practice", levels: "Abacus and Vedic Maths" },
  ].map((course) => ({
    ...course,
    topics: assignments.filter((item) => (item.course || "Abacus") === course.name).length,
    materials: materials.filter((item) => (item.course || "Abacus") === course.name).length,
  }));

  return (
    <div className="space-y-6">
      <SectionTitle title="Courses" subtitle="Course types, levels, topics, and assigned batches" />
      <div className="grid gap-4 lg:grid-cols-3">
        {rows.map((course) => (
          <Card key={course.name} className="p-5 shadow-card">
            <h3 className="text-lg font-semibold text-slate-900">{course.name}</h3>
            <p className="mt-2 text-sm text-slate-500">{course.levels}</p>
            <div className="mt-4 grid grid-cols-2 gap-3 text-sm">
              <div className="rounded-lg bg-slate-50 p-3">
                <p className="text-slate-500">Topics</p>
                <p className="text-lg font-semibold">{course.topics}</p>
              </div>
              <div className="rounded-lg bg-slate-50 p-3">
                <p className="text-slate-500">Materials</p>
                <p className="text-lg font-semibold">{course.materials}</p>
              </div>
            </div>
          </Card>
        ))}
      </div>
      <Card className="p-5 shadow-card">
        <h3 className="mb-4 text-base font-semibold">Batches By Course Level</h3>
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Batch</TableHead>
              <TableHead>Course</TableHead>
              <TableHead>Level</TableHead>
              <TableHead>Students</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {batches.map((batch) => (
              <TableRow key={batch.id}>
                <TableCell>{batch.name}</TableCell>
                <TableCell>{batch.course || "Abacus"}</TableCell>
                <TableCell>{batch.level}</TableCell>
                <TableCell>{batch.studentIds.length}</TableCell>
              </TableRow>
            ))}
          </TableBody>
        </Table>
      </Card>
    </div>
  );
};

const EnquiriesSection = () => {
  const [enquiries, setEnquiries] = usePersistentState("instructor_enquiries_v1", [
    { id: "enq-1", name: "Parent enquiry", phone: "9876543210", course: "Abacus", status: "New" },
  ]);
  const [form, setForm] = useState({ name: "", phone: "", course: "Abacus", status: "New" });

  const addEnquiry = () => {
    if (!form.name.trim() || !form.phone.trim()) {
      toast.error("Please enter name and phone.");
      return;
    }
    setEnquiries((prev) => [{ id: dashboardId(), ...form }, ...prev]);
    setForm({ name: "", phone: "", course: "Abacus", status: "New" });
    toast.success("Enquiry added.");
  };

  return (
    <div className="space-y-6">
      <SectionTitle title="Enquiries" subtitle="Capture and track student enquiries" />
      <Card className="grid gap-3 p-5 shadow-card md:grid-cols-5">
        <Input placeholder="Name" value={form.name} onChange={(event) => setForm((prev) => ({ ...prev, name: event.target.value }))} />
        <Input placeholder="Phone" value={form.phone} onChange={(event) => setForm((prev) => ({ ...prev, phone: event.target.value }))} />
        <Select value={form.course} onValueChange={(course) => setForm((prev) => ({ ...prev, course }))}>
          <SelectTrigger><SelectValue /></SelectTrigger>
          <SelectContent>
            <SelectItem value="Abacus">Abacus</SelectItem>
            <SelectItem value="Vedic Maths">Vedic Maths</SelectItem>
            <SelectItem value="Worksheets">Worksheets</SelectItem>
          </SelectContent>
        </Select>
        <Select value={form.status} onValueChange={(status) => setForm((prev) => ({ ...prev, status }))}>
          <SelectTrigger><SelectValue /></SelectTrigger>
          <SelectContent>
            <SelectItem value="New">New</SelectItem>
            <SelectItem value="Follow Up">Follow Up</SelectItem>
            <SelectItem value="Converted">Converted</SelectItem>
          </SelectContent>
        </Select>
        <Button onClick={addEnquiry}>Add Enquiry</Button>
      </Card>
      <Card className="shadow-card">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Name</TableHead>
              <TableHead>Phone</TableHead>
              <TableHead>Course</TableHead>
              <TableHead>Status</TableHead>
              <TableHead>Action</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {enquiries.map((enquiry) => (
              <TableRow key={enquiry.id}>
                <TableCell>{enquiry.name}</TableCell>
                <TableCell>{enquiry.phone}</TableCell>
                <TableCell>{enquiry.course}</TableCell>
                <TableCell>{enquiry.status}</TableCell>
                <TableCell>
                  <Button size="sm" variant="outline" onClick={() => setEnquiries((prev) => prev.filter((item) => item.id !== enquiry.id))}>
                    Close
                  </Button>
                </TableCell>
              </TableRow>
            ))}
          </TableBody>
        </Table>
      </Card>
    </div>
  );
};

const PromoteSection = () => {
  const [profile, setProfile] = usePersistentState("instructor_promotion_v1", {
    headline: "Certified Abacus Instructor",
    city: "Pune",
    phone: "",
    message: "Join my abacus and Vedic Maths classes for faster calculation practice.",
  });

  return (
    <div className="grid gap-6 lg:grid-cols-[1fr_1fr]">
      <Card className="space-y-4 p-6 shadow-card">
        <SectionTitle title="Promote Yourself" subtitle="Create a simple instructor promotion card" />
        <Input placeholder="Headline" value={profile.headline} onChange={(event) => setProfile((prev) => ({ ...prev, headline: event.target.value }))} />
        <Input placeholder="City" value={profile.city} onChange={(event) => setProfile((prev) => ({ ...prev, city: event.target.value }))} />
        <Input placeholder="Contact number" value={profile.phone} onChange={(event) => setProfile((prev) => ({ ...prev, phone: event.target.value }))} />
        <Textarea rows={4} placeholder="Promotion message" value={profile.message} onChange={(event) => setProfile((prev) => ({ ...prev, message: event.target.value }))} />
      </Card>
      <Card className="p-6 shadow-card">
        <p className="text-sm text-slate-500">Preview</p>
        <h3 className="mt-3 text-2xl font-semibold text-slate-950">{profile.headline}</h3>
        <p className="mt-2 text-sm text-slate-500">{profile.city}</p>
        <p className="mt-4 text-slate-700">{profile.message}</p>
        <Button className="mt-6" onClick={() => navigator.clipboard?.writeText(`${profile.headline}\n${profile.city}\n${profile.message}\n${profile.phone}`)}>
          Copy Promotion Text
        </Button>
      </Card>
    </div>
  );
};

const CertificatesSection = () => {
  const { students } = useInstructorDashboard();
  const [records, setRecords] = usePersistentState<Array<{ id: string; studentId: string; level: string; fileName: string; uploadedAt: string }>>(
    "instructor_certificates_v1",
    [],
  );
  const [form, setForm] = useState({ studentId: students[0]?.id || "", level: "Level 1", fileName: "" });
  const [fileInputKey, setFileInputKey] = useState(0);

  const addCertificate = () => {
    if (!form.studentId) {
      toast.error("Please select a student.");
      return;
    }
    if (!form.level.trim()) {
      toast.error("Please enter certificate level.");
      return;
    }
    if (!form.fileName) {
      toast.error("Please choose a certificate file before upload.");
      return;
    }
    setRecords((prev) => [{ id: dashboardId(), ...form, uploadedAt: new Date().toISOString().slice(0, 10) }, ...prev]);
    setForm((prev) => ({ ...prev, fileName: "" }));
    setFileInputKey((prev) => prev + 1);
    toast.success("Certificate uploaded.");
  };

  return (
    <div className="space-y-6">
      <SectionTitle title="Upload Certificate" subtitle="Attach completion certificates to students" />
      <Card className="grid gap-3 p-5 shadow-card md:grid-cols-4">
        <Select value={form.studentId} onValueChange={(studentId) => setForm((prev) => ({ ...prev, studentId }))}>
          <SelectTrigger><SelectValue placeholder="Student" /></SelectTrigger>
          <SelectContent>
            {students.map((student) => <SelectItem key={student.id} value={student.id}>{student.name}</SelectItem>)}
          </SelectContent>
        </Select>
        <Input placeholder="Level" value={form.level} onChange={(event) => setForm((prev) => ({ ...prev, level: event.target.value }))} />
        <Input key={fileInputKey} type="file" accept="application/pdf,image/*" onChange={(event) => setForm((prev) => ({ ...prev, fileName: event.target.files?.[0]?.name || "" }))} />
        <Button onClick={addCertificate}>Upload</Button>
      </Card>
      {form.fileName ? <p className="text-sm text-slate-600">Selected file: {form.fileName}</p> : null}
      <Card className="shadow-card">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Student</TableHead>
              <TableHead>Level</TableHead>
              <TableHead>File</TableHead>
              <TableHead>Uploaded</TableHead>
              <TableHead>Action</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {records.map((record) => (
              <TableRow key={record.id}>
                <TableCell>{students.find((student) => student.id === record.studentId)?.name}</TableCell>
                <TableCell>{record.level}</TableCell>
                <TableCell>{record.fileName}</TableCell>
                <TableCell>{formatDate(record.uploadedAt)}</TableCell>
                <TableCell>
                  <Button size="sm" variant="outline" onClick={() => setRecords((prev) => prev.filter((item) => item.id !== record.id))}>
                    Remove
                  </Button>
                </TableCell>
              </TableRow>
            ))}
            {!records.length && <TableRow><TableCell colSpan={5} className="py-6 text-center text-slate-500">No certificates uploaded</TableCell></TableRow>}
          </TableBody>
        </Table>
      </Card>
    </div>
  );
};

const FeedbackSection = () => {
  const [feedback, setFeedback] = usePersistentState<Array<{ id: string; name: string; rating: string; message: string }>>("instructor_feedback_v1", []);
  const [form, setForm] = useState({ name: "", rating: "5", message: "" });

  const addFeedback = () => {
    if (!form.name.trim() || !form.message.trim()) {
      toast.error("Please enter name and feedback.");
      return;
    }
    setFeedback((prev) => [{ id: dashboardId(), ...form }, ...prev]);
    setForm({ name: "", rating: "5", message: "" });
    toast.success("Feedback saved.");
  };

  return (
    <div className="grid gap-6 lg:grid-cols-[0.9fr_1.1fr]">
      <Card className="space-y-4 p-6 shadow-card">
        <SectionTitle title="FeedBack" subtitle="Record parent or student feedback" />
        <Input placeholder="Name" value={form.name} onChange={(event) => setForm((prev) => ({ ...prev, name: event.target.value }))} />
        <Select value={form.rating} onValueChange={(rating) => setForm((prev) => ({ ...prev, rating }))}>
          <SelectTrigger><SelectValue /></SelectTrigger>
          <SelectContent>
            {["5", "4", "3", "2", "1"].map((rating) => <SelectItem key={rating} value={rating}>{rating} Stars</SelectItem>)}
          </SelectContent>
        </Select>
        <Textarea rows={4} placeholder="Feedback" value={form.message} onChange={(event) => setForm((prev) => ({ ...prev, message: event.target.value }))} />
        <Button onClick={addFeedback}>Save Feedback</Button>
      </Card>
      <div className="space-y-3">
        {feedback.map((item) => (
          <Card key={item.id} className="p-4 shadow-card">
            <div className="flex items-center justify-between">
              <p className="font-semibold">{item.name}</p>
              <Badge variant="outline">{item.rating} Stars</Badge>
            </div>
            <p className="mt-2 text-sm text-slate-600">{item.message}</p>
          </Card>
        ))}
        {!feedback.length && <Card className="p-6 text-center text-slate-500 shadow-card">No feedback saved</Card>}
      </div>
    </div>
  );
};

const SlotsSection = () => {
  const { batches, scheduleClass } = useInstructorDashboard();
  const [slot, setSlot] = useState({ batchId: batches[0]?.id || "", topic: "", date: "", time: "", meetingLink: "" });

  const addSlot = () => {
    if (!slot.batchId || !slot.topic || !slot.date || !slot.time) {
      toast.error("Please select batch, topic, date, and time.");
      return;
    }
    scheduleClass(slot);
    setSlot((prev) => ({ ...prev, topic: "", date: "", time: "", meetingLink: "" }));
    toast.success("Slot added to timetable.");
  };

  return (
    <Card className="space-y-4 p-6 shadow-card">
      <SectionTitle title="Available Slots" subtitle="Create timetable slots for batches" />
      <div className="grid gap-3 md:grid-cols-5">
        <Select value={slot.batchId} onValueChange={(batchId) => setSlot((prev) => ({ ...prev, batchId }))}>
          <SelectTrigger><SelectValue placeholder="Batch" /></SelectTrigger>
          <SelectContent>
            {batches.map((batch) => <SelectItem key={batch.id} value={batch.id}>{batch.name}</SelectItem>)}
          </SelectContent>
        </Select>
        <Input placeholder="Topic" value={slot.topic} onChange={(event) => setSlot((prev) => ({ ...prev, topic: event.target.value }))} />
        <Input type="date" value={slot.date} onChange={(event) => setSlot((prev) => ({ ...prev, date: event.target.value }))} />
        <Input type="time" value={slot.time} onChange={(event) => setSlot((prev) => ({ ...prev, time: event.target.value }))} />
        <Button onClick={addSlot}>Add Slot</Button>
      </div>
      <Input placeholder="Meeting link" value={slot.meetingLink} onChange={(event) => setSlot((prev) => ({ ...prev, meetingLink: event.target.value }))} />
    </Card>
  );
};

const ProfileSection = () => {
  const { profile, updateProfile } = useInstructorDashboard();
  const [name, setName] = useState(profile.name);
  const [email, setEmail] = useState(profile.email);
  const [loginId, setLoginId] = useState(profile.email);
  const [passwordForm, setPasswordForm] = useState({ current: "", next: "", confirm: "" });
  const [passwordError, setPasswordError] = useState("");
  const [avatarPreview, setAvatarPreview] = useState<string | null>(profile.avatarUrl);
  const [avatarFile, setAvatarFile] = useState<File | null>(null);
  const [profileSaved, setProfileSaved] = useState(false);
  const [isSavingProfile, setIsSavingProfile] = useState(false);

  useEffect(() => {
    setName(profile.name);
    setEmail(profile.email);
    setLoginId(profile.email);
    setAvatarPreview(profile.avatarUrl);
  }, [profile.name, profile.email, profile.avatarUrl]);

  const handleAvatarChange = (event: React.ChangeEvent<HTMLInputElement>) => {
    const file = event.target.files?.[0];
    if (!file) return;
    if (file.size > 2 * 1024 * 1024) {
      toast.error("Profile picture must be 2MB or smaller.");
      event.target.value = "";
      return;
    }
    setAvatarFile(file);
    const reader = new FileReader();
    reader.onload = () => {
      const result = typeof reader.result === "string" ? reader.result : null;
      setAvatarPreview(result);
    };
    reader.readAsDataURL(file);
  };

  const handleProfileSave = async () => {
    const nextName = name.trim();
    const nextEmail = email.trim();
    if (!nextName) {
      toast.error("Please enter your full name.");
      return;
    }
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(nextEmail)) {
      toast.error("Please enter a valid email address.");
      return;
    }

    try {
      setIsSavingProfile(true);
      await updateProfile({ name: nextName, email: nextEmail, avatarUrl: avatarPreview }, avatarFile);
      setLoginId(nextEmail);
      setAvatarFile(null);
      setProfileSaved(true);
      toast.success("Profile saved successfully.");
      window.setTimeout(() => setProfileSaved(false), 2200);
    } catch (error) {
      toast.error(error instanceof Error ? error.message : "Profile could not be saved.");
    } finally {
      setIsSavingProfile(false);
    }
  };

  const handlePasswordUpdate = () => {
    setPasswordError("");
    if (!passwordForm.current && !passwordForm.next && !passwordForm.confirm) return;
    if (passwordForm.next !== passwordForm.confirm) {
      setPasswordError("New password and confirmation do not match.");
      return;
    }
    setPasswordForm({ current: "", next: "", confirm: "" });
  };

  return (
    <div className="space-y-6">
      <SectionTitle title="Profile Settings" subtitle="Manage your instructor profile" />
      <div className="grid gap-6 lg:grid-cols-[1.1fr_0.9fr] max-w-4xl">
        <Card className="p-6 shadow-card space-y-6">
          <div className="flex items-center gap-4">
            <div className="h-16 w-16 rounded-full bg-primary/10 flex items-center justify-center overflow-hidden">
              {avatarPreview ? (
                <img src={avatarPreview} alt="Profile" className="h-full w-full object-cover" />
              ) : (
                <GraduationCap className="h-6 w-6 text-primary" />
              )}
            </div>
            <div>
              <p className="text-lg font-semibold">{profile.name}</p>
              <p className="text-sm text-muted-foreground">Instructor</p>
            </div>
          </div>

          <div className="space-y-4">
            <h3 className="text-base font-semibold">Profile Info</h3>
            <Input value={name} onChange={(e) => setName(e.target.value)} placeholder="Full Name" />
            <Input value={email} onChange={(e) => setEmail(e.target.value)} placeholder="Email Address" />
          </div>

          <div className="space-y-3">
            <h3 className="text-base font-semibold">Profile Picture</h3>
            <Input type="file" accept="image/*" onChange={handleAvatarChange} />
            <p className="text-xs text-muted-foreground">PNG, JPG or WebP up to 2MB. Square images work best.</p>
          </div>

          <div className="flex flex-wrap items-center gap-3">
            <Button onClick={handleProfileSave} disabled={isSavingProfile}>
              {isSavingProfile ? "Saving..." : profileSaved ? "Saved" : "Save Profile"}
            </Button>
            {profileSaved ? (
              <span className="inline-flex items-center gap-1.5 text-sm font-medium text-emerald-700">
                <CheckCircle2 className="h-4 w-4" />
                Profile updated
              </span>
            ) : null}
          </div>
        </Card>

        <div className="space-y-6">
          <Card className="p-6 shadow-card space-y-4">
            <h3 className="text-base font-semibold">Login Details</h3>
            <Input value={loginId} readOnly className="bg-slate-50" />
            <div className="flex items-center justify-between rounded-lg border border-border px-4 py-3">
              <div>
                <p className="text-sm font-medium text-foreground">Role</p>
                <p className="text-xs text-muted-foreground">Instructor</p>
              </div>
              <Badge variant="outline" className="border-primary/30 text-primary">
                Active
              </Badge>
            </div>
          </Card>

          <Card className="p-6 shadow-card space-y-4">
            <h3 className="text-base font-semibold">Change Password</h3>
            <Input
              type="password"
              placeholder="Current Password"
              value={passwordForm.current}
              onChange={(e) => setPasswordForm((prev) => ({ ...prev, current: e.target.value }))}
            />
            <Input
              type="password"
              placeholder="New Password"
              value={passwordForm.next}
              onChange={(e) => setPasswordForm((prev) => ({ ...prev, next: e.target.value }))}
            />
            <Input
              type="password"
              placeholder="Confirm New Password"
              value={passwordForm.confirm}
              onChange={(e) => setPasswordForm((prev) => ({ ...prev, confirm: e.target.value }))}
            />
            {passwordError ? <p className="text-sm text-rose-600">{passwordError}</p> : null}
            <Button variant="outline" onClick={handlePasswordUpdate}>
              Update Password
            </Button>
          </Card>
        </div>
      </div>
    </div>
  );
};

const formatDuration = (seconds: number) => {
  const safe = Math.max(0, Math.floor(seconds || 0));
  const minutes = Math.floor(safe / 60);
  const rest = safe % 60;
  return `${minutes}:${rest.toString().padStart(2, "0")}`;
};

const programLabel = (program: string) => (program === "vedic_maths" ? "Vedic Maths Training" : "Abacus Training");

const TrainingVideosSection = ({ token }: { token: string | null }) => {
  const [dashboard, setDashboard] = useState<InstructorVideoDashboard | null>(null);
  const [selectedVideo, setSelectedVideo] = useState<InstructorTrainingVideo | null>(null);
  const [playbackUrl, setPlaybackUrl] = useState("");
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [watermark, setWatermark] = useState({ x: 8, y: 10 });
  const [nowText, setNowText] = useState(() => new Date().toLocaleString());
  const videoRef = useRef<HTMLVideoElement | null>(null);
  const sessionIdRef = useRef(dashboardId());
  const maxWatchedRef = useRef(0);
  const segmentStartRef = useRef<number | null>(null);
  const pendingSegmentsRef = useRef<Array<{ start: number; end: number }>>([]);

  const loadDashboard = async () => {
    if (!token) return;
    setLoading(true);
    try {
      const response = await getInstructorVideoDashboard(token);
      setDashboard(response);
      if (selectedVideo) {
        const updated = response.library.videos.find((video) => video.id === selectedVideo.id);
        if (updated) setSelectedVideo(updated);
      }
    } catch (error) {
      toast.error(error instanceof Error ? error.message : "Training videos could not be loaded.");
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    void loadDashboard();
  }, [token]);

  useEffect(() => {
    const timer = window.setInterval(() => {
      setNowText(new Date().toLocaleString());
      setWatermark((prev) => ({
        x: (prev.x + 23 + Math.round(Math.random() * 17)) % 72,
        y: (prev.y + 19 + Math.round(Math.random() * 11)) % 68,
      }));
    }, 4000);
    return () => window.clearInterval(timer);
  }, []);

  const flushProgress = async () => {
    if (!token || !selectedVideo || !videoRef.current || saving) return;
    const current = Math.floor(videoRef.current.currentTime || 0);
    const duration = Math.floor(videoRef.current.duration || selectedVideo.durationSeconds || 0);
    const openStart = segmentStartRef.current;
    if (openStart !== null && current > openStart) {
      pendingSegmentsRef.current.push({ start: Math.floor(openStart), end: current });
      segmentStartRef.current = current;
    }
    const segments = pendingSegmentsRef.current.splice(0);
    if (!segments.length && current === selectedVideo.progress.currentPositionSeconds) return;

    setSaving(true);
    try {
      const response = await saveInstructorVideoProgress(token, selectedVideo.id, {
        sessionId: sessionIdRef.current,
        currentPositionSeconds: current,
        maximumWatchedPositionSeconds: Math.floor(maxWatchedRef.current),
        durationSeconds: duration,
        segments,
      });
      setDashboard((prev) => (prev ? { ...prev, library: response.library } : prev));
      const updated = response.library.videos.find((video) => video.id === selectedVideo.id);
      if (updated) setSelectedVideo(updated);
    } catch (error) {
      pendingSegmentsRef.current.unshift(...segments);
      toast.error(error instanceof Error ? error.message : "Progress could not be saved.");
    } finally {
      setSaving(false);
    }
  };

  useEffect(() => {
    const timer = window.setInterval(() => void flushProgress(), 12000);
    const handler = () => void flushProgress();
    window.addEventListener("beforeunload", handler);
    return () => {
      window.clearInterval(timer);
      window.removeEventListener("beforeunload", handler);
      void flushProgress();
    };
  }, [token, selectedVideo?.id]);

  const openVideo = async (video: InstructorTrainingVideo) => {
    if (!token || !video.isUnlocked) return;
    try {
      await flushProgress();
      const response = await requestInstructorVideoPlayback(token, video.id);
      sessionIdRef.current = dashboardId();
      pendingSegmentsRef.current = [];
      segmentStartRef.current = null;
      maxWatchedRef.current = response.video.progress.maximumWatchedPositionSeconds || response.video.progress.currentPositionSeconds || 0;
      setSelectedVideo(response.video);
      setPlaybackUrl(response.playbackUrl);
    } catch (error) {
      toast.error(error instanceof Error ? error.message : "Video access denied.");
    }
  };

  const groupedVideos = useMemo(() => {
    const groups = new Map<string, InstructorTrainingVideo[]>();
    (dashboard?.library.videos || []).forEach((video) => {
      const key = `${video.program}__${video.level}`;
      groups.set(key, [...(groups.get(key) || []), video]);
    });
    return Array.from(groups.entries()).map(([key, videos]) => ({ key, videos }));
  }, [dashboard?.library.videos]);

  if (loading) {
    return <Card className="p-6 shadow-card text-sm text-slate-500">Loading training videos...</Card>;
  }

  const subscription = dashboard?.subscription;
  if (!subscription?.hasAccess) {
    const expired = subscription?.state === "expired";
    return (
      <Card className="max-w-2xl p-6 shadow-card">
        <SectionTitle title="Training Videos" subtitle="Offline activated 90-day training access" />
        <div className="mt-5 rounded-md border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
          <p className="font-semibold">
            {expired ? "Your 90-day Training Video subscription has expired." : "Your Training Video subscription is not active."}
          </p>
          <p className="mt-1">Please contact the administrator {expired ? "for renewal." : "to activate your 90-day access."}</p>
        </div>
      </Card>
    );
  }

  return (
    <div className="space-y-6">
      <div className="grid gap-4 lg:grid-cols-4">
        <Card className="p-5 shadow-card lg:col-span-2">
          <SectionTitle title="Training Videos" subtitle="Sequential Abacus and Vedic Maths instructor lessons" />
          <div className="mt-4 grid gap-3 text-sm sm:grid-cols-2">
            <div><span className="text-slate-500">Plan</span><p className="font-semibold">{subscription.subscription?.planName}</p></div>
            <div><span className="text-slate-500">Remaining Days</span><p className="font-semibold">{subscription.subscription?.remainingDays}</p></div>
            <div><span className="text-slate-500">Start Date</span><p className="font-semibold">{formatDate(subscription.subscription?.startDate || "")}</p></div>
            <div><span className="text-slate-500">Expiry Date</span><p className="font-semibold">{formatDate(subscription.subscription?.expiryDate || "")}</p></div>
          </div>
        </Card>
        <Card className="p-5 shadow-card lg:col-span-2">
          <p className="text-sm font-semibold text-slate-700">Overall Video Progress</p>
          <div className="mt-4 flex items-end justify-between gap-4">
            <p className="text-3xl font-bold text-slate-950">{dashboard?.library.summary.overallProgress || 0}%</p>
            <p className="text-sm text-slate-500">{dashboard?.library.summary.completedVideos} completed / {dashboard?.library.summary.remainingVideos} remaining</p>
          </div>
          <Progress value={dashboard?.library.summary.overallProgress || 0} className="mt-4" />
        </Card>
      </div>

      {selectedVideo && playbackUrl ? (
        <Card className="space-y-4 p-5 shadow-card">
          <div className="flex flex-wrap items-center justify-between gap-3">
            <div>
              <h3 className="text-lg font-semibold">{selectedVideo.title}</h3>
              <p className="text-sm text-slate-500">{programLabel(selectedVideo.program)} - {selectedVideo.level} - Video {selectedVideo.sequenceNumber} - {formatDuration(selectedVideo.durationSeconds)}</p>
            </div>
            <Badge className={selectedVideo.progress.isCompleted ? "bg-emerald-100 text-emerald-700" : "bg-amber-100 text-amber-700"}>
              {selectedVideo.progress.isCompleted ? "Video Completed" : `${Math.round(selectedVideo.progress.completionPercentage)}% watched`}
            </Badge>
          </div>
          <div className="relative overflow-hidden rounded-md bg-black" onContextMenu={(event) => event.preventDefault()}>
            <video
              ref={videoRef}
              src={playbackUrl}
              className="aspect-video w-full bg-black"
              controls
              controlsList="nodownload noplaybackrate"
              disablePictureInPicture
              onLoadedMetadata={(event) => {
                event.currentTarget.currentTime = selectedVideo.progress.currentPositionSeconds || 0;
              }}
              onPlay={(event) => {
                segmentStartRef.current = event.currentTarget.currentTime;
              }}
              onPause={() => void flushProgress()}
              onEnded={() => void flushProgress()}
              onTimeUpdate={(event) => {
                const current = event.currentTarget.currentTime;
                if (current > maxWatchedRef.current) maxWatchedRef.current = current;
              }}
              onSeeking={(event) => {
                const allowed = maxWatchedRef.current + 5;
                if (event.currentTarget.currentTime > allowed) {
                  event.currentTarget.currentTime = allowed;
                }
              }}
            />
            <div
              className="pointer-events-none absolute rounded bg-black/35 px-3 py-2 text-xs font-semibold text-white shadow"
              style={{ left: `${watermark.x}%`, top: `${watermark.y}%` }}
            >
              <div>{dashboard?.watermarkIdentity.name} | {dashboard?.watermarkIdentity.mobile}</div>
              <div>{nowText}</div>
            </div>
          </div>
          <Progress value={selectedVideo.progress.completionPercentage} />
          <div className="flex flex-wrap items-center justify-between gap-3">
            <p className="text-xs text-slate-500">Progress saves automatically. Forward seeking is limited to watched sections.</p>
            <Button disabled={!selectedVideo.progress.isCompleted} onClick={() => {
              const next = dashboard?.library.videos.find((video) => video.program === selectedVideo.program && video.level === selectedVideo.level && video.sequenceNumber === selectedVideo.sequenceNumber + 1);
              if (next?.isUnlocked) void openVideo(next);
            }}>
              Continue to Next Video
            </Button>
          </div>
        </Card>
      ) : null}

      {groupedVideos.map((group) => (
        <Card key={group.key} className="p-5 shadow-card">
          <h3 className="text-base font-semibold">{programLabel(group.videos[0].program)} - {group.videos[0].level}</h3>
          <div className="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            {group.videos.map((video) => {
              const completed = video.progress.isCompleted;
              const status = !video.isUnlocked ? "Locked" : completed ? "Completed" : video.progress.completionPercentage > 0 ? "Continue Watching" : "Available";
              return (
                <div key={video.id} className="rounded-md border border-slate-200 bg-white p-4">
                  <div className="flex items-start justify-between gap-3">
                    <div>
                      <p className="text-xs font-semibold text-slate-500">Video {video.sequenceNumber}</p>
                      <h4 className="mt-1 font-semibold text-slate-950">{video.title}</h4>
                      <p className="mt-1 text-xs text-slate-500">{formatDuration(video.durationSeconds)}</p>
                    </div>
                    <Badge variant="outline">{status}</Badge>
                  </div>
                  <Progress value={video.progress.completionPercentage} className="mt-4" />
                  <p className="mt-2 text-xs text-slate-500">{Math.round(video.progress.completionPercentage)}% complete</p>
                  {!video.isUnlocked ? (
                    <p className="mt-4 flex items-center gap-2 text-xs text-slate-500"><Lock className="h-3.5 w-3.5" />{video.lockedReason}</p>
                  ) : (
                    <Button size="sm" className="mt-4" onClick={() => void openVideo(video)}>
                      <PlayCircle className="mr-2 h-4 w-4" />Watch Now
                    </Button>
                  )}
                </div>
              );
            })}
          </div>
        </Card>
      ))}
    </div>
  );
};
const InstructorDashboardShell = () => {
  const { profile, updateProfile } = useInstructorDashboard();
  const { logout, token, user } = useAuth();
  const navigate = useNavigate();
  const [activeTab, setActiveTab] = useState<NavKey>(() => {
    const saved = localStorage.getItem("instructor_active_tab");
    return navItems.some((item) => item.key === saved) ? (saved as NavKey) : "overview";
  });
  const [mobileNavOpen, setMobileNavOpen] = useState(false);

  const handleTabChange = (tab: NavKey) => {
    setActiveTab(tab);
    localStorage.setItem("instructor_active_tab", tab);
  };

  const handleMobileTabChange = (tab: NavKey) => {
    handleTabChange(tab);
    setMobileNavOpen(false);
  };

  const handleLogout = () => {
    logout();
    navigate("/instructor-login");
  };

  return (
    <div className="min-h-screen overflow-x-hidden bg-[#e9ebf1]">
      <div className="flex min-w-0">
        <aside className="hidden lg:flex w-[182px] fixed inset-y-0 flex-col bg-[#465b91] px-5 py-4 text-white">
          <div className="bg-white p-2">
            <img src="/abacus_logo.png" alt="Simple Abacus logo" className="h-10 w-full object-contain" />
          </div>
          <button
            type="button"
            onClick={() => handleTabChange("settings")}
            className="mt-4 flex w-full items-center gap-3 rounded-md px-1 py-2 text-left transition hover:bg-white/10"
          >
            <span className="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-full bg-white/95 text-[#465b91]">
              {profile.avatarUrl ? (
                <img src={profile.avatarUrl} alt="Profile" className="h-full w-full object-cover" />
              ) : (
                <GraduationCap className="h-5 w-5" />
              )}
            </span>
            <span className="min-w-0 text-xs leading-5">
              <span className="block truncate font-semibold">{profile.name}</span>
              <span className="block truncate text-white/85">{profile.email}</span>
            </span>
          </button>
          <nav className="mt-5 space-y-1">
            {navItems.map((item) => (
              <button
                key={item.key}
                className={`flex w-full items-center gap-3 px-3 py-2 text-left text-xs font-semibold transition ${
                  activeTab === item.key ? "bg-white text-slate-950" : "text-white hover:bg-white/10"
                }`}
                type="button"
                onClick={() => handleTabChange(item.key)}
              >
                <item.icon className="h-4 w-4" />
                {item.label}
              </button>
            ))}
          </nav>
          <div className="mt-auto flex gap-2 pb-2 text-[11px]">
            <button type="button" onClick={() => handleTabChange("settings")}>Settings</button>
            <span>|</span>
            <button type="button" onClick={handleLogout}>Logout</button>
          </div>
        </aside>

        <div className="min-w-0 flex-1 lg:ml-[182px]">
          <header className="sticky top-0 z-30 flex min-h-[56px] items-center justify-between gap-2 bg-white px-3 py-2 shadow-sm sm:px-5 lg:px-8">
            <div className="flex min-w-0 items-center gap-2 sm:gap-3">
              <Sheet open={mobileNavOpen} onOpenChange={setMobileNavOpen}>
                <SheetTrigger asChild>
                  <Button variant="ghost" size="icon" className="shrink-0 lg:hidden" aria-label="Open instructor menu">
                    <Menu className="h-4 w-4" />
                  </Button>
                </SheetTrigger>
                <SheetContent side="left" className="flex h-full w-72 max-w-[85vw] flex-col overflow-y-auto bg-[#465b91] p-5 text-white">
                  <img src="/abacus_logo.png" alt="Simple Abacus logo" className="h-12 w-full bg-white object-contain p-2" />
                  <button
                    type="button"
                    onClick={() => handleMobileTabChange("settings")}
                    className="mt-4 flex w-full items-center gap-3 rounded-md p-2 text-left hover:bg-white/10"
                  >
                    <span className="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-full bg-white text-[#465b91]">
                      {profile.avatarUrl ? (
                        <img src={profile.avatarUrl} alt="Profile" className="h-full w-full object-cover" />
                      ) : (
                        <GraduationCap className="h-5 w-5" />
                      )}
                    </span>
                    <span className="min-w-0 text-sm">
                      <span className="block truncate font-semibold">{profile.name}</span>
                      <span className="block truncate text-xs text-white/85">{profile.email}</span>
                    </span>
                  </button>
                  <nav className="mt-6 space-y-1">
                    {navItems.map((item) => (
                      <button
                        key={item.key}
                        className={`flex w-full items-center gap-3 px-3 py-2 text-left text-sm font-semibold ${
                          activeTab === item.key ? "bg-white text-slate-950" : "text-white"
                        }`}
                        type="button"
                        onClick={() => handleMobileTabChange(item.key)}
                      >
                        <item.icon className="h-4 w-4" />
                        {item.label}
                      </button>
                    ))}
                  </nav>
                  <div className="mt-auto pt-5">
                    <Button type="button" variant="secondary" className="w-full justify-start" onClick={handleLogout}>
                      <LogOut className="mr-2 h-4 w-4" />
                      Logout
                    </Button>
                  </div>
                </SheetContent>
              </Sheet>
              <Menu className="hidden h-4 w-4 lg:block" />
              <p className="min-w-0 truncate text-sm sm:text-base lg:ml-4">
                Welcome to <span className="font-semibold">Simple Abacus</span>
              </p>
            </div>
            <div className="flex shrink-0 items-center gap-1 sm:gap-3">
              <DropdownMenu>
                <DropdownMenuTrigger asChild>
                  <Button variant="ghost" className="gap-2 px-2 text-xs sm:px-3">
                    <div className="h-7 w-7 rounded-full bg-slate-100 flex items-center justify-center overflow-hidden">
                      {profile.avatarUrl ? (
                        <img src={profile.avatarUrl} alt="User" className="h-full w-full object-cover" />
                      ) : (
                        <GraduationCap className="h-4 w-4 text-primary" />
                      )}
                    </div>
                    <span className="hidden max-w-28 truncate sm:inline">{profile.name}</span>
                  </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end">
                  <DropdownMenuItem onClick={() => handleTabChange("settings")}>Settings</DropdownMenuItem>
                  <DropdownMenuItem onClick={handleLogout}>Logout</DropdownMenuItem>
                </DropdownMenuContent>
              </DropdownMenu>
              <Button variant="ghost" size="icon" className="hidden sm:inline-flex" onClick={handleLogout} aria-label="Logout">
                <LogOut className="h-4 w-4" />
              </Button>
            </div>
          </header>

          <main className="min-w-0 space-y-6 p-4 sm:p-6 lg:space-y-8 lg:p-8 [&_table]:block [&_table]:w-full [&_table]:overflow-x-auto md:[&_table]:table">
            {activeTab === "overview" && <OverviewSection onNavigate={handleTabChange} />}
            {activeTab === "courses" && <CoursesSection />}
            {activeTab === "topics" && <AssignmentsSection />}
            {activeTab === "materials" && <MaterialsSection />}
            {activeTab === "students" && <StudentsSection />}
            {activeTab === "shop" && <TeacherShopSection token={token} paymentPath="/teacher-dashboard/payment-gateway" backPath="/teacher-dashboard" />}
            {activeTab === "trainingVideos" && <TrainingVideosSection token={token} />}
            {activeTab === "batches" && <BatchesSection />}
            {activeTab === "enquiries" && <EnquiriesSection />}
            {activeTab === "settings" && <ProfileSection />}
          </main>
        </div>
      </div>

    </div>
  );
};

const TeacherDashboard = () => (
  <InstructorDashboardProvider>
    <InstructorDashboardShell />
  </InstructorDashboardProvider>
);

export default TeacherDashboard;
