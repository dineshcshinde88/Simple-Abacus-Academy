const bcrypt = require("bcryptjs");
const prisma = require("../config/prisma");
const { signToken } = require("../utils/token");

const register = async (req, res) => {
  const { name, email, password, role } = req.body;

  const existing = await prisma.user.findUnique({ where: { email: email.toLowerCase() } });
  if (existing) {
    return res.status(409).json({ message: "Email already registered" });
  }

  const passwordHash = await bcrypt.hash(password, 10);
  const user = await prisma.user.create({
    data: {
      name,
      email: email.toLowerCase(),
      password: passwordHash,
      role,
    },
  });

  if (role === "student") {
    await prisma.student.create({ data: { userId: user.id } });
  }
  if (role === "tutor") {
    await prisma.tutor.create({ data: { userId: user.id } });
  }

  const token = signToken(user);
  return res.status(201).json({ token, role: user.role, name: user.name, user });
};

const login = async (req, res) => {
  const { email, password, role } = req.body;

  const user = await prisma.user.findUnique({ where: { email: email.toLowerCase() } });
  if (!user) {
    return res.status(401).json({ message: "Invalid email or password" });
  }

  if (role && user.role !== role) {
    return res.status(401).json({ message: "Invalid role" });
  }

  const isMatch = await bcrypt.compare(password, user.password);
  if (!isMatch) {
    return res.status(401).json({ message: "Invalid email or password" });
  }

  const token = signToken(user);
  return res.json({ token, role: user.role, name: user.name, user });
};

const me = async (req, res) => {
  const user = await prisma.user.findUnique({ where: { id: req.user.id } });
  if (!user) return res.status(404).json({ message: "User not found" });
  return res.json({ user: { id: user.id, name: user.name, email: user.email, role: user.role } });
};

module.exports = { register, login, me };
