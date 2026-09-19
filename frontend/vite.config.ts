import { fileURLToPath, URL } from 'node:url'

import vue from '@vitejs/plugin-vue'
import { defineConfig } from 'vite'

export default defineConfig({
  base: './',
  plugins: [vue()],
  server: { proxy: { '/api': { target: 'http://localhost', rewrite: path => `/skilltree/public${path}` } } },
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url)),
    },
  },
  build: {
    emptyOutDir: false,
    outDir: '../public',
  },
})
