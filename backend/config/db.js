const prisma = require("./prisma");

const connectDB = async () => {
  try {
    await prisma.$connect();
    // eslint-disable-next-line no-console
    console.log("MySQL connected");
  } catch (err) {
    // eslint-disable-next-line no-console
    console.error("MySQL connection error:", err.message);
    process.exit(1);
  }
};

module.exports = connectDB;
