const mongoose = require("mongoose");

const videoHistorySchema = new mongoose.Schema(
  {
    studentId: { type: mongoose.Schema.Types.ObjectId, ref: "Student", required: true },
    videoId: { type: mongoose.Schema.Types.ObjectId, ref: "Video", required: true },
    watchedAt: { type: Date, default: Date.now },
    progressPercent: { type: Number, default: 0 },
  },
  { timestamps: true },
);

module.exports = mongoose.model("VideoHistory", videoHistorySchema);
