import { describe, expect, it } from "vitest";
import {
  calculateAbacusValue,
  createEmptyRods,
  formatAbacusValue,
  numberToRods,
} from "./abacus";

describe("abacus place values", () => {
  it("uses the yellow center-marker rod as ones", () => {
    const rods = createEmptyRods();
    rods[Math.floor(rods.length / 2)] = { upperActive: false, lowerActive: 3 };

    expect(calculateAbacusValue(rods)).toBe("3");
    expect(formatAbacusValue(calculateAbacusValue(rods))).toBe("3");
  });

  it("maps whole-number digits through the center rod", () => {
    const rods = numberToRods("12345678");

    expect(calculateAbacusValue(rods)).toBe("12345678");
    expect(formatAbacusValue(calculateAbacusValue(rods))).toBe("12,345,678");
  });

  it("ignores practice rods to the right of the center marker", () => {
    const rods = createEmptyRods();
    rods[Math.floor(rods.length / 2) + 1] = { upperActive: true, lowerActive: 4 };

    expect(calculateAbacusValue(rods)).toBe("0");
  });
});
