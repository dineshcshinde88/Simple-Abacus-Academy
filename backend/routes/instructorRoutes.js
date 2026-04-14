const express = require("express");
const { body } = require("express-validator");
const { applyInstructor } = require("../controllers/instructorController");

const router = express.Router();

router.post(
  "/apply",
  [
    body("name").notEmpty(),
    body("email").isEmail(),
    body("mobile").notEmpty(),
    body("address").notEmpty(),
  ],
  applyInstructor,
);

module.exports = router;
