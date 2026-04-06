const mongoose = require("mongoose");

const worksheetCompletionSchema = new mongoose.Schema(
  {
    studentId: { type: mongoose.Schema.Types.ObjectId, ref: "Student", required: true },
    worksheetId: { type: mongoose.Schema.Types.ObjectId, ref: "Worksheet", required: true },
    completedAt: { type: Date, default: Date.now },
    score: { type: Number, default: 0 },
  },
  { timestamps: true },
);

module.exports = mongoose.model("WorksheetCompletion", worksheetCompletionSchema);
