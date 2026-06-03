import fs from "node:fs";
import path from "node:path";
import zlib from "node:zlib";

const repoRoot = process.cwd();
const defaultInput = path.join(repoRoot, "assets", "practice-papers");
const fallbackInput = path.join(repoRoot, "backend", "uploads", "practise paper");
const inputRoot = path.resolve(getArgValue("--input") || (fs.existsSync(defaultInput) ? defaultInput : fallbackInput));
const reportPath = path.join(inputRoot, "conversion-summary.json");

const XML_ENTITIES = {
  amp: "&",
  lt: "<",
  gt: ">",
  quot: "\"",
  apos: "'",
};

main();

function main() {
  const summary = {
    inputRoot,
    generatedAt: new Date().toISOString(),
    totalCategories: 0,
    totalSets: 0,
    totalQuestionsImported: 0,
    missingAnswers: 0,
    conversionErrors: [],
    sets: [],
  };

  if (!fs.existsSync(inputRoot)) {
    console.error(`Input folder not found: ${inputRoot}`);
    process.exitCode = 1;
    return;
  }

  const files = findDocxFiles(inputRoot);
  if (files.length === 0) {
    console.warn(`No .docx files found in ${inputRoot}`);
    writeJson(reportPath, summary);
    return;
  }

  const debugPattern = getArgValue("--debug-file");
  if (debugPattern) {
    const debugFile = files.find((file) => file.toLowerCase().includes(debugPattern.toLowerCase()));
    if (!debugFile) {
      console.error(`Debug file not found for pattern: ${debugPattern}`);
      process.exitCode = 1;
      return;
    }
    const xml = readDocxEntry(debugFile, "word/document.xml");
    const lines = extractDocumentLines(xml);
    console.log(`Debug file: ${path.relative(repoRoot, debugFile)}`);
    for (const line of lines.slice(0, 20)) {
      console.log(JSON.stringify(line.split("\t")));
    }
    return;
  }

  const categoryNames = new Set();

  for (const filePath of files) {
    const errors = [];
    const meta = inferPaperMeta(filePath, inputRoot);
    categoryNames.add(meta.category);

    try {
      const xml = readDocxEntry(filePath, "word/document.xml");
      const lines = extractDocumentLines(xml);
      const parsed = parseQuestions(lines, errors);
      const questions = validateQuestions(parsed.questions, parsed.answerMap, errors);
      const missingAnswers = questions.filter((question) => !question.correctAnswer).length;
      const output = {
        category: meta.category,
        paperCode: `#PP-${meta.category}-${String(meta.setNumber).padStart(3, "0")}`,
        setNumber: meta.setNumber,
        duration: 3,
        totalQuestions: questions.length,
        questions,
      };

      fs.mkdirSync(meta.outputDir, { recursive: true });
      writeJson(meta.outputPath, output);

      summary.totalSets += 1;
      summary.totalQuestionsImported += questions.length;
      summary.missingAnswers += missingAnswers;
      summary.sets.push({
        category: meta.category,
        setName: meta.setName,
        source: path.relative(repoRoot, filePath),
        output: path.relative(repoRoot, meta.outputPath),
        questionsImported: questions.length,
        missingAnswers,
        errorsFound: errors.length,
        errors,
      });

      logSet(meta.category, meta.setName, questions.length, errors);
    } catch (error) {
      const message = error instanceof Error ? error.message : String(error);
      const item = {
        category: meta.category,
        setName: meta.setName,
        source: path.relative(repoRoot, filePath),
        message,
      };
      summary.conversionErrors.push(item);
      console.error(`[${meta.category}] ${meta.setName}: failed - ${message}`);
    }
  }

  summary.totalCategories = categoryNames.size;
  writeJson(reportPath, summary);
  printSummary(summary);

  if (summary.conversionErrors.length > 0) {
    process.exitCode = 1;
  }
}

function getArgValue(name) {
  const exact = process.argv.find((arg) => arg.startsWith(`${name}=`));
  if (exact) {
    return exact.slice(name.length + 1);
  }
  const index = process.argv.indexOf(name);
  return index >= 0 ? process.argv[index + 1] : "";
}

function findDocxFiles(root) {
  const results = [];
  const entries = fs.readdirSync(root, { withFileTypes: true });
  for (const entry of entries) {
    const fullPath = path.join(root, entry.name);
    if (entry.isDirectory()) {
      results.push(...findDocxFiles(fullPath));
    } else if (entry.isFile() && entry.name.toLowerCase().endsWith(".docx") && !entry.name.startsWith("~$")) {
      results.push(fullPath);
    }
  }
  return results.sort((a, b) => a.localeCompare(b, undefined, { numeric: true, sensitivity: "base" }));
}

function inferPaperMeta(filePath, root) {
  const fileName = path.basename(filePath, ".docx");
  const parentName = path.basename(path.dirname(filePath));
  const relativeParts = path.relative(root, filePath).split(path.sep);
  const fileCategory = matchFirst(fileName, [
    /\bCategory\s+([A-Z]{1,3}\d*)\b/i,
    /\bCat(?:egory)?[-_\s]*([A-Z]{1,3}\d*)\b/i,
  ]);
  const parentCategory = relativeParts.length > 1 && !/\bset\s*\d+\b/i.test(parentName) ? parentName : "";
  const category = sanitizeName((fileCategory || parentCategory || "General").toUpperCase());
  const setText = matchFirst(`${fileName} ${parentName}`, [
    /\bSet\s*[-_ ]?(\d+)\b/i,
    /\bPractice\s+paper\s*[-_ ]?Set\s*(\d+)\b/i,
  ]);
  const setNumber = Number.parseInt(setText || "1", 10) || 1;
  const setName = `Set ${setNumber}`;

  return {
    category,
    setNumber,
    setName,
    outputDir: path.join(root, category),
    outputPath: path.join(root, category, `set${setNumber}.json`),
  };
}

function matchFirst(text, patterns) {
  for (const pattern of patterns) {
    const match = text.match(pattern);
    if (match?.[1]) {
      return match[1].trim();
    }
  }
  return "";
}

function sanitizeName(value) {
  return value.replace(/[^\p{L}\p{N}_-]+/gu, "-").replace(/^-+|-+$/g, "") || "General";
}

function readDocxEntry(filePath, entryName) {
  const buffer = fs.readFileSync(filePath);
  const entries = readZipEntries(buffer);
  const entry = entries.find((item) => item.name === entryName);
  if (!entry) {
    throw new Error(`Invalid Word file: missing ${entryName}`);
  }

  const data = buffer.subarray(entry.dataOffset, entry.dataOffset + entry.compressedSize);
  if (entry.compressionMethod === 0) {
    return data.toString("utf8");
  }
  if (entry.compressionMethod === 8) {
    return zlib.inflateRawSync(data).toString("utf8");
  }
  throw new Error(`Unsupported DOCX compression method ${entry.compressionMethod}`);
}

function readZipEntries(buffer) {
  const eocdOffset = findEndOfCentralDirectory(buffer);
  if (eocdOffset < 0) {
    throw new Error("Invalid Word file: ZIP directory not found");
  }

  const totalEntries = buffer.readUInt16LE(eocdOffset + 10);
  const centralDirectoryOffset = buffer.readUInt32LE(eocdOffset + 16);
  const entries = [];
  let offset = centralDirectoryOffset;

  for (let i = 0; i < totalEntries; i += 1) {
    if (buffer.readUInt32LE(offset) !== 0x02014b50) {
      throw new Error("Invalid Word file: corrupt ZIP central directory");
    }

    const compressionMethod = buffer.readUInt16LE(offset + 10);
    const compressedSize = buffer.readUInt32LE(offset + 20);
    const fileNameLength = buffer.readUInt16LE(offset + 28);
    const extraLength = buffer.readUInt16LE(offset + 30);
    const commentLength = buffer.readUInt16LE(offset + 32);
    const localHeaderOffset = buffer.readUInt32LE(offset + 42);
    const name = buffer.subarray(offset + 46, offset + 46 + fileNameLength).toString("utf8");

    if (buffer.readUInt32LE(localHeaderOffset) !== 0x04034b50) {
      throw new Error(`Invalid Word file: corrupt ZIP local header for ${name}`);
    }

    const localNameLength = buffer.readUInt16LE(localHeaderOffset + 26);
    const localExtraLength = buffer.readUInt16LE(localHeaderOffset + 28);
    const dataOffset = localHeaderOffset + 30 + localNameLength + localExtraLength;
    entries.push({ name, compressionMethod, compressedSize, dataOffset });
    offset += 46 + fileNameLength + extraLength + commentLength;
  }

  return entries;
}

function findEndOfCentralDirectory(buffer) {
  const minOffset = Math.max(0, buffer.length - 0xffff - 22);
  for (let offset = buffer.length - 22; offset >= minOffset; offset -= 1) {
    if (buffer.readUInt32LE(offset) === 0x06054b50) {
      return offset;
    }
  }
  return -1;
}

function extractDocumentLines(xml) {
  const body = xml.match(/<w:body[\s\S]*<\/w:body>/)?.[0] || xml;
  const blocks = body.match(/<w:tbl[\s\S]*?<\/w:tbl>|<w:p[\s\S]*?<\/w:p>/g) || [];
  const lines = [];

  for (const block of blocks) {
    if (block.startsWith("<w:tbl")) {
      const rows = block.match(/<w:tr[\s\S]*?<\/w:tr>/g) || [];
      for (const row of rows) {
        const cells = (row.match(/<w:tc[\s\S]*?<\/w:tc>/g) || [])
          .map((cell) => normalizeLine(extractText(cell)));
        if (cells.some(Boolean)) {
          lines.push(cells.join("\t"));
        }
      }
    } else {
      const line = normalizeLine(extractText(block));
      if (line) {
        lines.push(line);
      }
    }
  }

  return lines;
}

function extractText(xml) {
  return decodeXml(
    xml
      .replace(/<w:tab\s*\/>/g, "\t")
      .replace(/<w:br\s*\/>/g, "\n")
      .replace(/<w:t\b[^>]*>([\s\S]*?)<\/w:t>/g, "$1")
      .replace(/<[^>]+>/g, " ")
  );
}

function decodeXml(value) {
  return value.replace(/&(#x?[0-9a-f]+|[a-z]+);/gi, (entity, key) => {
    if (key[0] === "#") {
      const isHex = key[1]?.toLowerCase() === "x";
      const codePoint = Number.parseInt(key.slice(isHex ? 2 : 1), isHex ? 16 : 10);
      return Number.isFinite(codePoint) ? String.fromCodePoint(codePoint) : entity;
    }
    return XML_ENTITIES[key] || entity;
  });
}

function normalizeLine(value) {
  return value
    .replace(/\u00a0/g, " ")
    .replace(/[ \t]+/g, " ")
    .replace(/\s+([?.!,;:])/g, "$1")
    .trim();
}

function parseQuestions(lines, errors) {
  const gridQuestions = parseGridQuestions(lines);
  if (gridQuestions.length > 0) {
    return { questions: gridQuestions, answerMap: new Map() };
  }

  const questions = [];
  const answerMap = new Map();
  let current = null;
  let inAnswerKey = false;

  for (const rawLine of lines) {
    const tableQuestion = rawLine.includes("\t") ? parseTableQuestion(rawLine) : null;
    if (tableQuestion && !inAnswerKey) {
      commitQuestion(current, questions);
      current = tableQuestion;
      continue;
    }

    const line = normalizeLine(rawLine);
    if (!line) {
      continue;
    }

    if (/^(answer\s*key|answers?|correct\s*answers?)\b/i.test(line)) {
      inAnswerKey = true;
      parseAnswerLine(line, answerMap);
      continue;
    }

    if (inAnswerKey) {
      parseAnswerLine(line, answerMap);
      continue;
    }

    const questionStart = line.match(/^Q?\s*(\d{1,3})[\).:\-\s]+(.+)?$/i);
    if (questionStart && !looksLikeOptionOnly(line)) {
      commitQuestion(current, questions);
      current = {
        id: Number.parseInt(questionStart[1], 10),
        questionParts: [normalizeLine(questionStart[2] || "")].filter(Boolean),
        options: [],
        correctAnswer: "",
      };
      continue;
    }

    const option = parseOption(line);
    if (option && current) {
      current.options[option.index] = option.text;
      continue;
    }

    const answer = parseInlineAnswer(line);
    if (answer && current) {
      current.correctAnswer = answer;
      continue;
    }

    if (current) {
      if (current.options.length === 0) {
        current.questionParts.push(line);
      } else {
        const lastIndex = current.options.length - 1;
        current.options[lastIndex] = normalizeLine(`${current.options[lastIndex]} ${line}`);
      }
    }
  }

  commitQuestion(current, questions);
  return { questions, answerMap };
}

function parseGridQuestions(lines) {
  const questions = [];
  let header = [];
  let rows = [];

  const flush = () => {
    if (header.length < 2 || rows.length === 0) {
      header = [];
      rows = [];
      return;
    }

    for (let column = 1; column < header.length; column += 1) {
      const id = Number.parseInt(header[column], 10);
      if (!Number.isFinite(id)) {
        continue;
      }

      const entries = [];
      let answer = "";
      for (const row of rows) {
        const label = normalizeLine(row[0] || "");
        const value = normalizeLine(row[column] || "");
        if (!label || !value) {
          continue;
        }
        if (/^ans(?:wer)?$/i.test(label)) {
          answer = value;
        } else {
          entries.push(`${label}: ${value}`);
        }
      }

      if (entries.length > 0) {
        questions.push({
          id,
          question: entries.join("\n"),
          options: [],
          correctAnswer: answer,
        });
      }
    }

    header = [];
    rows = [];
  };

  for (const rawLine of lines) {
    if (!rawLine.includes("\t")) {
      continue;
    }

    const cells = rawLine.split("\t").map(normalizeLine);
    const values = cells.slice(1).filter(Boolean);
    const isHeader = values.length >= 2 && values.every((cell) => /^\d{1,3}$/.test(cell));

    if (isHeader) {
      flush();
      header = cells;
      rows = [];
      continue;
    }

    if (header.length > 0) {
      rows.push(cells);
    }
  }

  flush();
  return questions;
}

function parseTableQuestion(line) {
  const cells = line.split("\t").map(normalizeLine).filter(Boolean);
  if (cells.length < 4 || !/^\d{1,3}$/.test(cells[0])) {
    return null;
  }

  const id = Number.parseInt(cells[0], 10);
  const question = cells[1] || "";
  const remaining = cells.slice(2);
  const options = remaining.slice(0, 4).map((option) => parseOption(option)?.text || option);
  const answer = remaining.length > 4 ? normalizeAnswer(remaining[4], options) : "";

  return {
    id,
    questionParts: [question].filter(Boolean),
    options,
    correctAnswer: answer,
  };
}

function looksLikeOptionOnly(line) {
  return /^[A-D][\).:\-]\s+/i.test(line);
}

function parseOption(line) {
  const match = line.match(/^\(?([A-D])\)?[\).:\-]\s*(.+)$/i);
  if (!match) {
    return null;
  }
  return {
    index: match[1].toUpperCase().charCodeAt(0) - 65,
    text: normalizeLine(match[2]),
  };
}

function parseInlineAnswer(line) {
  const match = line.match(/^(?:correct\s*)?(?:answer|ans)\s*[:\-]\s*(.+)$/i);
  return match ? normalizeLine(match[1]) : "";
}

function parseAnswerLine(line, answerMap) {
  const pairs = [...line.matchAll(/(?:Q?\s*)?(\d{1,3})\s*[\).:\-]?\s*(?:Ans(?:wer)?\s*[:\-]?\s*)?([A-D])\b/gi)];
  for (const pair of pairs) {
    answerMap.set(Number.parseInt(pair[1], 10), pair[2].toUpperCase());
  }
}

function commitQuestion(current, questions) {
  if (!current) {
    return;
  }
  const question = normalizeLine(current.questionParts.join(" "));
  const options = current.options.map((option) => normalizeLine(option || "")).filter(Boolean);
  if (!question && options.length === 0) {
    return;
  }
  questions.push({
    id: current.id,
    question,
    options,
    correctAnswer: current.correctAnswer || "",
  });
}

function validateQuestions(questions, answerMap, errors) {
  const seen = new Set();
  const valid = [];

  for (const question of questions) {
    const questionText = normalizeLine(String(question.question || question.questionParts?.join(" ") || ""));
    let options = question.options.map((option) => normalizeLine(option || "")).filter(Boolean);
    const calculatedAnswer = calculateArithmeticAnswer(questionText);
    let correctAnswer = question.correctAnswer || (calculatedAnswer !== null ? String(calculatedAnswer) : "");

    if (options.length < 2 && correctAnswer !== "") {
      options = buildNumericOptions(Number(correctAnswer));
    }

    const fingerprint = normalizeLine(`${questionText} ${options.join(" ")}`).toLowerCase();
    if (!questionText) {
      errors.push(`Question ${question.id || "unknown"} removed: empty question text.`);
      continue;
    }
    if (options.length < 2) {
      errors.push(`Question ${question.id || "unknown"} removed: fewer than 2 options.`);
      continue;
    }
    if (seen.has(fingerprint)) {
      errors.push(`Question ${question.id || "unknown"} removed: duplicate question.`);
      continue;
    }
    seen.add(fingerprint);

    const mapped = answerMap.get(question.id);
    if (!correctAnswer && mapped) {
      correctAnswer = normalizeAnswer(mapped, options);
    }
    valid.push({
      id: valid.length + 1,
      question: questionText,
      options: options.slice(0, 4),
      correctAnswer: normalizeAnswer(correctAnswer, options),
    });
  }

  return valid;
}

function calculateArithmeticAnswer(questionText) {
  const values = [...questionText.matchAll(/[+\-]?\s*\d+(?:\.\d+)?/g)]
    .map((match) => Number(match[0].replace(/\s+/g, "")))
    .filter((value) => Number.isFinite(value));

  if (values.length === 0) {
    return null;
  }

  const total = values.reduce((sum, value) => sum + value, 0);
  return Number.isInteger(total) ? total : Number(total.toFixed(2));
}

function buildNumericOptions(answer) {
  if (!Number.isFinite(answer)) {
    return [];
  }

  const values = [answer, answer + 1, answer - 1, answer + 2];
  const unique = [];
  for (const value of values) {
    const text = String(value);
    if (!unique.includes(text)) {
      unique.push(text);
    }
  }

  let offset = 3;
  while (unique.length < 4) {
    const text = String(answer + offset);
    if (!unique.includes(text)) {
      unique.push(text);
    }
    offset += 1;
  }

  return unique;
}

function normalizeAnswer(answer, options) {
  const value = normalizeLine(String(answer || ""));
  if (!value) {
    return "";
  }

  const optionLetter = value.match(/(?:option\s*)?([A-D])\b/i)?.[1]?.toUpperCase();
  if (optionLetter) {
    const option = options[optionLetter.charCodeAt(0) - 65];
    return option || `Option ${optionLetter}`;
  }

  return value;
}

function writeJson(filePath, data) {
  fs.writeFileSync(filePath, `${JSON.stringify(data, null, 2)}\n`, "utf8");
}

function logSet(category, setName, questionsImported, errors) {
  console.log(`Category Name: ${category}`);
  console.log(`Set Name: ${setName}`);
  console.log(`Questions Imported: ${questionsImported}`);
  console.log(`Errors Found: ${errors.length}`);
  if (errors.length > 0) {
    for (const error of errors.slice(0, 5)) {
      console.log(`  - ${error}`);
    }
    if (errors.length > 5) {
      console.log(`  - ${errors.length - 5} more...`);
    }
  }
  console.log("");
}

function printSummary(summary) {
  console.log("Conversion Summary");
  console.log(`Total categories: ${summary.totalCategories}`);
  console.log(`Total sets: ${summary.totalSets}`);
  console.log(`Total questions imported: ${summary.totalQuestionsImported}`);
  console.log(`Missing answers: ${summary.missingAnswers}`);
  console.log(`Conversion errors: ${summary.conversionErrors.length}`);
  console.log(`Summary report: ${path.relative(repoRoot, reportPath)}`);
}
