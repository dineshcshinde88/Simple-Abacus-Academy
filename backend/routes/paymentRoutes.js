const express = require("express");
const { paymentWebhook } = require("../controllers/paymentController");

const router = express.Router();

router.post("/webhook", paymentWebhook);

module.exports = router;
