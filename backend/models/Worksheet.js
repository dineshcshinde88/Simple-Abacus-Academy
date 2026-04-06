const mongoose = require("mongoose");

const worksheetSchema = new mongoose.Schema(
  {
    title: { type: String, required: true },
    level: { type: mongoose.Schema.Types.ObjectId, ref: "Level", required: true },
    pdfUrl: { type: String, required: true },
    createdBy: { type: mongoose.Schema.Types.ObjectId, ref: "Tutor", required: true },
  },
  { timestamps: true },
);

module.exports = mongoose.model("Worksheet", worksheetSchema);
