const mongoose = require("mongoose");

const tutorSchema = new mongoose.Schema(
  {
    userId: { type: mongoose.Schema.Types.ObjectId, ref: "User", required: true },
    students: [{ type: mongoose.Schema.Types.ObjectId, ref: "Student" }],
  },
  { timestamps: true },
);

module.exports = mongoose.model("Tutor", tutorSchema);
