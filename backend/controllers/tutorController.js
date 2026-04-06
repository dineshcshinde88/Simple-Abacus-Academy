const bcrypt = require("bcryptjs");
const prisma = require("../config/prisma");

const getProfile = async (req, res) => {
  const tutor = await prisma.tutor.findUnique({
    where: { userId: req.user.id },
    include: { user: true },
  });
  if (!tutor) return res.status(404).json({ message: "Tutor not found" });
  return res.json({ tutor });
};

const getStudents = async (req, res) => {
  const tutor = await prisma.tutor.findUnique({
    where: { userId: req.user.id },
    include: { students: { include: { user: true } } },
  });
  if (!tutor) return res.status(404).json({ message: "Tutor not found" });
  return res.json({ students: tutor.students });
};

const addStudent = async (req, res) => {
  const { name, email, password } = req.body;

  const existing = await prisma.user.findUnique({ where: { email: email.toLowerCase() } });
  if (existing) return res.status(409).json({ message: "Email already registered" });

  const passwordHash = await bcrypt.hash(password, 10);
  const user = await prisma.user.create({
    data: { name, email: email.toLowerCase(), password: passwordHash, role: "student" },
  });
  const student = await prisma.student.create({ data: { userId: user.id } });

  const tutor = await prisma.tutor.findUnique({ where: { userId: req.user.id } });
  if (!tutor) return res.status(404).json({ message: "Tutor not found" });

  await prisma.student.update({
    where: { id: student.id },
    data: { tutorId: tutor.id },
  });

  return res.status(201).json({ student });
};

const assignLevel = async (req, res) => {
  const { studentId } = req.params;
  const { levelId } = req.body;

  const level = await prisma.level.findUnique({ where: { id: levelId } });
  if (!level) return res.status(404).json({ message: "Level not found" });

  const student = await prisma.student.update({
    where: { id: studentId },
    data: { levelId: level.id },
  });

  return res.json({ student });
};

const resolveFileUrl = (req) => {
  if (!req.file) return null;
  const baseUrl = process.env.BASE_URL || "http://localhost:5000";
  return `${baseUrl}/uploads/${req.file.filename}`;
};

const uploadVideo = async (req, res) => {
  const { title, url, levelId } = req.body;
  const tutor = await prisma.tutor.findUnique({ where: { userId: req.user.id } });
  if (!tutor) return res.status(404).json({ message: "Tutor not found" });

  const fileUrl = resolveFileUrl(req);
  const finalUrl = url || fileUrl;
  if (!finalUrl) return res.status(400).json({ message: "Video URL or file is required" });

  const video = await prisma.video.create({
    data: { title, url: finalUrl, levelId, uploadedBy: tutor.id },
  });
  return res.status(201).json({ video });
};

const uploadWorksheet = async (req, res) => {
  const { title, pdfUrl, levelId } = req.body;
  const tutor = await prisma.tutor.findUnique({ where: { userId: req.user.id } });
  if (!tutor) return res.status(404).json({ message: "Tutor not found" });

  const fileUrl = resolveFileUrl(req);
  const finalUrl = pdfUrl || fileUrl;
  if (!finalUrl) return res.status(400).json({ message: "Worksheet PDF URL or file is required" });

  const worksheet = await prisma.worksheet.create({
    data: { title, pdfUrl: finalUrl, levelId, createdBy: tutor.id },
  });
  return res.status(201).json({ worksheet });
};

module.exports = { getProfile, getStudents, addStudent, assignLevel, uploadVideo, uploadWorksheet };
