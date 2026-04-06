const mongoose = require("mongoose");

const videoSchema = new mongoose.Schema(
  {
    title: { type: String, required: true },
    url: { type: String, required: true },
    level: { type: mongoose.Schema.Types.ObjectId, ref: "Level", required: true },
    uploadedBy: { type: mongoose.Schema.Types.ObjectId, ref: "Tutor", required: true },
  },
  { timestamps: true },
);

module.exports = mongoose.model("Video", videoSchema);
