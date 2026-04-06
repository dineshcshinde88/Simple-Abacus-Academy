const express = require("express");
const authMiddleware = require("../middlewares/authMiddleware");
const roleMiddleware = require("../middlewares/roleMiddleware");
const checkSubscription = require("../middlewares/checkSubscription");
const { body } = require("express-validator");
const validate = require("../middlewares/validate");
const {
  getDashboard,
  getVideos,
  getWorksheets,
  upsertProgress,
  recordVideoHistory,
  recordWorksheetCompletion,
  getProgress,
  getVideoHistory,
  getWorksheetCompletions,
} = require("../controllers/studentController");

const router = express.Router();

router.get("/dashboard", authMiddleware, roleMiddleware("student"), getDashboard);
router.get("/videos", authMiddleware, roleMiddleware("student"), checkSubscription, getVideos);
router.get("/worksheets", authMiddleware, roleMiddleware("student"), checkSubscription, getWorksheets);
router.get("/progress", authMiddleware, roleMiddleware("student"), getProgress);
router.get("/video-history", authMiddleware, roleMiddleware("student"), getVideoHistory);
router.get("/worksheet-completions", authMiddleware, roleMiddleware("student"), getWorksheetCompletions);

router.post(
  "/progress",
  authMiddleware,
  roleMiddleware("student"),
  [body("score").optional().isNumeric(), body("completedLessons").optional().isInt()],
  validate,
  upsertProgress,
);

router.post(
  "/video-history",
  authMiddleware,
  roleMiddleware("student"),
  [body("videoId").notEmpty(), body("progressPercent").optional().isNumeric()],
  validate,
  recordVideoHistory,
);

router.post(
  "/worksheet-completions",
  authMiddleware,
  roleMiddleware("student"),
  [body("worksheetId").notEmpty(), body("score").optional().isNumeric()],
  validate,
  recordWorksheetCompletion,
);

module.exports = router;
