import abacusForSchoolImage from "@/assets/abacus for school.png";
import abacusKitNewAdmissionImage from "@/assets/abacus kit new admission.png";
import abacusKitRegularImage from "@/assets/abacus kit regular.png";
import abacusTshirtImage from "@/assets/abacus t shirt.png";
import abacusToolImage from "@/assets/abacus tool a kit.png";
import certificateImage from "@/assets/certificate.png";
import vedicMathematicsKitImage from "@/assets/Vedic Mathematics Kit.png";

export type TrainingShopOption = {
  label: string;
  price: number;
};

export type TrainingShopProduct = {
  id: string;
  name: string;
  category: "Abacus Kits" | "Tools" | "Apparel" | "Certificates" | "Vedic Maths";
  description: string;
  includes?: string[];
  optionLabel: string;
  image: string;
  options: TrainingShopOption[];
};

const abacusLevels = ["Foundation", "Level 1", "Level 2", "Level 3", "Level 4", "Level 5", "Level 6", "Level 7"];
const regularLevels = ["Level 1", "Level 2", "Level 3", "Level 4", "Level 5", "Level 6", "Level 7"];
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
    options: abacusLevels.map((label) => ({ label, price: 550 })),
  },
  {
    id: "student-abacus-kit-regular",
    name: "Student Abacus Kit (Regular)",
    category: "Abacus Kits",
    description: "Level-wise regular kit for continuing students with books and certificate.",
    includes: ["2 Books (Classwork + Homework)", "1 Certificate"],
    optionLabel: "Level",
    image: abacusKitRegularImage,
    options: regularLevels.map((label) => ({ label, price: 450 })),
  },
  {
    id: "student-abacus-kit-school",
    name: "Student Abacus Kit (For School)",
    category: "Abacus Kits",
    description: "School-ready kit with classwork book, abacus tool, certificate, and medal.",
    includes: ["1 Book (Classwork)", "1 Student Abacus Tool", "1 Certificate", "1 Medal"],
    optionLabel: "Level",
    image: abacusForSchoolImage,
    options: abacusLevels.map((label) => ({ label, price: 320 })),
  },
  {
    id: "student-abacus-tool",
    name: "Student Abacus Tool (1 Piece)",
    category: "Tools",
    description: "Durable student abacus tool available in 15-rod and 17-rod variants.",
    optionLabel: "Option",
    image: abacusToolImage,
    options: [
      { label: "15 Rod", price: 180 },
      { label: "17 Rod", price: 110 },
    ],
  },
  {
    id: "teacher-training-tshirt",
    name: "T-Shirt (1 Piece)",
    category: "Apparel",
    description: "Comfortable branded T-shirt for students, events, and classroom activities.",
    optionLabel: "Size",
    image: abacusTshirtImage,
    options: ["24 Size", "26 Size", "28 Size", "30 Size", "32 Size"].map((label) => ({ label, price: 400 })),
  },
  {
    id: "certificate",
    name: "Certificate",
    category: "Certificates",
    description: "Printed completion certificate for student recognition and records.",
    optionLabel: "Type",
    image: certificateImage,
    options: [{ label: "Certificate", price: 50 }],
  },
  {
    id: "vedic-mathematics-kit",
    name: "Vedic Mathematics Kit",
    category: "Vedic Maths",
    description: "Level-wise Vedic Maths learning kit with book and certificate.",
    includes: ["1 Book", "1 Certificate"],
    optionLabel: "Level",
    image: vedicMathematicsKitImage,
    options: vedicLevels.map((label) => ({ label, price: 310 })),
  },
];

export const trainingShopCategories = ["All", ...Array.from(new Set(trainingShopProducts.map((product) => product.category)))];
