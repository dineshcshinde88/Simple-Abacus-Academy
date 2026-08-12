const speakOperators = (value: string) =>
  value
    .replace(/[×x]/gi, " multiplied by ")
    .replace(/[÷/]/g, " divided by ")
    .replace(/\+/g, " plus ")
    .replace(/-/g, " minus ")
    .replace(/=/g, " equals ")
    .replace(/\s+/g, " ")
    .trim();

// Full stops create reliable pauses in browser speech engines, including mobile Chrome.
export const formatVisualizationQuestionForSpeech = (question: string) => {
  const lines = question.split(/\r?\n/).map((line) => line.trim()).filter(Boolean);

  if (lines.length <= 1) {
    return speakOperators(lines[0] || question);
  }

  return lines
    .map((line, lineIndex) => {
      const signedNumber = line.match(/^([+-])\s*(.+)$/);
      if (!signedNumber) {
        return lineIndex === 0 ? `Start with ${speakOperators(line)}` : `Then ${speakOperators(line)}`;
      }

      const [, operator, operand] = signedNumber;
      if (lineIndex === 0 && operator === "+") return `Start with ${speakOperators(operand)}`;
      return `${operator === "+" ? "Plus" : "Minus"} ${speakOperators(operand)}`;
    })
    .join(". ");
};
