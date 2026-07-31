import { defineConfig, minimal2023Preset } from '@vite-pwa/assets-generator/config'

// Genere les icones PWA (favicon, apple-touch-icon, 192/512, maskable) a
// partir du logo existant (non carre : icone+texte empiles) — fond bleu
// marine de la charte plutot que le blanc par defaut, pour eviter un carre
// blanc disgracieux autour du logo sur fond sombre (Android/iOS).
// npm run generate-pwa-assets pour regenerer apres un changement de logo —
// le CLI ecrit a cote de src/assets/logo.png, deplacer ensuite les fichiers
// generes (pwa-*.png, maskable-icon-*.png, apple-touch-icon-*.png,
// favicon.ico) vers public/ (pas d'option de dossier de sortie dans l'outil).
export default defineConfig({
  preset: {
    ...minimal2023Preset,
    transparent: {
      ...minimal2023Preset.transparent,
      resizeOptions: { background: '#001335', fit: 'contain' },
    },
    maskable: {
      ...minimal2023Preset.maskable,
      resizeOptions: { background: '#001335', fit: 'contain' },
    },
    apple: {
      ...minimal2023Preset.apple,
      resizeOptions: { background: '#001335', fit: 'contain' },
    },
  },
  images: ['src/assets/logo.png'],
})
