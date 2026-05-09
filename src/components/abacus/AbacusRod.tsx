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

const upperPositions = {
  inactive: 8,
  active: 34,
};

const lowerPositions = {
  activeBase: 74,
  inactiveBase: 120,
  gap: 32,
};

const spring = {
  type: "spring",
  stiffness: 420,
  damping: 24,
  mass: 0.55,
} as const;

const beadClass =
  "absolute left-1/2 z-20 h-7 w-11 touch-none bg-gradient-to-br from-orange-300 via-[#ff5b35] to-[#e6361e] shadow-[inset_3px_3px_5px_rgba(255,255,255,0.32),inset_-4px_-4px_7px_rgba(120,22,12,0.28),0_4px_8px_rgba(127,29,29,0.18)] outline-none [clip-path:polygon(50%_0%,100%_50%,50%_100%,0%_50%)] focus-visible:ring-2 focus-visible:ring-orange-300 focus-visible:ring-offset-2 focus-visible:ring-offset-zinc-100";

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
    <div className="relative h-[250px] min-w-[60px] flex-1" role="group" aria-label={`Rod ${index + 1}, ${placeLabel}`}>
      <div className="absolute left-1/2 top-2 z-0 h-[240px] w-1 -translate-x-1/2 bg-gradient-to-b from-zinc-500 via-zinc-800 to-zinc-500 shadow-[0_0_0_1px_rgba(255,255,255,0.18)]" />
      {showDividerDot && (
        <span
          className={`absolute left-1/2 top-16 z-30 h-2 w-2 -translate-x-1/2 rounded-full ${
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
        dragConstraints={{ top: upperPositions.inactive, bottom: upperPositions.active }}
        onClick={() => onToggleUpper(index)}
        onDragEnd={(_, info) => onUpperDrag(index, info.offset.y > 18)}
        animate={{ top: rod.upperActive ? upperPositions.active : upperPositions.inactive, x: "-50%", scale: rod.upperActive ? 1.04 : 1 }}
        transition={spring}
        whileTap={{ scale: 0.94 }}
        className={beadClass}
        aria-pressed={rod.upperActive}
        aria-label={`Toggle upper bead on rod ${index + 1}`}
      />

      {Array.from({ length: 4 }).map((_, beadIndex) => {
        const isActive = beadIndex < rod.lowerActive;
        const top = isActive
          ? lowerPositions.activeBase + beadIndex * lowerPositions.gap
          : lowerPositions.inactiveBase + beadIndex * lowerPositions.gap;

        return (
          <motion.button
            key={beadIndex}
            type="button"
            drag="y"
            dragElastic={0.12}
            dragConstraints={{ top: lowerPositions.activeBase, bottom: lowerPositions.inactiveBase + 3 * lowerPositions.gap }}
            onClick={() => onSetLower(index, beadIndex)}
            onDragEnd={(_, info) => onLowerDrag(index, beadIndex, info.offset.y < -18)}
            animate={{ top, x: "-50%", scale: isActive ? 1.04 : 1 }}
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
