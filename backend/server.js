require("dotenv").config();
const express = require("express");
const cors = require("cors");
const morgan = require("morgan");
const connectDB = require("./config/db");
const prisma = require("./config/prisma");
const swaggerUi = require("swagger-ui-express");
const swaggerSpec = require("./config/swagger");
const { uploadDir } = require("./config/upload");
const { notFound, errorHandler } = require("./middlewares/errorHandler");
const authRoutes = require("./routes/authRoutes");
const studentRoutes = require("./routes/studentRoutes");
const tutorRoutes = require("./routes/tutorRoutes");
const adminRoutes = require("./routes/adminRoutes");
const paymentRoutes = require("./routes/paymentRoutes");
const { startReminderCron } = require("./services/reminderService");

const app = express();

app.use(express.json());
app.use(cors({
  origin: process.env.CORS_ORIGIN?.split(",") || [
    "http://localhost:5173",
    "http://127.0.0.1:5173",
    "http://localhost:8080",
    "http://127.0.0.1:8080",
  ],
}));
app.use(morgan("dev"));
app.use("/uploads", express.static(uploadDir));

app.use("/api/docs", swaggerUi.serve, swaggerUi.setup(swaggerSpec));

app.get("/api/health", (_req, res) => {
  res.json({ ok: true });
});

app.use("/api/auth", authRoutes);
app.use("/api/student", studentRoutes);
app.use("/api/tutor", tutorRoutes);
app.use("/api/admin", adminRoutes);
app.use("/api/payments", paymentRoutes);

app.use(notFound);
app.use(errorHandler);

const PORT = process.env.PORT || 5000;

connectDB().then(() => {
  app.listen(PORT, () => {
    // eslint-disable-next-line no-console
    console.log(`Server running on http://localhost:${PORT}`);
  });
  startReminderCron();
});

process.on("SIGINT", async () => {
  await prisma.$disconnect();
  process.exit(0);
});
