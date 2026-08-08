import abacusForSchoolImage from "@/assets/abacus for school.png";
import abacusKitNewAdmissionImage from "@/assets/abacus kit new admission.png";
import abacusKitRegularImage from "@/assets/abacus kit regular.png";
import abacusTshirtImage from "@/assets/t-shirt.png";
import abacusToolImage from "@/assets/abacus tool a kit.png";
import certificateImage from "@/assets/certificate.png";
import vedicMathematicsKitNewImage from "@/assets/vedic mathematics kit new.png";

export type TrainingShopOption = {
  label: string;
  price: number;
};

export type TrainingShopProduct = {
  id: string;
  name: string;
  category: "Abacus Kits" | "Abacus Books" | "Tools" | "Apparel" | "Certificates" | "Vedic Maths";
  description: string;
  includes?: string[];
  optionLabel: string;
  image: string;
  options: TrainingShopOption[];
};

const abacusLevels = ["Foundation", "Level 1", "Level 2", "Level 3", "Level 4", "Level 5", "Level 6", "Level 7"];
const abacusBookLevels = ["Level 0", "Level 1", "Level 2", "Level 3", "Level 4", "Level 5", "Level 6", "Level 7"];
const vedicLevels = ["Level 1", "Level 2", "Level 3", "Level 4"];

export const trainingShopProducts: TrainingShopProduct[] = [
  {
    id: "student-abacus-kit-new-admission",
    name: "Student Abacus Kit (New Admission)",
    category: "Abacus Kits",
    description: "Complete starter kit for new abacus students with books, tool, and certificate.",
    includes: ["2 Books (Classwork + Homework)", "1 Student Abacus Tool", "1 Certificate"],
    optionLabel: "Level",
    image: abacusKitNewAdmissionImage,
    options: abacusLevels.map((label) => ({ label, price: 332 })),
  },
  {
    id: "student-abacus-kit-school",
    name: "Student Abacus Kit (For School)",
    category: "Abacus Kits",
    description: "School-ready kit with a classwork book, student abacus tool, and certificate.",
    includes: ["1 Book (Classwork)", "1 Student Abacus Tool", "1 Certificate"],
    optionLabel: "Level",
    image: abacusForSchoolImage,
    options: abacusLevels.map((label) => ({ label, price: 199 })),
  },
  {
    id: "student-abacus-tool",
    name: "Student Abacus Tool (1 Piece)",
    category: "Tools",
    description: "Durable student abacus tool available in 7-rod and 15-rod variants.",
    optionLabel: "Option",
    image: abacusToolImage,
    options: [
      { label: "7 Rod", price: 79 },
      { label: "15 Rod", price: 99 },
    ],
  },
  {
    id: "teacher-training-tshirt",
    name: "T-Shirt (1 Piece)",
    category: "Apparel",
    description: "Comfortable branded T-shirt for students, events, and classroom activities.",
    optionLabel: "Size",
    image: abacusTshirtImage,
    options: [
      ...["24 Size", "26 Size", "28 Size", "30 Size", "32 Size"].map((label) => ({ label, price: 400 })),
      { label: "34 Size", price: 450 },
      { label: "36 Size", price: 450 },
    ],
  },
  {
    id: "certificate",
    name: "Certificate",
    category: "Certificates",
    description: "Printed completion certificate for student recognition and records.",
    optionLabel: "Type",
    image: certificateImage,
    options: [{ label: "Certificate", price: 35 }],
  },
  {
    id: "vedic-mathematics-kit",
    name: "Vedic Mathematics Kit",
    category: "Vedic Maths",
    description: "Level-wise Vedic Maths learning kit with book and certificate.",
    includes: ["1 Book", "1 Certificate"],
    optionLabel: "Level",
    image: vedicMathematicsKitNewImage,
    options: vedicLevels.map((label) => ({ label, price: 99 })),
  },
  {
    id: "level-wise-abacus-book",
    name: "Level-wise Abacus Book",
    category: "Abacus Books",
    description: "Level-wise Abacus book for Foundation through Level 7.",
    includes: ["1 Book"],
    optionLabel: "Book / Level",
    image: abacusKitRegularImage,
    options: [
      ...abacusBookLevels.map((level) => ({ label: `Classwork - ${level}`, price: 99 })),
      ...abacusBookLevels.map((level) => ({ label: `Homework - ${level}`, price: 99 })),
    ],
  },
];

export const trainingShopCategories = ["All", ...Array.from(new Set(trainingShopProducts.map((product) => product.category)))];
