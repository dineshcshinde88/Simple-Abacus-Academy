export const toggleCartPlan = (selectedIds: string[], planId: string): string[] =>
  selectedIds.includes(planId) ? selectedIds.filter((id) => id !== planId) : [...selectedIds, planId];

export const calculateCartTotal = <T extends { id: string; price: number }>(plans: T[], selectedIds: string[]): number =>
  selectedIds.reduce((sum, id) => sum + Number(plans.find((plan) => plan.id === id)?.price || 0), 0);