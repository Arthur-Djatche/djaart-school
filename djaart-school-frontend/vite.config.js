import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'
import tailwindcss from '@tailwindcss/vite'
import { VitePWA } from 'vite-plugin-pwa'

// https://vite.dev/config/
export default defineConfig({
  plugins: [
    react(),
    tailwindcss(),
    VitePWA({
      registerType: 'autoUpdate',
      includeAssets: ['favicon.ico', 'apple-touch-icon-180x180.png'],
      manifest: {
        name: 'DJAART SCHOOL',
        short_name: 'DJAART SCHOOL',
        description: 'Gestion scolaire tout-en-un pour établissements francophones.',
        theme_color: '#001335',
        background_color: '#001335',
        display: 'standalone',
        start_url: '/dashboard',
        scope: '/',
        icons: [
          { src: 'pwa-64x64.png', sizes: '64x64', type: 'image/png' },
          { src: 'pwa-192x192.png', sizes: '192x192', type: 'image/png' },
          { src: 'pwa-512x512.png', sizes: '512x512', type: 'image/png' },
          { src: 'maskable-icon-512x512.png', sizes: '512x512', type: 'image/png', purpose: 'maskable' },
        ],
      },
      workbox: {
        // Coquille de l'app (JS/CSS/HTML) precachee automatiquement par
        // Workbox — c'est elle qui rend l'ouverture hors-ligne possible.
        navigateFallback: '/index.html',
        // Ne jamais mettre en cache l'authentification : un jeton CSRF/une
        // session perimee servie depuis le cache casserait la connexion.
        navigateFallbackDenylist: [/^\/sanctum/, /^\/api\/login/, /^\/api\/logout/],
        runtimeCaching: [
          {
            // Lectures API (GET uniquement, cf. methode par defaut de Workbox) :
            // NetworkFirst, la donnee la plus fraiche prevaut des que le
            // reseau repond, avec repli sur la derniere version connue hors
            // ligne. Les mutations (POST/PUT/DELETE) ne passent jamais par
            // une regle ici — elles restent non interceptees, donc echouent
            // normalement hors-ligne plutot que d'etre mises en file (pas de
            // synchronisation differee pour des donnees financieres).
            urlPattern: ({ url, request }) =>
              request.method === 'GET' &&
              url.pathname.startsWith('/api/') &&
              !url.pathname.startsWith('/api/login') &&
              !url.pathname.startsWith('/api/logout'),
            handler: 'NetworkFirst',
            options: {
              cacheName: 'api-lecture',
              networkTimeoutSeconds: 8,
              expiration: { maxEntries: 200, maxAgeSeconds: 60 * 60 * 24 },
              cacheableResponse: { statuses: [0, 200] },
            },
          },
        ],
      },
    }),
  ],
  server: {
    port: 5173,
    // host: true lie sur 0.0.0.0 (pas seulement localhost) pour permettre
    // l'acces depuis d'autres appareils sur le meme reseau local.
    host: true,
  },
})
