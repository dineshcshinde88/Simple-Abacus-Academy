const nodemailer = require("nodemailer");

const isEnabled = String(process.env.EMAIL_ENABLED || "false").toLowerCase() === "true";

const transporter = nodemailer.createTransport({
  host: process.env.EMAIL_HOST,
  port: Number(process.env.EMAIL_PORT || 587),
  secure: false,
  auth: {
    user: process.env.EMAIL_USER,
    pass: process.env.EMAIL_PASS,
  },
});

const sendEmail = async ({ to, subject, html, text }) => {
  if (!isEnabled) {
    // eslint-disable-next-line no-console
    console.log("[EMAIL MOCK]", { to, subject });
    return;
  }

  await transporter.sendMail({
    from: process.env.EMAIL_FROM,
    to,
    subject,
    html,
    text,
  });
};

module.exports = { sendEmail };
