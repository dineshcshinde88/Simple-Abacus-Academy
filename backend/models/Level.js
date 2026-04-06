const mongoose = require("mongoose");

const levelSchema = new mongoose.Schema(
  {
    levelName: { type: String, required: true },
    duration: { type: Number, required: true },
    description: { type: String },
  },
  { timestamps: true },
);

module.exports = mongoose.model("Level", levelSchema);
