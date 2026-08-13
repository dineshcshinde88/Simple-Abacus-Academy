export type RodState = {
  upperActive: boolean;
  lowerActive: number;
};

export const ROD_COUNT = 15;
export const MAX_DIGIT = 9;
export const UNIT_ROD_INDEX = Math.floor(ROD_COUNT / 2);

export const createEmptyRods = (count = ROD_COUNT): RodState[] =>
  Array.from({ length: count }, () => ({ upperActive: false, lowerActive: 0 }));

// Each rod digit is formed from one heaven bead worth 5 and up to four earth beads worth 1.
export const getRodDigit = (rod: RodState) =>
  (rod.upperActive ? 5 : 0) + rod.lowerActive;

// The yellow center marker is ones. Rods to its left are higher whole-number places.
// Rods to its right are available for bead practice but are not part of the displayed count.
export const calculateAbacusValue = (rods: RodState[], unitRodIndex = Math.floor(rods.length / 2)) => {
  const digits = rods
    .slice(0, unitRodIndex + 1)
    .map((rod) => String(getRodDigit(rod)))
    .join("")
    .replace(/^0+(?=\d)/, "");

  return digits || "0";
};

// Converts a number into bead states without allowing impossible soroban digits.
export const numberToRods = (value: string | number, count = ROD_COUNT): RodState[] => {
  const [rawInteger = ""] = String(value).split(".");
  const unitRodIndex = Math.floor(count / 2);
  const integerDigits = rawInteger.replace(/\D/g, "").slice(-(unitRodIndex + 1)).padStart(unitRodIndex + 1, "0");
  const digits = `${integerDigits}${"0".repeat(count - unitRodIndex - 1)}`;

  return digits.split("").map((digit) => {
    const parsed = Math.min(Number(digit), MAX_DIGIT);
    return {
      upperActive: parsed >= 5,
      lowerActive: parsed % 5,
    };
  });
};

export const formatAbacusValue = (value: string | number) => {
  const rawValue = String(value);
  const [integerPart, decimalPart] = rawValue.split(".");

  try {
    const formattedInteger = new Intl.NumberFormat("en-US").format(BigInt(integerPart || "0"));
    return decimalPart ? `${formattedInteger}.${decimalPart}` : formattedInteger;
  } catch {
    return rawValue;
  }
};
