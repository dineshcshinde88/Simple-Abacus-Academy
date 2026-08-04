import { useState } from "react";
import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from "@/components/ui/accordion";
import { SyllabusLevel } from "@/data/courseSyllabus";

const LevelSyllabus = ({ levels }: { levels: SyllabusLevel[] }) => {
  const [openLevel, setOpenLevel] = useState("");

  return (
  <Accordion type="single" collapsible value={openLevel} onValueChange={setOpenLevel} className="w-full space-y-3">
    {levels.map((item, index) => (
      <AccordionItem
        key={item.level}
        value={`syllabus-${index}`}
        onMouseEnter={() => setOpenLevel(`syllabus-${index}`)}
        className="group rounded-2xl border border-slate-200 bg-white px-5 shadow-sm transition hover:border-purple-300 hover:shadow-md"
      >
        <AccordionTrigger className="py-5 text-left hover:no-underline">
          <span>
            <span className="block text-lg font-heading font-bold text-[#4c1d95]">
              {item.level}{item.title ? ` (${item.title})` : ""}
            </span>
            <span className="mt-1 block text-xs font-normal text-slate-500">
              {item.topics.length} syllabus {item.topics.length === 1 ? "topic" : "topics"} · Hover or click to view
            </span>
          </span>
        </AccordionTrigger>
        <AccordionContent className="pb-5">
          <ul className="grid gap-2 md:grid-cols-2">
            {item.topics.map((topic) => (
              <li key={topic} className="flex items-start gap-2 rounded-lg bg-slate-50 px-3 py-2 text-sm leading-relaxed text-slate-700">
                <span className="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-orange-500" />
                <span>{topic}</span>
              </li>
            ))}
          </ul>
        </AccordionContent>
      </AccordionItem>
    ))}
  </Accordion>
  );
};

export default LevelSyllabus;
