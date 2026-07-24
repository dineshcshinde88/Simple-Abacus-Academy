export type FormattedCompletionTimestamp = {
  date: string;
  time: string;
  completedAt: string;
};

export function formatStoredCompletion(value?: string | null): FormattedCompletionTimestamp {
  if (!value) return { date: "-", time: "-", completedAt: "-" };

  const hasTimezone = /(?:Z|[+-]\d{2}:?\d{2})$/i.test(value);
  const normalizedValue = hasTimezone
    ? value
    : `${value.includes("T") ? value : value.replace(" ", "T")}Z`;
  const timestamp = new Date(normalizedValue);
  if (Number.isNaN(timestamp.getTime())) return { date: "-", time: "-", completedAt: "-" };

  const date = new Intl.DateTimeFormat("en-GB", {
    timeZone: "Asia/Kolkata",
    day: "2-digit",
    month: "2-digit",
    year: "numeric",
  }).format(timestamp);
  const time = new Intl.DateTimeFormat("en-US", {
    timeZone: "Asia/Kolkata",
    hour: "2-digit",
    minute: "2-digit",
    second: "2-digit",
    hour12: true,
  }).format(timestamp);
  return { date, time, completedAt: `${date}, ${time}` };
}