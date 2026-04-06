const jwt = require("jsonwebtoken");

const signToken = (user) =>
  jwt.sign(
    { id: user.id || user._id?.toString(), email: user.email, role: user.role, name: user.name },
    process.env.JWT_SECRET || "dev_secret_change_me",
    { expiresIn: "7d" },
  );

module.exports = { signToken };
