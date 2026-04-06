const { handleWebhook } = require("../services/paymentService");

const paymentWebhook = async (req, res) => {
  const event = await handleWebhook(req.body, req.headers);
  return res.json({ received: true, event });
};

module.exports = { paymentWebhook };
