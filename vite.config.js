import { resolve } from "path";
import { defineConfig } from "vite";
import vue from "@vitejs/plugin-vue";
import i18nExtractKeys from "./i18nExtractKeys.vite.js";

// https://vitejs.dev/config/
export default defineConfig({
  target: "es2016",
  plugins: [i18nExtractKeys(), vue()],
  build: {
    lib: {
      entry: resolve(__dirname, "resources/js/main.js"),
      name: "AuthorsHistoryPlugin",
      fileName: "build",
      formats: ["iife"],
    },
    outDir: resolve(__dirname, "public/build"),
    rollupOptions: {
      external: ["vue"],
      output: {
        globals: {
          vue: "pkp.modules.vue",
        },
      },
    },
  },
});
