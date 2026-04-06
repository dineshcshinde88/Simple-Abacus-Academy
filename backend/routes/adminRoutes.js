const express = require("express");
const { body } = require("express-validator");
const authMiddleware = require("../middlewares/authMiddleware");
const roleMiddleware = require("../middlewares/roleMiddleware");
const validate = require("../middlewares/validate");
const {
  getStudents,
  getTutors,
  createPlan,
  assignTutor,
  createLevel,
  assignSubscription,
  getStats,
} = require("../controllers/adminController");

const router = express.Router();

router.get("/students", authMiddleware, roleMiddleware("admin"), getStudents);
router.get("/tutors", authMiddleware, roleMiddleware("admin"), getTutors);
router.get("/stats", authMiddleware, roleMiddleware("admin"), getStats);

router.post(
  "/plans",
  authMiddleware,
  roleMiddleware("admin"),
  [body("name").notEmpty(), body("durationDays").isInt(), body("price").isNumeric()],
  validate,
  createPlan,
);

router.put(
  "/assign-tutor/:studentId",
  authMiddleware,
  roleMiddleware("admin"),
  [body("tutorId").notEmpty()],
  validate,
  assignTutor,
);

router.put(
  "/assign-subscription/:studentId",
  authMiddleware,
  roleMiddleware("admin"),
  [body("planId").optional(), body("durationDays").optional().isInt(), body("startDate").optional().isISO8601()],
  validate,
  assignSubscription,
);

router.post(
  "/levels",
  authMiddleware,
  roleMiddleware("admin"),
  [body("levelName").notEmpty(), body("duration").isInt()],
  validate,
  createLevel,
);

module.exports = router;
