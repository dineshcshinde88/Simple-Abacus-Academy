export type FormattedCompletionTimestamp = {
  date: string;
  time: string;
  completedAt: string;
};

export function formatStoredCompletion(value?: string | null): FormattedCompletionTimestamp {
  const match = value?.match(/^(\d{4})-(\d{2})-(\d{2})[T ](\d{2}):(\d{2}):(\d{2})/);
  if (!match) return { date: "-", time: "-", completedAt: "-" };
  const [, year, month, day, hourText, minute, second] = match;
  const hour = Number(hourText);
  const meridiem = hour >= 12 ? "PM" : "AM";
  const displayHour = String(hour % 12 || 12).padStart(2, "0");
  const date = `${day}/${month}/${year}`;
  const time = `${displayHour}:${minute}:${second} ${meridiem}`;
  return { date, time, completedAt: `${date}, ${time}` };
}
