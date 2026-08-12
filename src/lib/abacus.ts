export type RodState = {
  upperActive: boolean;
  lowerActive: number;
};

export const ROD_COUNT = 15;
export const MAX_DIGIT = 9;

export const createEmptyRods = (count = ROD_COUNT): RodState[] =>
  Array.from({ length: count }, () => ({ upperActive: false, lowerActive: 0 }));

// Each rod digit is formed from one heaven bead worth 5 and up to four earth beads worth 1.
export const getRodDigit = (rod: RodState) =>
  (rod.upperActive ? 5 : 0) + rod.lowerActive;

// The rightmost rod is ones; every rod to its left is the next whole-number place.
export const calculateAbacusValue = (rods: RodState[]) => {
  const digits = rods
    .map((rod) => String(getRodDigit(rod)))
    .join("")
    .replace(/^0+(?=\d)/, "");

  return digits || "0";
};

// Converts a number into bead states without allowing impossible soroban digits.
export const numberToRods = (value: string | number, count = ROD_COUNT): RodState[] => {
  const [rawInteger = ""] = String(value).split(".");
  const integerDigits = rawInteger.replace(/\D/g, "").slice(-count).padStart(count, "0");

  return integerDigits.split("").map((digit) => {
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
