const cron = require("node-cron");
const prisma = require("../config/prisma");
const { sendEmail } = require("./emailService");

const startReminderCron = () => {
  cron.schedule("0 9 * * *", async () => {
    const now = new Date();
    const reminderDate = new Date(now.getTime() + 3 * 24 * 60 * 60 * 1000);

    const students = await prisma.student.findMany({
      where: {
        subscriptionStatus: "active",
        subscriptionEnd: {
          lte: reminderDate,
          gte: now,
        },
      },
      include: { user: true },
    });

    for (const student of students) {
      const email = student.user?.email;
      if (!email) continue;
      await sendEmail({
        to: email,
        subject: "Subscription Expiring Soon",
        text: "Your subscription will expire in 3 days. Please renew to keep access.",
      });
    }
  });
};

module.exports = { startReminderCron };
