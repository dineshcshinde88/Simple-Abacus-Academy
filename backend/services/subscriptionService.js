const prisma = require("../config/prisma");

const createPlan = async ({ name, durationDays, price }) => {
  return prisma.subscriptionPlan.create({ data: { name, durationDays, price } });
};

const getPlanByName = async (name) => prisma.subscriptionPlan.findUnique({ where: { name } });

module.exports = { createPlan, getPlanByName };
