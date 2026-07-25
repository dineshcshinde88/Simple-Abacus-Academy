import { describe, expect, it } from "vitest";
import { calculateCartTotal, toggleCartPlan } from "./shopCart";
const plans = [{ id: "a", price: 99 }, { id: "b", price: 99 }, { id: "v", price: 149 }];
describe("worksheet subscription cart", () => {
  it("supports a single plan and mixed multi-plan totals", () => {
    expect(calculateCartTotal(plans, ["a"])).toBe(99);
    expect(calculateCartTotal(plans, ["a", "b", "v"])).toBe(347);
  });
  it("prevents duplicates and removes an item with immediate recalculation", () => {
    const selected = toggleCartPlan(toggleCartPlan([], "a"), "b");
    expect(new Set(selected).size).toBe(selected.length);
    const removed = toggleCartPlan(selected, "a");
    expect(removed).toEqual(["b"]);
    expect(calculateCartTotal(plans, removed)).toBe(99);
  });
});