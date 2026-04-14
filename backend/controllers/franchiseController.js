const { validationResult } = require("express-validator");
const { sendEmail } = require("../services/emailService");

const applyFranchise = async (req, res) => {
  const errors = validationResult(req);
  if (!errors.isEmpty()) {
    return res.status(400).json({ message: "Invalid request", errors: errors.array() });
  }

  const {
    name,
    email,
    mobile,
    location,
    qualification,
    languages,
    plan,
    message,
  } = req.body;

  const notificationEmail = process.env.FRANCHISE_NOTIFICATION_EMAIL || "simpleabacuspune@gmail.com";

  const subject = "New Franchise Application";
  const html = `
    <div style="font-family: Arial, sans-serif; color: #111827;">
      <h2 style="margin: 0 0 12px;">Franchise Application</h2>
      <table cellpadding="6" cellspacing="0" style="border-collapse: collapse;">
        <tr><td><strong>Name:</strong></td><td>${name}</td></tr>
        <tr><td><strong>Email:</strong></td><td>${email}</td></tr>
        <tr><td><strong>Mobile:</strong></td><td>${mobile}</td></tr>
        <tr><td><strong>Location:</strong></td><td>${location}</td></tr>
        <tr><td><strong>Qualification:</strong></td><td>${qualification}</td></tr>
        <tr><td><strong>Languages:</strong></td><td>${languages}</td></tr>
        <tr><td><strong>Plan:</strong></td><td>${plan}</td></tr>
        <tr><td><strong>Message:</strong></td><td>${message || "N/A"}</td></tr>
      </table>
    </div>
  `;

  const text = `Franchise Application
Name: ${name}
Email: ${email}
Mobile: ${mobile}
Location: ${location}
Qualification: ${qualification}
Languages: ${languages}
Plan: ${plan}
Message: ${message || "N/A"}`;

  await sendEmail({
    to: notificationEmail,
    subject,
    html,
    text,
  });

  return res.json({ message: "Application received" });
};

module.exports = { applyFranchise };
