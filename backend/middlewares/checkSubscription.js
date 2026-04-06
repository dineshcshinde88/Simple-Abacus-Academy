const prisma = require("../config/prisma");

const checkSubscription = async (req, res, next) => {
  const student = await prisma.student.findUnique({ where: { userId: req.user.id } });
  if (!student || !student.subscriptionEnd) {
    return res.status(403).json({ message: "Subscription expired. Please renew." });
  }

  const now = new Date();
  const isActive = student.subscriptionStatus === "active" && new Date(student.subscriptionEnd) > now;

  if (!isActive) {
    await prisma.student.update({
      where: { id: student.id },
      data: { subscriptionStatus: "expired" },
    });
    return res.status(403).json({ message: "Subscription expired. Please renew." });
  }

  return next();
};

module.exports = checkSubscription;
