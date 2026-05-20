import baseConfig from "./vite.config.mjs";

export default async function config(env) {
  const resolved = typeof baseConfig === "function" ? await baseConfig(env) : baseConfig;

  return {
    ...resolved,
    cacheDir: "C:/tmp/abacus-vite-cache",
  };
}
