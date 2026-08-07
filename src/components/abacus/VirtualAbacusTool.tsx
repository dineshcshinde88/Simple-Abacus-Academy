import { useCallback, useMemo, useRef, useState } from "react";
import { AnimatePresence, motion } from "framer-motion";
import { Eye, EyeOff, RefreshCw } from "lucide-react";
import AbacusRod from "@/components/abacus/AbacusRod";
import { Button } from "@/components/ui/button";
import {
  ROD_COUNT,
  calculateAbacusValue,
  createEmptyRods,
  formatAbacusValue,
  type RodState,
} from "@/lib/abacus";

const clamp = (value: number, min: number, max: number) => Math.min(Math.max(value, min), max);

const VirtualAbacusTool = () => {
  const [rods, setRods] = useState<RodState[]>(() => createEmptyRods());
  const [showCount, setShowCount] = useState(true);
  const [soundEnabled, setSoundEnabled] = useState(true);
  const [focusedRod, setFocusedRod] = useState(ROD_COUNT - 1);
  const [audioContext, setAudioContext] = useState<AudioContext | null>(null);
  const toolRef = useRef<HTMLDivElement | null>(null);

  const currentValue = useMemo(() => calculateAbacusValue(rods), [rods]);

  const playBeadSound = useCallback(() => {
    if (!soundEnabled) return;

    try {
      const ctx = audioContext ?? new AudioContext();
      if (!audioContext) setAudioContext(ctx);

      const oscillator = ctx.createOscillator();
      const gain = ctx.createGain();

      // A short percussive click gives bead movement feedback without being harsh.
      oscillator.type = "triangle";
      oscillator.frequency.setValueAtTime(520, ctx.currentTime);
      oscillator.frequency.exponentialRampToValueAtTime(180, ctx.currentTime + 0.08);
      gain.gain.setValueAtTime(0.0001, ctx.currentTime);
      gain.gain.exponentialRampToValueAtTime(0.18, ctx.currentTime + 0.01);
      gain.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + 0.12);

      oscillator.connect(gain);
      gain.connect(ctx.destination);
      oscillator.start();
      oscillator.stop(ctx.currentTime + 0.13);
    } catch {
      // Browsers can block audio until a user gesture; the tool still works silently.
    }
  }, [audioContext, soundEnabled]);

  const updateRod = useCallback(
    (index: number, updater: (rod: RodState) => RodState) => {
      setRods((previous) =>
        previous.map((rod, rodIndex) => (rodIndex === index ? updater(rod) : rod)),
      );
      setFocusedRod(index);
      playBeadSound();
    },
    [playBeadSound],
  );

  const toggleUpper = (index: number) => {
    updateRod(index, (rod) => ({ ...rod, upperActive: !rod.upperActive }));
  };

  const setUpperFromDrag = (index: number, active: boolean) => {
    updateRod(index, (rod) => ({ ...rod, upperActive: active }));
  };

  const setLower = (index: number, beadIndex: number) => {
    updateRod(index, (rod) => {
      const selectedCount = beadIndex + 1;
      const lowerActive = selectedCount === rod.lowerActive ? beadIndex : selectedCount;
      return { ...rod, lowerActive: clamp(lowerActive, 0, 4) };
    });
  };

  const setLowerFromDrag = (index: number, beadIndex: number, active: boolean) => {
    updateRod(index, (rod) => {
      const lowerActive = active
        ? Math.max(rod.lowerActive, beadIndex + 1)
        : Math.min(rod.lowerActive, beadIndex);
      return { ...rod, lowerActive: clamp(lowerActive, 0, 4) };
    });
  };

  const handleKeyboardControl = (event: React.KeyboardEvent<HTMLDivElement>) => {
    if (event.key === "ArrowLeft") {
      event.preventDefault();
      setFocusedRod((value) => clamp(value - 1, 0, ROD_COUNT - 1));
    }

    if (event.key === "ArrowRight") {
      event.preventDefault();
      setFocusedRod((value) => clamp(value + 1, 0, ROD_COUNT - 1));
    }

    if (event.key === "ArrowUp") {
      event.preventDefault();
      updateRod(focusedRod, (rod) => ({ ...rod, lowerActive: clamp(rod.lowerActive + 1, 0, 4) }));
    }

    if (event.key === "ArrowDown") {
      event.preventDefault();
      updateRod(focusedRod, (rod) => ({ ...rod, lowerActive: clamp(rod.lowerActive - 1, 0, 4) }));
    }

    if (event.key === " " || event.key === "Enter") {
      event.preventDefault();
      toggleUpper(focusedRod);
    }
  };

  const refreshAbacus = () => {
    setRods(createEmptyRods());
    setShowCount(true);
    setFocusedRod(ROD_COUNT - 1);
    playBeadSound();
  };

  return (
    <section className="bg-[#f3f3f3] py-6 sm:py-14">
      <div ref={toolRef} className="mx-auto max-w-[1160px] px-3 text-zinc-950 sm:px-4">
        <motion.div
          initial={{ opacity: 0, y: 18 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ duration: 0.45, ease: "easeOut" }}
          className="rounded-2xl border border-zinc-200 bg-white px-2 py-7 shadow-[0_22px_60px_rgba(15,23,42,0.10)] sm:rounded-[28px] sm:px-8 sm:py-12"
        >
          <h1 className="sr-only">Virtual Abacus Tool</h1>

          <div
            tabIndex={0}
            onKeyDown={handleKeyboardControl}
            className="overflow-hidden pb-2 outline-none focus-visible:ring-2 focus-visible:ring-orange-500 md:overflow-x-auto"
            aria-label="Virtual abacus. Use left and right arrows to choose a rod, up and down arrows for lower beads, and space for upper bead."
          >
            <motion.div
              className="mx-auto w-full max-w-[1040px] origin-top overflow-hidden rounded-[8px] border-[7px] border-black bg-[#111] shadow-[inset_0_2px_0_rgba(255,255,255,0.14),inset_0_-8px_18px_rgba(0,0,0,0.72),0_18px_34px_rgba(15,23,42,0.16)] sm:rounded-[12px] sm:border-[10px] md:min-w-[980px]"
            >
              <div className="relative bg-gradient-to-b from-[#efefef] via-[#f8f8f8] to-[#e5e5e5] shadow-[inset_0_0_18px_rgba(0,0,0,0.24)]">
                <div className="absolute inset-x-0 top-[28%] z-10 h-2 bg-black shadow-[0_2px_4px_rgba(0,0,0,0.28)] sm:h-3" />
                <div className="grid gap-0" style={{ gridTemplateColumns: `repeat(${ROD_COUNT}, minmax(0, 1fr))` }}>
                  {rods.map((rod, index) => (
                    <AbacusRod
                      key={index}
                      index={index}
                      rod={rod}
                      totalRods={ROD_COUNT}
                      onToggleUpper={toggleUpper}
                      onSetLower={setLower}
                      onUpperDrag={setUpperFromDrag}
                      onLowerDrag={setLowerFromDrag}
                    />
                  ))}
                </div>
              </div>
            </motion.div>
          </div>

          <div className="mx-auto mt-5 flex max-w-[1040px] flex-col items-stretch justify-between gap-4 rounded-2xl border border-[#dce5ef] bg-[#f8fbff] px-4 py-5 shadow-[0_12px_28px_rgba(15,23,42,0.06)] sm:mt-10 sm:flex-row sm:items-center sm:px-8">
            <div className="flex min-h-10 flex-wrap items-center justify-center gap-3 sm:justify-start sm:gap-4">
              <span className="text-xs font-black uppercase tracking-[0.16em] text-[#58708f] sm:text-sm">
                Current Value:
              </span>
              <AnimatePresence mode="wait">
                {showCount ? (
                  <motion.span
                    key={currentValue}
                    initial={{ opacity: 0, y: -6 }}
                    animate={{ opacity: 1, y: 0 }}
                    exit={{ opacity: 0, y: 6 }}
                    className="font-heading text-3xl font-black leading-none text-[#1267f1] sm:text-4xl"
                  >
                    {formatAbacusValue(currentValue)}
                  </motion.span>
                ) : (
                  <motion.span
                    key="hidden"
                    initial={{ opacity: 0 }}
                    animate={{ opacity: 1 }}
                    exit={{ opacity: 0 }}
                    className="text-xl font-black tracking-[0.2em] text-[#1267f1] sm:text-2xl"
                  >
                    ---
                  </motion.span>
                )}
              </AnimatePresence>
            </div>

            <div className="grid grid-cols-2 gap-3 sm:flex sm:flex-wrap sm:items-center sm:justify-center sm:gap-4">
              <Button
                type="button"
                variant="outline"
                onClick={() => setShowCount((value) => !value)}
                className="h-11 rounded-full border-[#ff3150] bg-white px-4 font-heading font-bold text-[#ff3150] hover:bg-[#fff1f3] hover:text-[#ff3150] sm:px-6"
              >
                {showCount ? (
                  <EyeOff className="mr-2 h-4 w-4" />
                ) : (
                  <Eye className="mr-2 h-4 w-4" />
                )}
                {showCount ? "Hide Count" : "Show Count"}
              </Button>
              <Button
                type="button"
                onClick={refreshAbacus}
                className="h-11 rounded-full bg-[#181e25] px-4 font-heading font-bold text-white hover:bg-black sm:px-7"
              >
                <RefreshCw className="mr-2 h-4 w-4" />
                Zoom
              </Button>
              <button
                type="button"
                onClick={() => setSoundEnabled((value) => !value)}
                className="sr-only"
                aria-label={soundEnabled ? "Turn bead sound off" : "Turn bead sound on"}
              />
            </div>
          </div>
        </motion.div>
      </div>
    </section>
  );
};

export default VirtualAbacusTool;
