const express = require("express");
const { body } = require("express-validator");
const { applyFranchise } = require("../controllers/franchiseController");

const router = express.Router();

router.post(
  "/apply",
  [
    body("name").notEmpty().withMessage("Name is required"),
    body("email").isEmail().withMessage("Valid email is required"),
    body("mobile").notEmpty().withMessage("Mobile is required"),
    body("location").notEmpty().withMessage("Location is required"),
    body("qualification").notEmpty().withMessage("Qualification is required"),
    body("languages").notEmpty().withMessage("Languages are required"),
    body("plan").notEmpty().withMessage("Plan is required"),
  ],
  applyFranchise,
);

module.exports = router;
