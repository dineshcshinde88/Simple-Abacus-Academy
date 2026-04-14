const { validationResult } = require("express-validator");
const { sendEmail } = require("../services/emailService");

const applyInstructor = async (req, res) => {
  const errors = validationResult(req);
  if (!errors.isEmpty()) {
    return res.status(400).json({ message: "Invalid request", errors: errors.array() });
  }

  const {
    name,
    email,
    mobile,
    gender,
    dob,
    qualification,
    address,
    programs,
  } = req.body;

  const programsText = Array.isArray(programs) && programs.length
    ? programs.length === 2
      ? `${programs[0]} & ${programs[1]}`
      : programs.join(", ")
    : "N/A";

  const notificationEmail = process.env.INSTRUCTOR_NOTIFICATION_EMAIL || "simpleabacuspune@gmail.com";

  const subject = "New Instructor Registration";
  const html = `
    <div style="font-family: Arial, sans-serif; color: #111827;">
      <h2 style="margin: 0 0 12px;">Instructor Registration</h2>
      <table cellpadding="6" cellspacing="0" style="border-collapse: collapse;">
        <tr><td><strong>Name:</strong></td><td>${name}</td></tr>
        <tr><td><strong>Email:</strong></td><td>${email}</td></tr>
        <tr><td><strong>Mobile:</strong></td><td>${mobile}</td></tr>
        <tr><td><strong>Gender:</strong></td><td>${gender || "N/A"}</td></tr>
        <tr><td><strong>Date of Birth:</strong></td><td>${dob || "N/A"}</td></tr>
        <tr><td><strong>Qualification:</strong></td><td>${qualification || "N/A"}</td></tr>
        <tr><td><strong>Address:</strong></td><td>${address}</td></tr>
        <tr><td><strong>Programs:</strong></td><td>${programsText}</td></tr>
      </table>
    </div>
  `;

  const text = `Instructor Registration
Name: ${name}
Email: ${email}
Mobile: ${mobile}
Gender: ${gender || "N/A"}
Date of Birth: ${dob || "N/A"}
Qualification: ${qualification || "N/A"}
Address: ${address}
Programs: ${programsText}`;

  try {
    await sendEmail({ to: notificationEmail, subject, html, text });
    return res.json({ message: "Application received" });
  } catch (error) {
    // eslint-disable-next-line no-console
    console.error("Instructor email failed:", error.message);
    return res.status(500).json({ message: "Unable to send email. Please verify email settings." });
  }
};

module.exports = { applyInstructor };
