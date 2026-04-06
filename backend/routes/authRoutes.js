const express = require("express");
const { body } = require("express-validator");
const { register, login, me } = require("../controllers/authController");
const validate = require("../middlewares/validate");
const authMiddleware = require("../middlewares/authMiddleware");

const router = express.Router();

router.post(
  "/register",
  [
    body("name").notEmpty(),
    body("email").isEmail(),
    body("password").isLength({ min: 6 }),
    body("role").isIn(["student", "tutor"]),
  ],
  validate,
  register,
);

router.post(
  "/login",
  [body("email").isEmail(), body("password").notEmpty(), body("role").isIn(["student", "tutor", "admin"])],
  validate,
  login,
);

router.get("/me", authMiddleware, me);

module.exports = router;
