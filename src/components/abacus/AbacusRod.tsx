import { motion } from "framer-motion";
import type { RodState } from "@/lib/abacus";

type AbacusRodProps = {
  index: number;
  rod: RodState;
  totalRods: number;
  onToggleUpper: (index: number) => void;
  onSetLower: (index: number, beadIndex: number) => void;
  onUpperDrag: (index: number, active: boolean) => void;
  onLowerDrag: (index: number, beadIndex: number, active: boolean) => void;
};

const spring = {
  type: "spring",
  stiffness: 420,
  damping: 24,
  mass: 0.55,
} as const;

const beadClass =
  "absolute left-1/2 z-20 h-[var(--bead-height)] w-[calc(100%+1px)] touch-none bg-gradient-to-br from-orange-300 via-[#ff5b35] to-[#e6361e] shadow-[inset_3px_3px_5px_rgba(255,255,255,0.32),inset_-4px_-4px_7px_rgba(120,22,12,0.28),0_3px_6px_rgba(127,29,29,0.16)] outline-none [clip-path:polygon(50%_0%,100%_50%,50%_100%,0%_50%)] focus-visible:ring-2 focus-visible:ring-orange-300 focus-visible:ring-offset-1 focus-visible:ring-offset-zinc-100";

const getPlaceLabel = (index: number, totalRods: number) => {
  const unitRodIndex = Math.floor(totalRods / 2);
  const place = unitRodIndex - index;

  if (place === 0) return "ones";
  if (place > 0) return `10 to the power ${place}`;
  return `10 to the power ${place}`;
};

const AbacusRod = ({
  index,
  rod,
  totalRods,
  onToggleUpper,
  onSetLower,
  onUpperDrag,
  onLowerDrag,
}: AbacusRodProps) => {
  const placeLabel = getPlaceLabel(index, totalRods);
  const showDividerDot = index % 3 === 1;
  const isCenterDot = index === Math.floor(totalRods / 2);

  return (
    <div
      className="relative h-[clamp(6.75rem,28vw,8rem)] min-w-0 flex-1 [--bar-height:0.5rem] [--bead-height:clamp(0.6rem,3.4vw,1rem)] [--bead-rest-gap:clamp(0.6rem,4vw,1.25rem)] sm:h-[clamp(9rem,32vw,15.625rem)] sm:[--bar-height:0.75rem] sm:[--bead-height:clamp(0.9rem,3.2vw,1.5rem)] lg:[--bead-height:1.75rem]"
      role="group"
      aria-label={`Rod ${index + 1}, ${placeLabel}`}
    >
      <div className="absolute inset-y-0 left-1/2 z-0 h-full w-[3px] -translate-x-1/2 bg-black shadow-[0_0_0_1px_rgba(255,255,255,0.18)] md:w-1" />
      {showDividerDot && (
        <span
          className={`absolute left-1/2 top-[calc(28%+var(--bar-height)/2)] z-30 h-1.5 w-1.5 -translate-x-1/2 -translate-y-1/2 rounded-full md:h-2 md:w-2 ${
            isCenterDot
              ? "bg-[#ffd400] shadow-[0_0_0_1px_rgba(94,78,0,0.7),0_0_8px_rgba(255,212,0,0.85)]"
              : "bg-white shadow-[0_0_0_1px_rgba(0,0,0,0.35)]"
          }`}
          aria-hidden="true"
        />
      )}

      <motion.button
        type="button"
        drag="y"
        dragElastic={0.12}
        dragConstraints={{ top: 0, bottom: 70 }}
        onTap={() => onToggleUpper(index)}
        onDragEnd={(_, info) => onUpperDrag(index, info.offset.y > 18)}
        animate={{ top: rod.upperActive ? "calc(28% - var(--bead-height))" : "calc(28% - var(--bead-rest-gap) - var(--bead-height))", x: "-50%", scale: 1 }}
        transition={spring}
        whileTap={{ scale: 0.94 }}
        className={beadClass}
        aria-pressed={rod.upperActive}
        aria-label={`Toggle upper bead on rod ${index + 1}`}
      />

      {Array.from({ length: 4 }).map((_, beadIndex) => {
        const isActive = beadIndex < rod.lowerActive;
        const top = isActive
          ? `calc(28% + var(--bar-height) + ${beadIndex} * var(--bead-height))`
          : `calc(28% + var(--bar-height) + var(--bead-rest-gap) + ${beadIndex} * var(--bead-height))`;

        return (
          <motion.button
            key={beadIndex}
            type="button"
            drag="y"
            dragElastic={0.04}
            dragConstraints={{ top: -45, bottom: 45 }}
            onTap={() => onSetLower(index, beadIndex)}
            onDragEnd={(_, info) => onLowerDrag(index, beadIndex, info.offset.y < -18)}
            animate={{ top, x: "-50%", scale: 1 }}
            transition={spring}
            whileTap={{ scale: 0.94 }}
            className={beadClass}
            aria-pressed={isActive}
            aria-label={`${isActive ? "Deactivate" : "Activate"} lower bead ${beadIndex + 1} on rod ${index + 1}`}
          />
        );
      })}
    </div>
  );
};

export default AbacusRod;
