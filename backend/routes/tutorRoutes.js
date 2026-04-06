const express = require("express");
const { body } = require("express-validator");
const authMiddleware = require("../middlewares/authMiddleware");
const roleMiddleware = require("../middlewares/roleMiddleware");
const validate = require("../middlewares/validate");
const { upload } = require("../config/upload");
const {
  getProfile,
  getStudents,
  addStudent,
  assignLevel,
  uploadVideo,
  uploadWorksheet,
} = require("../controllers/tutorController");

const router = express.Router();

router.get("/profile", authMiddleware, roleMiddleware("tutor"), getProfile);
router.get("/students", authMiddleware, roleMiddleware("tutor"), getStudents);

router.post(
  "/add-student",
  authMiddleware,
  roleMiddleware("tutor"),
  [body("name").notEmpty(), body("email").isEmail(), body("password").isLength({ min: 6 })],
  validate,
  addStudent,
);

router.put(
  "/assign-level/:studentId",
  authMiddleware,
  roleMiddleware("tutor"),
  [body("levelId").notEmpty()],
  validate,
  assignLevel,
);

router.post(
  "/upload-video",
  authMiddleware,
  roleMiddleware("tutor"),
  upload.single("file"),
  [body("title").notEmpty(), body("url").optional(), body("levelId").notEmpty()],
  validate,
  uploadVideo,
);

router.post(
  "/upload-worksheet",
  authMiddleware,
  roleMiddleware("tutor"),
  upload.single("file"),
  [body("title").notEmpty(), body("pdfUrl").optional(), body("levelId").notEmpty()],
  validate,
  uploadWorksheet,
);

module.exports = router;
