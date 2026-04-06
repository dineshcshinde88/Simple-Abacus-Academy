const mongoose = require("mongoose");

const userWorksheetAccessSchema = new mongoose.Schema(
  {
    userId: { type: mongoose.Schema.Types.ObjectId, ref: "User", required: true, index: true },
    worksheetId: { type: mongoose.Schema.Types.ObjectId, ref: "Worksheet", required: true },
  },
  { timestamps: { createdAt: "createdAt", updatedAt: "updatedAt" } },
);

module.exports = mongoose.model("UserWorksheetAccess", userWorksheetAccessSchema);
