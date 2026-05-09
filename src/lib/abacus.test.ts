import { describe, expect, it } from "vitest";
import {
  calculateAbacusValue,
  createEmptyRods,
  formatAbacusValue,
  numberToRods,
} from "./abacus";

describe("abacus place values", () => {
  it("uses the center marker as ones", () => {
    const rods = createEmptyRods();
    rods[0] = { upperActive: false, lowerActive: 1 };

    expect(calculateAbacusValue(rods)).toBe("10000000");
    expect(formatAbacusValue(calculateAbacusValue(rods))).toBe("10,000,000");
  });

  it("maps every rod around the center marker", () => {
    const rods = numberToRods("12345678.1234567");

    expect(calculateAbacusValue(rods)).toBe("12345678.1234567");
    expect(formatAbacusValue(calculateAbacusValue(rods))).toBe("12,345,678.1234567");
  });
});
