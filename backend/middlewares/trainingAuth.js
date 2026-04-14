const jwt = require("jsonwebtoken");
const TrainingUser = require("../models/training/TrainingUser");

const authRequired = async (req, res, next) => {
  const header = req.headers.authorization || "";
  const token = header.startsWith("Bearer ") ? header.slice(7) : null;
  if (!token) return res.status(401).json({ message: "Unauthorized" });

  try {
    const payload = jwt.verify(token, process.env.JWT_SECRET || "change_me");
    const user = await TrainingUser.findById(payload.id).lean();
    if (!user) return res.status(401).json({ message: "Unauthorized" });
    req.user = user;
    return next();
  } catch {
    return res.status(401).json({ message: "Unauthorized" });
  }
};

const requireRole = (...roles) => (req, res, next) => {
  if (!req.user || !roles.includes(req.user.role)) {
    return res.status(403).json({ message: "Forbidden" });
  }
  return next();
};

module.exports = { authRequired, requireRole };
