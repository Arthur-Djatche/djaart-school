import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'
import tailwindcss from '@tailwindcss/vite'

// https://vite.dev/config/
export default defineConfig({
  plugins: [react(), tailwindcss()],
  server: {
    port: 5173,
    // host: true lie sur 0.0.0.0 (pas seulement localhost) pour permettre
    // l'acces depuis d'autres appareils sur le meme reseau local.
    host: true,
  },
})
