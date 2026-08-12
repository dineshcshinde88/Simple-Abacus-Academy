import { describe, expect, it } from "vitest";
import {
  calculateAbacusValue,
  createEmptyRods,
  formatAbacusValue,
  numberToRods,
} from "./abacus";

describe("abacus place values", () => {
  it("uses the rightmost rod as ones", () => {
    const rods = createEmptyRods();
    rods[rods.length - 1] = { upperActive: false, lowerActive: 3 };

    expect(calculateAbacusValue(rods)).toBe("3");
    expect(formatAbacusValue(calculateAbacusValue(rods))).toBe("3");
  });

  it("maps every rod as a whole-number place", () => {
    const rods = numberToRods("123456789012345");

    expect(calculateAbacusValue(rods)).toBe("123456789012345");
    expect(formatAbacusValue(calculateAbacusValue(rods))).toBe("123,456,789,012,345");
  });
});
