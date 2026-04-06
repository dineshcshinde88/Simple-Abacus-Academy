const mongoose = require("mongoose");

const progressSchema = new mongoose.Schema(
  {
    studentId: { type: mongoose.Schema.Types.ObjectId, ref: "Student", required: true },
    level: { type: mongoose.Schema.Types.ObjectId, ref: "Level" },
    score: { type: Number, default: 0 },
    completedLessons: { type: Number, default: 0 },
  },
  { timestamps: true },
);

module.exports = mongoose.model("Progress", progressSchema);
