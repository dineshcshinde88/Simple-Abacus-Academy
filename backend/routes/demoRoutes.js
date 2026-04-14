const express = require("express");
const { body } = require("express-validator");
const { bookDemo } = require("../controllers/demoController");

const router = express.Router();

router.post(
  "/book",
  [
    body("name").notEmpty().withMessage("Name is required"),
    body("email").isEmail().withMessage("Valid email is required"),
    body("mobile").notEmpty().withMessage("Mobile is required"),
  ],
  bookDemo,
);

module.exports = router;
