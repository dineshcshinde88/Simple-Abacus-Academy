import { createContext, ReactNode, useContext, useMemo, useState } from "react";

export type FeesStatus = "paid" | "pending";
export type PerformanceStatus = "Good" | "Average" | "Needs Improvement";

export type Student = {
  id: string;
  name: string;
  email: string;
  level: string;
  batchId: string | null;
  feesStatus: FeesStatus;
  joinedAt: string;
  progress: {
    marks: number;
    levelCompleted: number;
    status: PerformanceStatus;
  };
};

export type Batch = {
  id: string;
  name: string;
  level: string;
  studentIds: string[];
};

export type ClassSession = {
  id: string;
  batchId: string;
  topic: string;
  date: string;
  time: string;
  meetingLink: string;
  attendance: Record<string, boolean>;
};

export type Assignment = {
  id: string;
  title: string;
  questions: string[];
  assignedTo: { type: "student" | "batch"; id: string };
  dueDate: string;
  submissions: Array<{ studentId: string; submittedAt: string; score: number; status: "submitted" | "pending" }>;
};

export type Material = {
  id: string;
  title: string;
  type: "pdf" | "video";
  url: string;
  batchId: string;
  uploadedAt: string;
};

export type Payment = {
  id: string;
  studentId: string;
  amount: number;
  date: string;
  method: string;
  status: "paid" | "pending";
};

export type Announcement = {
  id: string;
  title: string;
  message: string;
  sentAt: string;
};

export type Activity = {
  id: string;
  text: string;
  time: string;
};

export type InstructorProfile = {
  name: string;
  email: string;
  avatarUrl: string | null;
};

type InstructorDashboardContextType = {
  profile: InstructorProfile;
  students: Student[];
  batches: Batch[];
  classes: ClassSession[];
  assignments: Assignment[];
  materials: Material[];
  payments: Payment[];
  announcements: Announcement[];
  activities: Activity[];
  addStudent: (student: Omit<Student, "id" | "joinedAt" | "progress"> & { progress?: Student["progress"] }) => void;
  updateStudent: (id: string, updates: Partial<Student>) => void;
  deleteStudent: (id: string) => void;
  addBatch: (batch: Omit<Batch, "id" | "studentIds">) => void;
  assignStudentToBatch: (studentId: string, batchId: string) => void;
  scheduleClass: (session: Omit<ClassSession, "id" | "attendance">) => void;
  toggleAttendance: (classId: string, studentId: string) => void;
  addAssignment: (assignment: Omit<Assignment, "id" | "submissions">) => void;
  addSubmission: (assignmentId: string, studentId: string, score: number) => void;
  updateProgress: (studentId: string, updates: Partial<Student["progress"]>) => void;
  addPayment: (payment: Omit<Payment, "id">) => void;
  addMaterial: (material: Omit<Material, "id" | "uploadedAt">) => void;
  addAnnouncement: (announcement: Omit<Announcement, "id" | "sentAt">) => void;
  updateProfile: (updates: Partial<InstructorProfile>) => void;
};

const InstructorDashboardContext = createContext<InstructorDashboardContextType | null>(null);

const uid = () => (typeof crypto !== "undefined" && "randomUUID" in crypto ? crypto.randomUUID() : Math.random().toString(36).slice(2));

const PROFILE_STORAGE_KEY = "instructor_profile_v1";

const initialStudents: Student[] = [
  {
    id: "stu-1",
    name: "Aarav Mehta",
    email: "aarav@student.com",
    level: "Level 3",
    batchId: "batch-1",
    feesStatus: "paid",
    joinedAt: "2026-03-12",
    progress: { marks: 86, levelCompleted: 3, status: "Good" },
  },
  {
    id: "stu-2",
    name: "Siya Patel",
    email: "siya@student.com",
    level: "Level 2",
    batchId: "batch-1",
    feesStatus: "pending",
    joinedAt: "2026-02-24",
    progress: { marks: 72, levelCompleted: 2, status: "Average" },
  },
  {
    id: "stu-3",
    name: "Kabir Joshi",
    email: "kabir@student.com",
    level: "Level 4",
    batchId: "batch-2",
    feesStatus: "paid",
    joinedAt: "2026-01-18",
    progress: { marks: 91, levelCompleted: 4, status: "Good" },
  },
  {
    id: "stu-4",
    name: "Myra Shah",
    email: "myra@student.com",
    level: "Level 1",
    batchId: null,
    feesStatus: "pending",
    joinedAt: "2026-03-28",
    progress: { marks: 58, levelCompleted: 1, status: "Needs Improvement" },
  },
];

const initialBatches: Batch[] = [
  { id: "batch-1", name: "Evening Stars", level: "Level 2-3", studentIds: ["stu-1", "stu-2"] },
  { id: "batch-2", name: "Morning Wizards", level: "Level 4", studentIds: ["stu-3"] },
];

const initialClasses: ClassSession[] = [
  {
    id: "class-1",
    batchId: "batch-1",
    topic: "Speed drills",
    date: "2026-04-15",
    time: "17:00",
    meetingLink: "https://meet.google.com/abc-defg-hij",
    attendance: { "stu-1": true, "stu-2": false },
  },
  {
    id: "class-2",
    batchId: "batch-2",
    topic: "Advanced multiplication",
    date: "2026-04-16",
    time: "08:00",
    meetingLink: "https://zoom.us/j/123456789",
    attendance: { "stu-3": true },
  },
];

const initialAssignments: Assignment[] = [
  {
    id: "ass-1",
    title: "Week 2 Worksheet",
    questions: ["5-digit addition", "2-digit multiplication", "Speed round: 20 sums"],
    assignedTo: { type: "batch", id: "batch-1" },
    dueDate: "2026-04-18",
    submissions: [
      { studentId: "stu-1", submittedAt: "2026-04-10", score: 88, status: "submitted" },
      { studentId: "stu-2", submittedAt: "", score: 0, status: "pending" },
    ],
  },
];

const initialMaterials: Material[] = [
  {
    id: "mat-1",
    title: "Abacus Level 2 Drill Pack",
    type: "pdf",
    url: "https://example.com/abacus-drills.pdf",
    batchId: "batch-1",
    uploadedAt: "2026-04-01",
  },
];

const initialPayments: Payment[] = [
  { id: "pay-1", studentId: "stu-1", amount: 1500, date: "2026-04-01", method: "UPI", status: "paid" },
  { id: "pay-2", studentId: "stu-2", amount: 1500, date: "2026-04-05", method: "Cash", status: "pending" },
];

const initialAnnouncements: Announcement[] = [
  { id: "ann-1", title: "Class rescheduled", message: "Friday class moved to 6 PM.", sentAt: "2026-04-08" },
];

const initialActivities: Activity[] = [
  { id: "act-1", text: "Added new student Siya Patel", time: "2 hours ago" },
  { id: "act-2", text: "Assigned Week 2 Worksheet to Evening Stars", time: "Yesterday" },
  { id: "act-3", text: "Uploaded Drill Pack PDF", time: "2 days ago" },
];

export const InstructorDashboardProvider = ({ children }: { children: ReactNode }) => {
  const [profile, setProfile] = useState<InstructorProfile>(() => {
    if (typeof window !== "undefined") {
      const stored = window.localStorage.getItem(PROFILE_STORAGE_KEY);
      if (stored) {
        try {
          return JSON.parse(stored) as InstructorProfile;
        } catch {
          window.localStorage.removeItem(PROFILE_STORAGE_KEY);
        }
      }
    }
    return {
      name: "Neha Kulkarni",
      email: "neha@simpleabacus.com",
      avatarUrl: null,
    };
  });
  const [students, setStudents] = useState<Student[]>(initialStudents);
  const [batches, setBatches] = useState<Batch[]>(initialBatches);
  const [classes, setClasses] = useState<ClassSession[]>(initialClasses);
  const [assignments, setAssignments] = useState<Assignment[]>(initialAssignments);
  const [materials, setMaterials] = useState<Material[]>(initialMaterials);
  const [payments, setPayments] = useState<Payment[]>(initialPayments);
  const [announcements, setAnnouncements] = useState<Announcement[]>(initialAnnouncements);
  const [activities, setActivities] = useState<Activity[]>(initialActivities);

  const addActivity = (text: string) => {
    setActivities((prev) => [{ id: uid(), text, time: "Just now" }, ...prev].slice(0, 8));
  };

  const addStudent: InstructorDashboardContextType["addStudent"] = (student) => {
    const newStudent: Student = {
      id: uid(),
      joinedAt: new Date().toISOString().slice(0, 10),
      progress: student.progress || { marks: 0, levelCompleted: 0, status: "Average" },
      ...student,
    };
    setStudents((prev) => [newStudent, ...prev]);
    addActivity(`Added new student ${newStudent.name}`);
  };

  const updateStudent: InstructorDashboardContextType["updateStudent"] = (id, updates) => {
    setStudents((prev) => prev.map((student) => (student.id === id ? { ...student, ...updates } : student)));
    addActivity("Updated student profile");
  };

  const deleteStudent: InstructorDashboardContextType["deleteStudent"] = (id) => {
    setStudents((prev) => prev.filter((student) => student.id !== id));
    setBatches((prev) => prev.map((batch) => ({ ...batch, studentIds: batch.studentIds.filter((sid) => sid !== id) })));
    addActivity("Removed a student");
  };

  const addBatch: InstructorDashboardContextType["addBatch"] = (batch) => {
    const newBatch: Batch = { id: uid(), studentIds: [], ...batch };
    setBatches((prev) => [newBatch, ...prev]);
    addActivity(`Created batch ${newBatch.name}`);
  };

  const assignStudentToBatch: InstructorDashboardContextType["assignStudentToBatch"] = (studentId, batchId) => {
    setStudents((prev) => prev.map((student) => (student.id === studentId ? { ...student, batchId } : student)));
    setBatches((prev) =>
      prev.map((batch) =>
        batch.id === batchId && !batch.studentIds.includes(studentId)
          ? { ...batch, studentIds: [...batch.studentIds, studentId] }
          : batch,
      ),
    );
    addActivity("Assigned student to batch");
  };

  const scheduleClass: InstructorDashboardContextType["scheduleClass"] = (session) => {
    const newClass: ClassSession = { id: uid(), attendance: {}, ...session };
    setClasses((prev) => [newClass, ...prev]);
    addActivity(`Scheduled class ${newClass.topic}`);
  };

  const toggleAttendance: InstructorDashboardContextType["toggleAttendance"] = (classId, studentId) => {
    setClasses((prev) =>
      prev.map((session) =>
        session.id === classId
          ? { ...session, attendance: { ...session.attendance, [studentId]: !session.attendance[studentId] } }
          : session,
      ),
    );
  };

  const addAssignment: InstructorDashboardContextType["addAssignment"] = (assignment) => {
    const newAssignment: Assignment = { id: uid(), submissions: [], ...assignment };
    setAssignments((prev) => [newAssignment, ...prev]);
    addActivity(`Created worksheet ${newAssignment.title}`);
  };

  const addSubmission: InstructorDashboardContextType["addSubmission"] = (assignmentId, studentId, score) => {
    setAssignments((prev) =>
      prev.map((assignment) =>
        assignment.id === assignmentId
          ? {
              ...assignment,
              submissions: assignment.submissions.some((sub) => sub.studentId === studentId)
                ? assignment.submissions.map((sub) =>
                    sub.studentId === studentId
                      ? { ...sub, score, status: "submitted", submittedAt: new Date().toISOString().slice(0, 10) }
                      : sub,
                  )
                : [
                    ...assignment.submissions,
                    {
                      studentId,
                      score,
                      status: "submitted",
                      submittedAt: new Date().toISOString().slice(0, 10),
                    },
                  ],
            }
          : assignment,
      ),
    );
    addActivity("Recorded assignment submission");
  };

  const updateProgress: InstructorDashboardContextType["updateProgress"] = (studentId, updates) => {
    setStudents((prev) =>
      prev.map((student) =>
        student.id === studentId ? { ...student, progress: { ...student.progress, ...updates } } : student,
      ),
    );
    addActivity("Updated progress report");
  };

  const addPayment: InstructorDashboardContextType["addPayment"] = (payment) => {
    const newPayment: Payment = { id: uid(), ...payment };
    setPayments((prev) => [newPayment, ...prev]);
    setStudents((prev) =>
      prev.map((student) =>
        student.id === payment.studentId ? { ...student, feesStatus: payment.status } : student,
      ),
    );
    addActivity("Added fee payment entry");
  };

  const addMaterial: InstructorDashboardContextType["addMaterial"] = (material) => {
    const newMaterial: Material = { id: uid(), uploadedAt: new Date().toISOString().slice(0, 10), ...material };
    setMaterials((prev) => [newMaterial, ...prev]);
    addActivity(`Uploaded ${newMaterial.type.toUpperCase()} material`);
  };

  const addAnnouncement: InstructorDashboardContextType["addAnnouncement"] = (announcement) => {
    const newAnnouncement: Announcement = { id: uid(), sentAt: new Date().toISOString().slice(0, 10), ...announcement };
    setAnnouncements((prev) => [newAnnouncement, ...prev]);
    addActivity(`Sent announcement: ${newAnnouncement.title}`);
  };

  const updateProfile: InstructorDashboardContextType["updateProfile"] = (updates) => {
    setProfile((prev) => {
      const next = { ...prev, ...updates };
      if (typeof window !== "undefined") {
        window.localStorage.setItem(PROFILE_STORAGE_KEY, JSON.stringify(next));
      }
      return next;
    });
  };

  const value = useMemo(
    () => ({
      profile,
      students,
      batches,
      classes,
      assignments,
      materials,
      payments,
      announcements,
      activities,
      addStudent,
      updateStudent,
      deleteStudent,
      addBatch,
      assignStudentToBatch,
      scheduleClass,
      toggleAttendance,
      addAssignment,
      addSubmission,
      updateProgress,
      addPayment,
      addMaterial,
      addAnnouncement,
      updateProfile,
    }),
    [
      profile,
      students,
      batches,
      classes,
      assignments,
      materials,
      payments,
      announcements,
      activities,
    ],
  );

  return <InstructorDashboardContext.Provider value={value}>{children}</InstructorDashboardContext.Provider>;
};

export const useInstructorDashboard = () => {
  const context = useContext(InstructorDashboardContext);
  if (!context) {
    throw new Error("useInstructorDashboard must be used within InstructorDashboardProvider");
  }
  return context;
};

