import { describe, expect, it } from "vitest";
import { abacusSyllabus, vedicMathsSyllabus } from "./courseSyllabus";

describe("document syllabus", () => {
  it("contains Foundation plus Abacus Levels 1-7", () => {
    expect(abacusSyllabus.map((item) => item.level)).toEqual([
      "Level 0", "Level 1", "Level 2", "Level 3", "Level 4", "Level 5", "Level 6", "Level 7",
    ]);
    expect(abacusSyllabus[0].title).toBe("Foundation");
  });

  it("contains all four Vedic Maths levels and the complete Level 4 syllabus", () => {
    expect(vedicMathsSyllabus).toHaveLength(4);
    expect(vedicMathsSyllabus[3].topics).toContain("Duplex Square (2D, 3D, 4D, 5D, 6D)");
    expect(vedicMathsSyllabus[3].topics).toContain("Calendar Calculation Method");
  });
});
