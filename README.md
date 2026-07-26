# DJAART SCHOOL

Plateforme multi-établissements de gestion scolaire (Primaire/Secondaire, Universitaire LMD, Centre de formation DQP/CQP).

> Cahier des charges : [DJAART_SCHOOL_CLAUDE_CODE_BUILD_PLAN.md](DJAART_SCHOOL_CLAUDE_CODE_BUILD_PLAN.md)
> Analyse & conception UML : [DJAART_SCHOOL_Analyse_Conception_UML.md](DJAART_SCHOOL_Analyse_Conception_UML.md)

## Stack

- Backend : Laravel 11 (PHP 8.2+), Sanctum, Spatie Laravel-Permission, MySQL
- Frontend : React (Vite) + React Router + Tailwind CSS
- PDF : barryvdh/laravel-dompdf ou spatie/laravel-pdf

## Structure du dépôt

```
djaart-school-backend/    API Laravel
djaart-school-frontend/   SPA React
```

## Identité visuelle

Palette extraite du logo officiel (`LOGO_DS.png`) :

| Rôle | Couleur | Hex |
|---|---|---|
| Marine (texte, headers) | ![#001335](https://via.placeholder.com/12/001335/001335.png) | `#001335` |
| Bleu primaire (dégradé foncé) | ![#003FA2](https://via.placeholder.com/12/003FA2/003FA2.png) | `#003FA2` |
| Bleu primaire (dégradé clair) | ![#009BEC](https://via.placeholder.com/12/009BEC/009BEC.png) | `#009BEC` |
| Orange (accent / CTA) | ![#FE9605](https://via.placeholder.com/12/FE9605/FE9605.png) | `#FE9605` |
| Teal (accent secondaire) | ![#009CA0](https://via.placeholder.com/12/009CA0/009CA0.png) | `#009CA0` |

Ces couleurs sont déclarées dans `tailwind.config.js` du frontend (`brand.navy`, `brand.blue`, `brand.blueLight`, `brand.orange`, `brand.teal`).

## Développement

Voir `PROGRESS.md` pour l'état d'avancement par phase.
