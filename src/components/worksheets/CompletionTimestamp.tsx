import { formatStoredCompletion } from "@/lib/completionTimestamp";

type CompletionTimestampProps = {
  completedAt?: string | null;
  layout?: "summary" | "inline";
};

export function CompletionTimestamp({ completedAt, layout = "inline" }: CompletionTimestampProps) {
  const formatted = formatStoredCompletion(completedAt);
  if (layout === "summary") {
    return (
      <>
        <div className="rounded-xl bg-slate-50 p-4">
          <p className="text-xs uppercase text-slate-500">Completed Date</p>
          <p className="mt-1 text-lg font-bold text-slate-900">{formatted.date}</p>
        </div>
        <div className="rounded-xl bg-slate-50 p-4">
          <p className="text-xs uppercase text-slate-500">India Time</p>
          <p className="mt-1 text-lg font-bold text-slate-900">{formatted.time}</p>
        </div>
      </>
    );
  }
  return <time dateTime={completedAt || undefined}>{formatted.completedAt}</time>;
}