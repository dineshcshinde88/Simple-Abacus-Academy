const mongoose = require("mongoose");

const studentSchema = new mongoose.Schema(
  {
    userId: { type: mongoose.Schema.Types.ObjectId, ref: "User", required: true },
    tutorId: { type: mongoose.Schema.Types.ObjectId, ref: "Tutor" },
    level: { type: mongoose.Schema.Types.ObjectId, ref: "Level" },
    subscription: {
      planName: { type: String },
      startDate: { type: Date },
      endDate: { type: Date },
      status: { type: String, enum: ["active", "expired"], default: "expired" },
    },
  },
  { timestamps: true },
);

module.exports = mongoose.model("Student", studentSchema);
