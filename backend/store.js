const fs = require("fs/promises");
const path = require("path");

const DATA_FILE = path.join(__dirname, "data", "users.json");

async function ensureDataFile() {
  try {
    await fs.access(DATA_FILE);
  } catch {
    await fs.mkdir(path.dirname(DATA_FILE), { recursive: true });
    await fs.writeFile(DATA_FILE, "[]", "utf8");
  }
}

async function readUsers() {
  await ensureDataFile();
  const raw = await fs.readFile(DATA_FILE, "utf8");
  const data = JSON.parse(raw || "[]");
  return Array.isArray(data) ? data : [];
}

async function writeUsers(users) {
  await ensureDataFile();
  await fs.writeFile(DATA_FILE, JSON.stringify(users, null, 2), "utf8");
}

module.exports = {
  readUsers,
  writeUsers,
};
