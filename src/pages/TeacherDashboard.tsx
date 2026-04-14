
import { useEffect, useMemo, useState } from "react";
import { useNavigate } from "react-router-dom";
import {
  Award,
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
  LogOut,
  Mail,
  MessageCircle,
  Menu,
  Pencil,
  Plus,
  Search,
  Settings,
  Star,
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
import { Badge } from "@/components/ui/badge";
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from "@/components/ui/dialog";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Textarea } from "@/components/ui/textarea";
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from "@/components/ui/dropdown-menu";
import { Progress } from "@/components/ui/progress";
import { Sheet, SheetContent, SheetTrigger } from "@/components/ui/sheet";
import {
  Assignment,
  FeesStatus,
  InstructorDashboardProvider,
  PerformanceStatus,
  Student,
  useInstructorDashboard,
} from "@/context/InstructorDashboardContext";
import { useAuth } from "@/context/AuthContext";

const navItems = [
  { key: "overview", label: "Dashboard", icon: LayoutDashboard },
  { key: "students", label: "My Students", icon: Users },
  { key: "batches", label: "Batches / Classes", icon: Calendar },
  { key: "assignments", label: "Assignments / Worksheets", icon: ClipboardList },
  { key: "progress", label: "Progress / Reports", icon: Star },
  { key: "fees", label: "Fees Management", icon: Wallet },
  { key: "materials", label: "Study Material", icon: BookOpen },
  { key: "communication", label: "Communication", icon: MessageCircle },
  { key: "profile", label: "Profile Settings", icon: Settings },
] as const;

type NavKey = (typeof navItems)[number]["key"];

type StudentFormState = {
  name: string;
  email: string;
  level: string;
  batchId: string | "none";
  feesStatus: FeesStatus;
};

type BatchFormState = {
  name: string;
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
  status: "paid" | "pending";
};

type MaterialFormState = {
  title: string;
  type: "pdf" | "video";
  url: string;
  batchId: string;
};

type AnnouncementFormState = {
  title: string;
  message: string;
};

const statusBadge = (status: PerformanceStatus) => {
  if (status === "Good") return "bg-emerald-100 text-emerald-700";
  if (status === "Average") return "bg-amber-100 text-amber-700";
  return "bg-rose-100 text-rose-700";
};

const feeBadge = (status: FeesStatus) =>
  status === "paid" ? "bg-emerald-100 text-emerald-700" : "bg-rose-100 text-rose-700";

const formatDate = (value: string) => {
  if (!value) return "-";
  return new Date(value).toLocaleDateString();
};

const SectionTitle = ({ title, subtitle }: { title: string; subtitle?: string }) => (
  <div className="flex flex-col gap-1">
    <h2 className="text-2xl font-semibold text-foreground">{title}</h2>
    {subtitle ? <p className="text-sm text-muted-foreground">{subtitle}</p> : null}
  </div>
);

const OverviewSection = ({
  onAddStudent,
  onAddBatch,
  onAddAssignment,
}: {
  onAddStudent: () => void;
  onAddBatch: () => void;
  onAddAssignment: () => void;
}) => {
  const { students, classes, activities } = useInstructorDashboard();
  const today = new Date().toISOString().slice(0, 10);

  const stats = useMemo(() => {
    const totalStudents = students.length;
    const activeStudents = students.filter((student) => student.feesStatus === "paid").length;
    const pendingFees = students.filter((student) => student.feesStatus === "pending").length;
    const upcomingClasses = classes.filter((session) => session.date >= today).length;
    return { totalStudents, activeStudents, pendingFees, upcomingClasses };
  }, [students, classes, today]);

  return (
    <div className="space-y-8">
      <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        {[
          { label: "Total Students", value: stats.totalStudents, icon: Users },
          { label: "Active Students", value: stats.activeStudents, icon: CheckCircle2 },
          { label: "Pending Fees", value: stats.pendingFees, icon: Wallet },
          { label: "Upcoming Classes", value: stats.upcomingClasses, icon: Calendar },
        ].map((card) => (
          <Card key={card.label} className="p-5 shadow-card">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-muted-foreground">{card.label}</p>
                <p className="text-2xl font-semibold text-foreground mt-2">{card.value}</p>
              </div>
              <div className="h-11 w-11 rounded-xl bg-primary/10 text-primary flex items-center justify-center">
                <card.icon className="h-5 w-5" />
              </div>
            </div>
          </Card>
        ))}
      </div>

      <div className="grid gap-6 lg:grid-cols-[2fr_1fr]">
        <Card className="p-6 shadow-card">
          <div className="flex items-center justify-between">
            <SectionTitle title="Recent Activities" subtitle="Latest actions across your classes" />
            <Badge variant="outline" className="border-primary/20 text-primary">
              Live
            </Badge>
          </div>
          <ul className="mt-6 space-y-4">
            {activities.map((activity) => (
              <li key={activity.id} className="flex items-start justify-between gap-3">
                <div className="flex items-start gap-3">
                  <span className="mt-1 h-2 w-2 rounded-full bg-primary" />
                  <div>
                    <p className="text-sm font-medium text-foreground">{activity.text}</p>
                    <p className="text-xs text-muted-foreground">{activity.time}</p>
                  </div>
                </div>
                <Button variant="ghost" size="sm">
                  View
                </Button>
              </li>
            ))}
          </ul>
        </Card>

        <div className="space-y-6">
          <Card className="p-6 shadow-card">
            <SectionTitle title="Quick Actions" subtitle="Jumpstart common tasks" />
            <div className="mt-4 grid gap-3">
              <Button className="justify-start gap-2" onClick={onAddStudent}>
                <UserPlus className="h-4 w-4" /> Add Student
              </Button>
              <Button variant="outline" className="justify-start gap-2" onClick={onAddBatch}>
                <Calendar className="h-4 w-4" /> Create Batch
              </Button>
              <Button variant="outline" className="justify-start gap-2" onClick={onAddAssignment}>
                <ClipboardList className="h-4 w-4" /> Assign Worksheet
              </Button>
            </div>
          </Card>

          <Card className="p-6 shadow-card">
            <SectionTitle title="Leaderboard" subtitle="Top performers this week" />
            <div className="mt-4 space-y-3">
              {students
                .slice()
                .sort((a, b) => b.progress.marks - a.progress.marks)
                .slice(0, 3)
                .map((student, index) => (
                  <div key={student.id} className="flex items-center justify-between">
                    <div className="flex items-center gap-3">
                      <div className="h-9 w-9 rounded-full bg-amber-100 text-amber-700 flex items-center justify-center">
                        <Trophy className="h-4 w-4" />
                      </div>
                      <div>
                        <p className="text-sm font-medium">{student.name}</p>
                        <p className="text-xs text-muted-foreground">{student.level}</p>
                      </div>
                    </div>
                    <span className="text-sm font-semibold text-foreground">#{index + 1}</span>
                  </div>
                ))}
            </div>
          </Card>
        </div>
      </div>
    </div>
  );
};
const StudentsSection = () => {
  const { students, batches, addStudent, updateStudent, deleteStudent } = useInstructorDashboard();
  const [search, setSearch] = useState("");
  const [feeFilter, setFeeFilter] = useState<"all" | FeesStatus>("all");
  const [editingStudent, setEditingStudent] = useState<Student | null>(null);
  const [viewStudent, setViewStudent] = useState<Student | null>(null);

  const [formState, setFormState] = useState<StudentFormState>({
    name: "",
    email: "",
    level: "Level 0",
    batchId: "none",
    feesStatus: "pending",
  });

  const filteredStudents = students.filter((student) => {
    const matchesSearch = student.name.toLowerCase().includes(search.toLowerCase());
    const matchesFee = feeFilter === "all" || student.feesStatus === feeFilter;
    return matchesSearch && matchesFee;
  });

  const resetForm = () =>
    setFormState({ name: "", email: "", level: "Level 0", batchId: "none", feesStatus: "pending" });

  const handleSaveStudent = () => {
    if (!formState.name.trim() || !formState.email.trim()) return;
    if (editingStudent) {
      updateStudent(editingStudent.id, {
        name: formState.name,
        email: formState.email,
        level: formState.level,
        batchId: formState.batchId === "none" ? null : formState.batchId,
        feesStatus: formState.feesStatus,
      });
      setEditingStudent(null);
    } else {
      addStudent({
        name: formState.name,
        email: formState.email,
        level: formState.level,
        batchId: formState.batchId === "none" ? null : formState.batchId,
        feesStatus: formState.feesStatus,
      });
    }
    resetForm();
  };

  const startEdit = (student: Student) => {
    setEditingStudent(student);
    setFormState({
      name: student.name,
      email: student.email,
      level: student.level,
      batchId: student.batchId ?? "none",
      feesStatus: student.feesStatus,
    });
  };

  return (
    <div className="space-y-6">
      <div className="flex flex-wrap items-center justify-between gap-4">
        <SectionTitle title="My Students" subtitle="Manage all enrolled learners" />
        <Dialog>
          <DialogTrigger asChild>
            <Button className="gap-2" onClick={() => setEditingStudent(null)}>
              <Plus className="h-4 w-4" /> Add Student
            </Button>
          </DialogTrigger>
          <DialogContent className="max-w-lg">
            <DialogHeader>
              <DialogTitle>{editingStudent ? "Edit Student" : "Add Student"}</DialogTitle>
            </DialogHeader>
            <div className="grid gap-4">
              <Input
                placeholder="Student Name"
                value={formState.name}
                onChange={(e) => setFormState((prev) => ({ ...prev, name: e.target.value }))}
              />
              <Input
                placeholder="Email"
                value={formState.email}
                onChange={(e) => setFormState((prev) => ({ ...prev, email: e.target.value }))}
              />
              <Select value={formState.level} onValueChange={(value) => setFormState((prev) => ({ ...prev, level: value }))}>
                <SelectTrigger>
                  <SelectValue placeholder="Select level" />
                </SelectTrigger>
                <SelectContent>
                  {[
                    "Level 0",
                    "Level 1",
                    "Level 2",
                    "Level 3",
                    "Level 4",
                    "Level 5",
                    "Level 6",
                    "Level 7",
                  ].map((level) => (
                    <SelectItem key={level} value={level}>
                      {level}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
              <Select
                value={formState.batchId}
                onValueChange={(value) => setFormState((prev) => ({ ...prev, batchId: value }))}
              >
                <SelectTrigger>
                  <SelectValue placeholder="Assign batch" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="none">No batch</SelectItem>
                  {batches.map((batch) => (
                    <SelectItem key={batch.id} value={batch.id}>
                      {batch.name}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
              <Select
                value={formState.feesStatus}
                onValueChange={(value: FeesStatus) =>
                  setFormState((prev) => ({ ...prev, feesStatus: value }))
                }
              >
                <SelectTrigger>
                  <SelectValue placeholder="Fee status" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="paid">Paid</SelectItem>
                  <SelectItem value="pending">Pending</SelectItem>
                </SelectContent>
              </Select>
              <Button onClick={handleSaveStudent}>Save Student</Button>
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
            <SelectItem value="pending">Pending</SelectItem>
          </SelectContent>
        </Select>
      </div>

      <Card className="shadow-card">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Name</TableHead>
              <TableHead>Level</TableHead>
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
                  <TableCell>{student.level}</TableCell>
                  <TableCell>{batch?.name || "Unassigned"}</TableCell>
                  <TableCell>
                    <span className={`px-2 py-1 rounded-full text-xs font-medium ${feeBadge(student.feesStatus)}`}>
                      {student.feesStatus}
                    </span>
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
                  <p className="font-medium capitalize">{student.feesStatus}</p>
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
            <Card className="p-4">
              <p className="text-sm text-muted-foreground">Progress Summary</p>
              <div className="mt-3">
                <p className="text-3xl font-semibold">{student.progress.marks}%</p>
                <Progress value={student.progress.marks} className="mt-2" />
                <p className="mt-3 text-sm">
                  Status:{" "}
                  <span className={`px-2 py-1 rounded-full text-xs ${statusBadge(student.progress.status)}`}>
                    {student.progress.status}
                  </span>
                </p>
              </div>
            </Card>
          </div>
        </div>
      </DialogContent>
    </Dialog>
  );
};
const BatchesSection = () => {
  const { batches, students, classes, addBatch, assignStudentToBatch, scheduleClass, toggleAttendance } =
    useInstructorDashboard();
  const [batchForm, setBatchForm] = useState<BatchFormState>({ name: "", level: "" });
  const [classForm, setClassForm] = useState<ClassFormState>({
    batchId: batches[0]?.id || "",
    topic: "",
    date: "",
    time: "",
    meetingLink: "",
  });

  const handleCreateBatch = () => {
    if (!batchForm.name.trim()) return;
    addBatch({ name: batchForm.name, level: batchForm.level || "Mixed" });
    setBatchForm({ name: "", level: "" });
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
          const upcoming = classes.filter((session) => session.batchId === batch.id).slice(0, 2);
          return (
            <Card key={batch.id} className="p-5 shadow-card space-y-4">
              <div className="flex items-start justify-between">
                <div>
                  <p className="text-lg font-semibold">{batch.name}</p>
                  <p className="text-sm text-muted-foreground">
                    {batch.level} • {batchStudents.length} students
                  </p>
                </div>
                <Badge variant="outline">Active</Badge>
              </div>
              <div className="space-y-2">
                <p className="text-sm font-medium">Assign students</p>
                <div className="flex gap-2">
                  <Select onValueChange={(value) => value && assignStudentToBatch(value, batch.id)}>
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
                  <Button variant="outline">Assign</Button>
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
                <p className="text-sm font-medium mb-2">Attendance</p>
                <div className="space-y-2">
                  {batchStudents.map((student) => (
                    <div key={student.id} className="flex items-center justify-between text-sm">
                      <span>{student.name}</span>
                      <Button
                        variant="outline"
                        size="sm"
                        onClick={() => {
                          const latestClass = classes.find((session) => session.batchId === batch.id);
                          if (latestClass) toggleAttendance(latestClass.id, student.id);
                        }}
                      >
                        Mark
                      </Button>
                    </div>
                  ))}
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
      dueDate: form.dueDate,
      assignedTo: { type: form.targetType, id: form.targetId },
      questions,
    });
    setForm({ title: "", dueDate: "", targetType: "batch", targetId: batches[0]?.id || "", questions: "", autoGenerate: false });
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

const ProgressSection = () => {
  const { students, updateProgress } = useInstructorDashboard();
  const [editing, setEditing] = useState<{ studentId: string; marks: string; status: PerformanceStatus } | null>(null);

  return (
    <div className="space-y-6">
      <SectionTitle title="Progress & Reports" subtitle="Track performance and mark improvements" />
      <Card className="shadow-card">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Student</TableHead>
              <TableHead>Marks</TableHead>
              <TableHead>Level Completed</TableHead>
              <TableHead>Status</TableHead>
              <TableHead className="text-right">Actions</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {students.map((student) => (
              <TableRow key={student.id}>
                <TableCell className="font-medium">{student.name}</TableCell>
                <TableCell>
                  <div className="space-y-2">
                    <div className="flex items-center justify-between text-sm">
                      <span>{student.progress.marks}%</span>
                      <span className="text-xs text-muted-foreground">{student.level}</span>
                    </div>
                    <Progress value={student.progress.marks} />
                  </div>
                </TableCell>
                <TableCell>{student.progress.levelCompleted}</TableCell>
                <TableCell>
                  <span className={`px-2 py-1 rounded-full text-xs font-medium ${statusBadge(student.progress.status)}`}>
                    {student.progress.status}
                  </span>
                </TableCell>
                <TableCell className="text-right">
                  <Button
                    variant="outline"
                    size="sm"
                    onClick={() =>
                      setEditing({
                        studentId: student.id,
                        marks: String(student.progress.marks),
                        status: student.progress.status,
                      })
                    }
                  >
                    Update
                  </Button>
                </TableCell>
              </TableRow>
            ))}
          </TableBody>
        </Table>
      </Card>

      <Card className="p-6 shadow-card">
        <SectionTitle title="Certificates" subtitle="Generate certificates for top students" />
        <div className="mt-4 flex flex-wrap gap-3">
          {students.slice(0, 3).map((student) => (
            <Button key={student.id} variant="outline" className="gap-2">
              <Award className="h-4 w-4" /> Generate for {student.name.split(" ")[0]}
            </Button>
          ))}
        </div>
      </Card>

      <Dialog open={!!editing} onOpenChange={(open) => !open && setEditing(null)}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Update Progress</DialogTitle>
          </DialogHeader>
          {editing && (
            <div className="space-y-3">
              <Input
                placeholder="Marks"
                value={editing.marks}
                onChange={(e) => setEditing({ ...editing, marks: e.target.value })}
              />
              <Select value={editing.status} onValueChange={(value: PerformanceStatus) => setEditing({ ...editing, status: value })}>
                <SelectTrigger>
                  <SelectValue placeholder="Status" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="Good">Good</SelectItem>
                  <SelectItem value="Average">Average</SelectItem>
                  <SelectItem value="Needs Improvement">Needs Improvement</SelectItem>
                </SelectContent>
              </Select>
              <Button
                onClick={() => {
                  updateProgress(editing.studentId, { marks: Number(editing.marks), status: editing.status });
                  setEditing(null);
                }}
              >
                Save
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
        <SectionTitle title="Fees Management" subtitle="Track payments and pending dues" />
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
              <Select value={form.status} onValueChange={(value: "paid" | "pending") => setForm((prev) => ({ ...prev, status: value }))}>
                <SelectTrigger>
                  <SelectValue placeholder="Status" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="paid">Paid</SelectItem>
                  <SelectItem value="pending">Pending</SelectItem>
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
                <TableCell>₹{payment.amount}</TableCell>
                <TableCell>{formatDate(payment.date)}</TableCell>
                <TableCell>
                  <span className={`px-2 py-1 rounded-full text-xs font-medium ${feeBadge(payment.status)}`}>
                    {payment.status}
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
  const [form, setForm] = useState<MaterialFormState>({ title: "", type: "pdf", url: "", batchId: batches[0]?.id || "" });
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
    addMaterial({ title: form.title, type: form.type, url: form.url || "https://example.com/material", batchId: form.batchId });
    setForm({ title: "", type: "pdf", url: "", batchId: batches[0]?.id || "" });
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

const ProfileSection = () => {
  const { profile, updateProfile } = useInstructorDashboard();
  const [name, setName] = useState(profile.name);
  const [email, setEmail] = useState(profile.email);
  const [loginId, setLoginId] = useState(profile.email);
  const [passwordForm, setPasswordForm] = useState({ current: "", next: "", confirm: "" });
  const [passwordError, setPasswordError] = useState("");
  const [avatarPreview, setAvatarPreview] = useState<string | null>(profile.avatarUrl);

  useEffect(() => {
    setLoginId(profile.email);
    setAvatarPreview(profile.avatarUrl);
  }, [profile.email, profile.avatarUrl]);

  const handleAvatarChange = (event: React.ChangeEvent<HTMLInputElement>) => {
    const file = event.target.files?.[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = () => {
      const result = typeof reader.result === "string" ? reader.result : null;
      setAvatarPreview(result);
    };
    reader.readAsDataURL(file);
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
            <p className="text-xs text-muted-foreground">PNG, JPG up to 5MB. Square images work best.</p>
          </div>

          <Button
            onClick={() => {
              updateProfile({ name, email, avatarUrl: avatarPreview });
            }}
          >
            Save Profile
          </Button>
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

const InstructorDashboardShell = () => {
  const { profile } = useInstructorDashboard();
  const { logout } = useAuth();
  const navigate = useNavigate();
  const [activeTab, setActiveTab] = useState<NavKey>(() => {
    const saved = localStorage.getItem("instructor_active_tab");
    return (saved as NavKey) || "overview";
  });

  const handleTabChange = (tab: NavKey) => {
    setActiveTab(tab);
    localStorage.setItem("instructor_active_tab", tab);
  };

  const handleLogout = () => {
    logout();
    navigate("/instructor-login");
  };

  return (
    <div className="min-h-screen bg-slate-50">
      <div className="flex">
        <aside className="hidden lg:flex w-64 flex-col border-r border-border bg-white p-6">
          <div className="flex items-center">
            <img src="/abacus_logo.svg" alt="Simple Abacus Academy logo" className="h-9 w-auto object-contain" />
          </div>
          <nav className="mt-8 space-y-1">
            {navItems.map((item) => (
              <Button
                key={item.key}
                variant={activeTab === item.key ? "default" : "ghost"}
                className={`w-full justify-start gap-3 ${activeTab === item.key ? "text-white" : "text-muted-foreground"}`}
                type="button" onClick={() => handleTabChange(item.key)}
              >
                <item.icon className="h-4 w-4" />
                {item.label}
              </Button>
            ))}
          </nav>
          <div className="mt-auto pt-6">
            <Button variant="outline" className="w-full justify-start gap-3" onClick={handleLogout}>
              <LogOut className="h-4 w-4" />
              Logout
            </Button>
          </div>
        </aside>

        <div className="flex-1">
          <header className="flex items-center justify-between border-b border-border bg-white px-6 py-4">
            <div className="flex items-center gap-3">
              <Sheet>
                <SheetTrigger asChild>
                  <Button variant="outline" size="icon" className="lg:hidden">
                    <Menu className="h-4 w-4" />
                  </Button>
                </SheetTrigger>
                <SheetContent side="left" className="w-64 p-6">
                  <div className="flex items-center">
                    <img src="/abacus_logo.svg" alt="Simple Abacus Academy logo" className="h-9 w-auto object-contain" />
                  </div>
                  <nav className="mt-6 space-y-1">
                    {navItems.map((item) => (
                      <Button
                        key={item.key}
                        variant={activeTab === item.key ? "default" : "ghost"}
                        className={`w-full justify-start gap-3 ${activeTab === item.key ? "text-white" : "text-muted-foreground"}`}
                        type="button" onClick={() => handleTabChange(item.key)}
                      >
                        <item.icon className="h-4 w-4" />
                        {item.label}
                      </Button>
                    ))}
                  </nav>
                  <div className="mt-6">
                    <Button variant="outline" className="w-full justify-start gap-3" onClick={handleLogout}>
                      <LogOut className="h-4 w-4" />
                      Logout
                    </Button>
                  </div>
                </SheetContent>
              </Sheet>
              <div>
                <h1 className="text-xl font-semibold">Instructor Dashboard</h1>
                <p className="text-sm text-muted-foreground">Welcome back, {profile.name}</p>
              </div>
            </div>
            <div className="flex items-center gap-3">
              <Button variant="outline" size="icon">
                <Bell className="h-4 w-4" />
              </Button>
              <DropdownMenu>
                <DropdownMenuTrigger asChild>
                  <Button variant="outline" className="gap-2">
                    <div className="h-8 w-8 rounded-full bg-primary/10 flex items-center justify-center overflow-hidden">
                      {profile.avatarUrl ? (
                        <img src={profile.avatarUrl} alt="Profile" className="h-full w-full object-cover" />
                      ) : (
                        <GraduationCap className="h-4 w-4 text-primary" />
                      )}
                    </div>
                    <span className="hidden sm:inline">{profile.name}</span>
                  </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end">
                  <DropdownMenuItem onClick={() => handleTabChange("profile")}>Profile Settings</DropdownMenuItem>
                  <DropdownMenuItem onClick={handleLogout}>Logout</DropdownMenuItem>
                </DropdownMenuContent>
              </DropdownMenu>
            </div>
          </header>

          <main className="p-6 space-y-8">
            {activeTab === "overview" && (
              <OverviewSection
                onAddStudent={() => handleTabChange("students")}
                onAddBatch={() => handleTabChange("batches")}
                onAddAssignment={() => handleTabChange("assignments")}
              />
            )}
            {activeTab === "students" && <StudentsSection />}
            {activeTab === "batches" && <BatchesSection />}
            {activeTab === "assignments" && <AssignmentsSection />}
            {activeTab === "progress" && <ProgressSection />}
            {activeTab === "fees" && <FeesSection />}
            {activeTab === "materials" && <MaterialsSection />}
            {activeTab === "communication" && <CommunicationSection />}
            {activeTab === "profile" && <ProfileSection />}
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




