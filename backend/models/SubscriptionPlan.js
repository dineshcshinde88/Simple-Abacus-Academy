const mongoose = require("mongoose");

const subscriptionPlanSchema = new mongoose.Schema(
  {
    name: { type: String, required: true, unique: true },
    durationDays: { type: Number, required: true },
    price: { type: Number, required: true },
  },
  { timestamps: true },
);

module.exports = mongoose.model("SubscriptionPlan", subscriptionPlanSchema);
