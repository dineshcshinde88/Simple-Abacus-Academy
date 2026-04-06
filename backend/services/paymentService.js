const handleWebhook = async (payload, headers) => {
  // Placeholder for payment provider webhook verification
  const provider = process.env.PAYMENT_PROVIDER || "stripe";
  return {
    provider,
    receivedAt: new Date().toISOString(),
    payload,
    headers: {
      "payment-signature": headers["payment-signature"],
      "stripe-signature": headers["stripe-signature"],
    },
  };
};

module.exports = { handleWebhook };
