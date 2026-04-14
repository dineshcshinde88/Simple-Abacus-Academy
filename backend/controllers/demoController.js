const { validationResult } = require("express-validator");
const { sendEmail } = require("../services/emailService");

const bookDemo = async (req, res) => {
  const errors = validationResult(req);
  if (!errors.isEmpty()) {
    return res.status(400).json({ message: "Invalid request", errors: errors.array() });
  }

  const {
    name,
    email,
    mobile,
    gender,
    motherTongue,
    dob,
    programs,
  } = req.body;

  const formatPrograms = (items) => {
    if (!Array.isArray(items) || items.length === 0) return "N/A";
    if (items.length === 1) return items[0];
    if (items.length === 2) return `${items[0]} & ${items[1]}`;
    return items.join(", ");
  };
  const programsText = formatPrograms(programs);
  const notificationEmail = process.env.DEMO_NOTIFICATION_EMAIL || "simpleabacuspune@gmail.com";

  const subject = "New Free Demo Request";
  const html = `
    <div style="font-family: Arial, sans-serif; color: #111827;">
      <h2 style="margin: 0 0 12px;">Free Demo Booking</h2>
      <table cellpadding="6" cellspacing="0" style="border-collapse: collapse;">
        <tr><td><strong>Name:</strong></td><td>${name}</td></tr>
        <tr><td><strong>Email:</strong></td><td>${email}</td></tr>
        <tr><td><strong>Mobile:</strong></td><td>${mobile}</td></tr>
        <tr><td><strong>Gender:</strong></td><td>${gender || "N/A"}</td></tr>
        <tr><td><strong>Mother Tongue:</strong></td><td>${motherTongue || "N/A"}</td></tr>
        <tr><td><strong>Date of Birth:</strong></td><td>${dob || "N/A"}</td></tr>
        <tr><td><strong>Programs:</strong></td><td>${programsText}</td></tr>
      </table>
    </div>
  `;

  const text = `Free Demo Booking
Name: ${name}
Email: ${email}
Mobile: ${mobile}
Gender: ${gender || "N/A"}
Mother Tongue: ${motherTongue || "N/A"}
Date of Birth: ${dob || "N/A"}
Programs: ${programsText}`;

  await sendEmail({
    to: notificationEmail,
    subject,
    html,
    text,
  });

  return res.json({ message: "Demo request received" });
};

module.exports = { bookDemo };
