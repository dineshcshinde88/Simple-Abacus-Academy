type CertificateDetails = {
  studentName: string;
  levelName: string;
  courseName?: string | null;
  issuedOn?: Date;
};

const safeFilePart = (value: string) =>
  value.trim().replace(/[^a-z0-9]+/gi, "-").replace(/^-|-$/g, "") || "student";

export const downloadStudentCertificate = async ({
  studentName,
  levelName,
  courseName = "Abacus",
  issuedOn = new Date(),
}: CertificateDetails) => {
  const { jsPDF } = await import("jspdf");
  const pdf = new jsPDF({ orientation: "landscape", unit: "mm", format: "a4" });
  const width = pdf.internal.pageSize.getWidth();
  const height = pdf.internal.pageSize.getHeight();
  const issuedDate = issuedOn.toLocaleDateString("en-IN", {
    day: "2-digit",
    month: "long",
    year: "numeric",
  });
  const certificateId = `SA-${issuedOn.getFullYear()}-${safeFilePart(studentName).slice(0, 8).toUpperCase()}-${safeFilePart(levelName).toUpperCase()}`;

  pdf.setFillColor(255, 252, 247);
  pdf.rect(0, 0, width, height, "F");
  pdf.setDrawColor(91, 33, 182);
  pdf.setLineWidth(2.2);
  pdf.rect(9, 9, width - 18, height - 18);
  pdf.setDrawColor(249, 115, 22);
  pdf.setLineWidth(0.7);
  pdf.rect(14, 14, width - 28, height - 28);

  pdf.setTextColor(220, 38, 38);
  pdf.setFont("helvetica", "bold");
  pdf.setFontSize(25);
  pdf.text("SIMPLE ABACUS", width / 2, 34, { align: "center" });

  pdf.setTextColor(91, 33, 182);
  pdf.setFontSize(30);
  pdf.text("CERTIFICATE OF COMPLETION", width / 2, 62, { align: "center" });

  pdf.setTextColor(71, 85, 105);
  pdf.setFont("helvetica", "normal");
  pdf.setFontSize(14);
  pdf.text("This certificate is proudly presented to", width / 2, 82, { align: "center" });

  pdf.setTextColor(15, 23, 42);
  pdf.setFont("helvetica", "bold");
  pdf.setFontSize(27);
  pdf.text(studentName, width / 2, 103, { align: "center" });
  pdf.setDrawColor(249, 115, 22);
  pdf.setLineWidth(0.6);
  pdf.line(width / 2 - 62, 108, width / 2 + 62, 108);

  pdf.setTextColor(71, 85, 105);
  pdf.setFont("helvetica", "normal");
  pdf.setFontSize(14);
  pdf.text("for successfully completing", width / 2, 124, { align: "center" });

  pdf.setTextColor(91, 33, 182);
  pdf.setFont("helvetica", "bold");
  pdf.setFontSize(21);
  pdf.text(`${courseName || "Abacus"} - ${levelName}`, width / 2, 143, { align: "center" });

  pdf.setTextColor(71, 85, 105);
  pdf.setFont("helvetica", "normal");
  pdf.setFontSize(11);
  pdf.text(`Issued on: ${issuedDate}`, 42, 174, { align: "center" });
  pdf.text("Authorized by Simple Abacus", width - 55, 174, { align: "center" });
  pdf.setFontSize(8);
  pdf.text(`Certificate ID: ${certificateId}`, width / 2, 190, { align: "center" });

  pdf.save(`${safeFilePart(studentName)}-${safeFilePart(levelName)}-certificate.pdf`);
};
