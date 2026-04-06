const prisma = require("../config/prisma");

const getDashboard = async (req, res) => {
  const student = await prisma.student.findUnique({
    where: { userId: req.user.id },
    include: { user: true, level: true },
  });

  if (!student) {
    return res.status(404).json({ message: "Student not found" });
  }

  let subscriptionStatus = student.subscriptionStatus;
  const now = new Date();
  if (student.subscriptionEnd && student.subscriptionEnd < now) {
    subscriptionStatus = "expired";
    if (student.subscriptionStatus !== "expired") {
      await prisma.student.update({
        where: { id: student.id },
        data: { subscriptionStatus: "expired" },
      });
    }
  }

  const batchesArray = Array.isArray(student.batches) ? student.batches : [];
  const batchesCount = batchesArray.length;

  const [worksheetsCount, videosCount] = await Promise.all([
    student.levelId ? prisma.worksheet.count({ where: { levelId: student.levelId } }) : 0,
    student.levelId ? prisma.video.count({ where: { levelId: student.levelId } }) : 0,
  ]);

  return res.json({
    name: student.user?.name || "Student",
    level: student.level?.levelName || null,
    batchesCount,
    worksheetsCount,
    videosCount,
    subscriptionStatus,
    expiryDate: student.subscriptionEnd,
  });
};

const getVideos = async (req, res) => {
  const student = await prisma.student.findUnique({ where: { userId: req.user.id } });
  if (!student || !student.levelId) {
    return res.status(404).json({ message: "Level not assigned" });
  }

  const videos = await prisma.video.findMany({
    where: { levelId: student.levelId },
    orderBy: { createdAt: "desc" },
  });
  return res.json({ videos });
};

const getWorksheets = async (req, res) => {
  const student = await prisma.student.findUnique({ where: { userId: req.user.id } });
  if (!student || !student.levelId) {
    return res.status(404).json({ message: "Level not assigned" });
  }

  const worksheets = await prisma.worksheet.findMany({
    where: { levelId: student.levelId },
    orderBy: { createdAt: "desc" },
  });
  return res.json({ worksheets });
};

const upsertProgress = async (req, res) => {
  const student = await prisma.student.findUnique({ where: { userId: req.user.id } });
  if (!student) return res.status(404).json({ message: "Student not found" });

  const { levelId, score = 0, completedLessons = 0 } = req.body;
  const resolvedLevelId = levelId || student.levelId;
  if (!resolvedLevelId) {
    return res.status(400).json({ message: "Level is required for progress tracking" });
  }

  const progress = await prisma.progress.upsert({
    where: {
      studentId_levelId: {
        studentId: student.id,
        levelId: resolvedLevelId,
      },
    },
    update: { score, completedLessons },
    create: {
      studentId: student.id,
      levelId: resolvedLevelId,
      score,
      completedLessons,
    },
  });

  return res.json({ progress });
};

const recordVideoHistory = async (req, res) => {
  const student = await prisma.student.findUnique({ where: { userId: req.user.id } });
  if (!student) return res.status(404).json({ message: "Student not found" });

  const { videoId, progressPercent = 0 } = req.body;

  const history = await prisma.videoHistory.upsert({
    where: {
      studentId_videoId: { studentId: student.id, videoId },
    },
    update: { progressPercent, watchedAt: new Date() },
    create: { studentId: student.id, videoId, progressPercent },
  });

  return res.status(201).json({ history });
};

const recordWorksheetCompletion = async (req, res) => {
  const student = await prisma.student.findUnique({ where: { userId: req.user.id } });
  if (!student) return res.status(404).json({ message: "Student not found" });

  const { worksheetId, score = 0 } = req.body;

  const completion = await prisma.worksheetCompletion.upsert({
    where: {
      studentId_worksheetId: { studentId: student.id, worksheetId },
    },
    update: { score, completedAt: new Date() },
    create: { studentId: student.id, worksheetId, score },
  });

  return res.status(201).json({ completion });
};

const getProgress = async (req, res) => {
  const student = await prisma.student.findUnique({ where: { userId: req.user.id } });
  if (!student) return res.status(404).json({ message: "Student not found" });

  const progress = await prisma.progress.findMany({
    where: { studentId: student.id },
    include: { level: true },
  });
  return res.json({ progress });
};

const getVideoHistory = async (req, res) => {
  const student = await prisma.student.findUnique({ where: { userId: req.user.id } });
  if (!student) return res.status(404).json({ message: "Student not found" });

  const history = await prisma.videoHistory.findMany({
    where: { studentId: student.id },
    include: { video: true },
  });
  return res.json({ history });
};

const getWorksheetCompletions = async (req, res) => {
  const student = await prisma.student.findUnique({ where: { userId: req.user.id } });
  if (!student) return res.status(404).json({ message: "Student not found" });

  const completions = await prisma.worksheetCompletion.findMany({
    where: { studentId: student.id },
    include: { worksheet: true },
  });
  return res.json({ completions });
};

module.exports = {
  getDashboard,
  getVideos,
  getWorksheets,
  upsertProgress,
  recordVideoHistory,
  recordWorksheetCompletion,
  getProgress,
  getVideoHistory,
  getWorksheetCompletions,
};
