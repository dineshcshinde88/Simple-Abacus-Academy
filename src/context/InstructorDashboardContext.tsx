import { createContext, ReactNode, useContext, useEffect, useMemo, useState } from "react";
import { AuthUser } from "@/lib/auth";
import { useAuth } from "@/context/AuthContext";

export type FeesStatus = "paid" | "unpaid";
export type PerformanceStatus = "Good" | "Average" | "Needs Improvement";

export type Student = {
  id: string;
  name: string;
  email: string;
  parentEmail?: string;
  parentMobile?: string;
  dateOfBirth?: string;
  gender?: string;
  motherTongue?: string;
  avatarUrl?: string | null;
  level: string;
  batchId: string | null;
  feesStatus: FeesStatus;
  joinedAt: string;
  levelStartDate?: string;
  levelEndDate?: string;
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
  status: FeesStatus;
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
  addStudent: (student: Omit<Student, "id" | "progress"> & { progress?: Student["progress"] }) => void;
  updateStudent: (id: string, updates: Partial<Student>) => void;
  deleteStudent: (id: string) => void;
  addBatch: (batch: Omit<Batch, "id" | "studentIds">) => void;
  deleteBatch: (id: string) => void;
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

const LEGACY_PROFILE_STORAGE_KEY = "instructor_profile_v1";
const DASHBOARD_STORAGE_VERSION = "v2";

const initialStudents: Student[] = [];
const initialBatches: Batch[] = [];
const initialClasses: ClassSession[] = [];
const initialAssignments: Assignment[] = [];
const initialMaterials: Material[] = [];
const initialPayments: Payment[] = [];
const initialAnnouncements: Announcement[] = [];
const initialActivities: Activity[] = [];

type InstructorDashboardState = {
  profile: InstructorProfile;
  students: Student[];
  batches: Batch[];
  classes: ClassSession[];
  assignments: Assignment[];
  materials: Material[];
  payments: Payment[];
  announcements: Announcement[];
  activities: Activity[];
};

const defaultProfile = (user: AuthUser | null): InstructorProfile => ({
  name: user?.name || "Instructor",
  email: user?.email || "",
  avatarUrl: null,
});

const defaultDashboardState = (user: AuthUser | null): InstructorDashboardState => ({
  profile: defaultProfile(user),
  students: initialStudents,
  batches: initialBatches,
  classes: initialClasses,
  assignments: initialAssignments,
  materials: initialMaterials,
  payments: initialPayments,
  announcements: initialAnnouncements,
  activities: initialActivities,
});

const instructorStorageKey = (user: AuthUser | null) => {
  const identity = user?.id || user?.email || "anonymous";
  return `instructor_dashboard_${DASHBOARD_STORAGE_VERSION}_${identity}`;
};

const readDashboardState = (key: string, user: AuthUser | null): InstructorDashboardState => {
  const fallback = defaultDashboardState(user);
  if (typeof window === "undefined") {
    return fallback;
  }

  const stored = window.localStorage.getItem(key);
  if (stored) {
    try {
      const parsed = JSON.parse(stored) as Partial<InstructorDashboardState>;
      return {
        ...fallback,
        ...parsed,
        profile: {
          ...fallback.profile,
          ...(parsed.profile || {}),
        },
      };
    } catch {
      window.localStorage.removeItem(key);
    }
  }

  const legacyProfile = window.localStorage.getItem(LEGACY_PROFILE_STORAGE_KEY);
  if (legacyProfile) {
    try {
      return {
        ...fallback,
        profile: {
          ...fallback.profile,
          ...(JSON.parse(legacyProfile) as Partial<InstructorProfile>),
        },
      };
    } catch {
      window.localStorage.removeItem(LEGACY_PROFILE_STORAGE_KEY);
    }
  }

  return fallback;
};

export const InstructorDashboardProvider = ({ children }: { children: ReactNode }) => {
  const { user } = useAuth();
  const storageKey = instructorStorageKey(user);
  const initialDashboard = readDashboardState(storageKey, user);
  const [profile, setProfile] = useState<InstructorProfile>(initialDashboard.profile);
  const [students, setStudents] = useState<Student[]>(initialDashboard.students);
  const [batches, setBatches] = useState<Batch[]>(initialDashboard.batches);
  const [classes, setClasses] = useState<ClassSession[]>(initialDashboard.classes);
  const [assignments, setAssignments] = useState<Assignment[]>(initialDashboard.assignments);
  const [materials, setMaterials] = useState<Material[]>(initialDashboard.materials);
  const [payments, setPayments] = useState<Payment[]>(initialDashboard.payments);
  const [announcements, setAnnouncements] = useState<Announcement[]>(initialDashboard.announcements);
  const [activities, setActivities] = useState<Activity[]>(initialDashboard.activities);

  useEffect(() => {
    const storedDashboard = readDashboardState(storageKey, user);
    setProfile(storedDashboard.profile);
    setStudents(storedDashboard.students);
    setBatches(storedDashboard.batches);
    setClasses(storedDashboard.classes);
    setAssignments(storedDashboard.assignments);
    setMaterials(storedDashboard.materials);
    setPayments(storedDashboard.payments);
    setAnnouncements(storedDashboard.announcements);
    setActivities(storedDashboard.activities);
  }, [storageKey, user]);

  useEffect(() => {
    if (typeof window === "undefined") return;
    const dashboardState: InstructorDashboardState = {
      profile,
      students,
      batches,
      classes,
      assignments,
      materials,
      payments,
      announcements,
      activities,
    };
    window.localStorage.setItem(storageKey, JSON.stringify(dashboardState));
  }, [activities, announcements, assignments, batches, classes, materials, payments, profile, storageKey, students]);

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

  const deleteBatch: InstructorDashboardContextType["deleteBatch"] = (id) => {
    setBatches((prev) => prev.filter((batch) => batch.id !== id));
    setStudents((prev) => prev.map((student) => (student.batchId === id ? { ...student, batchId: null } : student)));
    setClasses((prev) => prev.filter((session) => session.batchId !== id));
    setMaterials((prev) => prev.filter((material) => material.batchId !== id));
    setAssignments((prev) => prev.filter((assignment) => assignment.assignedTo.type !== "batch" || assignment.assignedTo.id !== id));
    addActivity("Removed a batch");
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
    setProfile((prev) => ({ ...prev, ...updates }));
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
      deleteBatch,
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
