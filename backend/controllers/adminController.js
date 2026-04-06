const prisma = require("../config/prisma");

const getStudents = async (_req, res) => {
  const students = await prisma.student.findMany({
    include: { user: true, level: true, tutor: true },
  });
  return res.json({ students });
};

const getTutors = async (_req, res) => {
  const tutors = await prisma.tutor.findMany({
    include: { user: true, students: true },
  });
  return res.json({ tutors });
};

const createPlan = async (req, res) => {
  const { name, durationDays, price } = req.body;
  const existing = await prisma.subscriptionPlan.findUnique({ where: { name } });
  if (existing) return res.status(409).json({ message: "Plan already exists" });

  const plan = await prisma.subscriptionPlan.create({ data: { name, durationDays, price } });
  return res.status(201).json({ plan });
};

const assignTutor = async (req, res) => {
  const { studentId } = req.params;
  const { tutorId } = req.body;

  const tutor = await prisma.tutor.findUnique({ where: { id: tutorId } });
  const student = await prisma.student.findUnique({ where: { id: studentId } });
  if (!tutor || !student) return res.status(404).json({ message: "Tutor or student not found" });

  const updatedStudent = await prisma.student.update({
    where: { id: studentId },
    data: { tutorId: tutor.id },
  });

  return res.json({ student: updatedStudent, tutor });
};

const createLevel = async (req, res) => {
  const { levelName, duration, description } = req.body;
  const level = await prisma.level.create({ data: { levelName, duration, description } });
  return res.status(201).json({ level });
};

const assignSubscription = async (req, res) => {
  const { studentId } = req.params;
  const { planId, durationDays, startDate } = req.body;

  const student = await prisma.student.findUnique({ where: { id: studentId } });
  if (!student) return res.status(404).json({ message: "Student not found" });

  let plan = null;
  if (planId) {
    plan = await prisma.subscriptionPlan.findUnique({ where: { id: planId } });
    if (!plan) return res.status(404).json({ message: "Plan not found" });
  }

  const start = startDate ? new Date(startDate) : new Date();
  const days = plan ? plan.durationDays : Number(durationDays || 0);
  if (!days) return res.status(400).json({ message: "Duration days is required" });

  const end = new Date(start.getTime() + days * 24 * 60 * 60 * 1000);

  const updated = await prisma.student.update({
    where: { id: studentId },
    data: {
      subscriptionPlan: plan ? plan.name : "Custom Plan",
      subscriptionStart: start,
      subscriptionEnd: end,
      subscriptionStatus: "active",
    },
  });

  return res.json({
    subscription: {
      planName: updated.subscriptionPlan,
      startDate: updated.subscriptionStart,
      endDate: updated.subscriptionEnd,
      status: updated.subscriptionStatus,
    },
  });
};

const getStats = async (_req, res) => {
  const [studentCount, tutorCount, activeSubscriptions] = await Promise.all([
    prisma.student.count(),
    prisma.tutor.count(),
    prisma.student.count({ where: { subscriptionStatus: "active" } }),
  ]);

  return res.json({
    students: studentCount,
    tutors: tutorCount,
    activeSubscriptions,
  });
};

module.exports = {
  getStudents,
  getTutors,
  createPlan,
  assignTutor,
  createLevel,
  assignSubscription,
  getStats,
};
