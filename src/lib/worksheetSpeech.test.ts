import { describe, expect, it } from "vitest";
import { formatVisualizationQuestionForSpeech } from "./worksheetSpeech";

describe("visualization worksheet speech", () => {
  it("adds clear pauses between abacus rows", () => {
    expect(formatVisualizationQuestionForSpeech("+45\n-12\n+30")).toBe(
      "Start with 45. Minus 12. Plus 30",
    );
  });

  it("speaks inline Vedic Maths operators clearly", () => {
    expect(formatVisualizationQuestionForSpeech("24 x 5 + 10")).toBe(
      "24 multiplied by 5 plus 10",
    );
  });
});
