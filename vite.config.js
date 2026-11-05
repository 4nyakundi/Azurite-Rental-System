import { defineConfig } from 'vite';
import { fileURLToPath } from 'node:url';
export default defineConfig({
  root: fileURLToPath(new URL('./firebase-app', import.meta.url)),
  build: { outDir: '../dist', emptyOutDir: true },
  server: { port: 5173, strictPort: true },
});
