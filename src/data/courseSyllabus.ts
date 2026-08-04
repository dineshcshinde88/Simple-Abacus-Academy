export type SyllabusLevel = {
  level: string;
  title?: string;
  topics: string[];
};

export const abacusSyllabus: SyllabusLevel[] = [
  { level: "Level 0", title: "Foundation", topics: ["Direct Method - Mixed Addition & Subtraction (Single Digit)"] },
  { level: "Level 1", topics: ["Big Friend Addition & Subtraction"] },
  { level: "Level 2", topics: [
    "3 Rows - Single Digit Addition", "3 Rows - Single Digit Subtraction", "3 Rows - Mixed Addition & Subtraction (Single Digit)",
    "4 Rows - Single Digit Addition", "4 Rows - Mixed Addition & Subtraction (Single Digit)", "5 Rows - Single Digit Addition", "5 Rows - Mixed Addition & Subtraction (Single Digit)",
  ] },
  { level: "Level 3", topics: [
    "5 Rows - Single Digit Addition", "5 Rows - Mixed Addition & Subtraction (Single Digit)", "10 Rows - Single Digit Addition", "10 Rows - Mixed Addition & Subtraction (Single Digit)",
    "3 Rows - Two Digit Addition", "3 Rows - Two Digit Subtraction", "3 Rows - Mixed Addition & Subtraction (Two Digit)",
    "4 Rows - Two Digit Addition", "4 Rows - Two Digit Subtraction", "4 Rows - Mixed Addition & Subtraction (Two Digit)",
    "6 Rows - Two Digit Addition", "6 Rows - Mixed Addition & Subtraction (Two Digit)",
    "10 Rows - Two Digit Addition", "10 Rows - Two Digit Subtraction", "10 Rows - Mixed Addition & Subtraction (Two Digit)",
    "Multiplication (2D×1D, 2D×2D, 3D×1D)",
  ] },
  { level: "Level 4", topics: [
    "3 Rows - Two Digit Addition", "3 Rows - Two Digit Subtraction", "3 Rows - Mixed Addition & Subtraction (Two Digit)",
    "10 Rows - Two Digit Addition", "10 Rows - Two Digit Subtraction", "10 Rows - Mixed Addition & Subtraction (Two Digit)",
    "3 Rows - Three Digit Addition", "3 Rows - Three Digit Subtraction", "3 Rows - Mixed Addition & Subtraction (Three Digit)",
    "4 Rows - Three Digit Addition", "4 Rows - Three Digit Subtraction", "4 Rows - Mixed Addition & Subtraction (Three Digit)",
    "5 Rows - Three Digit Addition", "5 Rows - Three Digit Subtraction", "5 Rows - Mixed Addition & Subtraction (Three Digit)",
    "Multiplication (2D×1D, 3D×1D, 2D×2D)",
  ] },
  { level: "Level 5", topics: [
    "3 Rows - Three Digit Addition", "3 Rows - Three Digit Subtraction", "3 Rows - Mixed Addition & Subtraction (Three Digit)",
    "5 Rows - Three Digit Addition", "5 Rows - Three Digit Subtraction", "5 Rows - Mixed Addition & Subtraction (Three Digit)",
    "Multiplication (2D×1D, 3D×1D, 2D×2D)", "Division (2D÷1D, 3D÷1D, 4D÷1D)",
    "10 Rows - Single Digit Addition", "10 Rows - Mixed Addition & Subtraction (Single Digit)",
    "4 Rows - Single Digit Decimal Addition", "4 Rows - Mixed Decimal Addition & Subtraction (Single Digit)",
    "10 Rows - Single Decimal Digit Addition", "10 Rows - Mixed Decimal Addition & Subtraction (Single Digit)",
  ] },
  { level: "Level 6", topics: [
    "4 Rows - Three Digit Addition", "4 Rows - Three Digit Subtraction", "4 Rows - Mixed Addition & Subtraction (Three Digit)",
    "5 Rows - Three Digit Addition", "5 Rows - Three Digit Subtraction", "5 Rows - Mixed Addition & Subtraction (Three Digit)",
    "5 Rows - Four Digit Addition", "5 Rows - Four Digit Subtraction", "5 Rows - Mixed Addition & Subtraction (Four Digit)",
    "4 Rows - Two Digit Decimal Addition", "4 Rows - Two Digit Decimal Subtraction", "4 Rows - Mixed Decimal Addition & Subtraction (Two Digit)",
    "5 Rows - Two Digit Decimal Addition", "5 Rows - Two Digit Decimal Subtraction", "5 Rows - Mixed Decimal Addition & Subtraction (Two Digit)",
    "Multiplication (4D×1D, 5D×1D, 3D×2D, 3D×3D, 4D×2D)", "Division (3D÷1D)",
  ] },
  { level: "Level 7", topics: [
    "5 Rows - Four Digit Addition", "5 Rows - Four Digit Subtraction", "5 Rows - Mixed Addition & Subtraction (Four Digit)",
    "6 Rows - Four Digit Addition", "6 Rows - Four Digit Subtraction", "6 Rows - Mixed Addition & Subtraction (Four Digit)",
    "7 Rows - Four Digit Addition", "7 Rows - Four Digit Subtraction", "7 Rows - Mixed Addition & Subtraction (Four Digit)",
    "Multiplication (3D×3D, 4D×4D)", "Division (4D÷1D, 4D÷2D)",
  ] },
];

export const vedicMathsSyllabus: SyllabusLevel[] = [
  { level: "Level 1", topics: [
    "Complement (Base 100, 1000, 10,000)", "Subtraction (2D, 3D, 4D)", "Multiplication (2D×2D, 3D×2D, 4D×2D, 5D×2D, 6D×2D)",
    "99 Method Multiplication (2D×2D, 3D×3D, 4D×4D)", "Above Base Method (Base 10, 100, 1000)", "Below Base Method (Base 100, 1000, 10,000)",
    "Vertical & Crosswise Multiplication (2D)", "Square (2D)",
  ] },
  { level: "Level 2", topics: [
    "Square (3D)", "Square Root (3D, 4D)", "Cube (2D)", "Cube Root (4D, 5D, 6D)",
    "Multiplication - First Same, Last Add 10; Last Same, First Add 10", "Multiplication - Factorwise (4D, 5D)",
    "Division - Above Base (Base 10); Below Base (Base 100)", "Division - Factorwise (4D, 5D)",
  ] },
  { level: "Level 3", topics: [
    "Division by 5 (3D, 4D)", "Division by 25 (3D, 4D)", "Division by 50 and 125", "Division - General Method",
    "Fraction Addition, Subtraction, Multiplication & Division", "Vertical & Crosswise Multiplication (3D)",
    "Mixed Base Multiplication - Base 100 (3D×2D, 2D×3D)", "Mixed Base Multiplication - Base 1000 (4D×3D, 3D×4D)",
  ] },
  { level: "Level 4", topics: [
    "Vertical & Crosswise Multiplication (4D)", "Duplex Square (2D, 3D, 4D, 5D, 6D)", "Cube (3D)", "Square Root (6D)",
    "Cube Root (8D, 9D - Odd)", "Cube Root (8D, 9D - Even)", "Calendar Calculation Method",
  ] },
];
