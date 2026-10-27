# IMMO Digital — site web

Next.js 14 (App Router) · TypeScript strict · TailwindCSS · Framer Motion · canvas 2D/3D maison (symbole IMMO).

Le contenu, les formulaires et l'espace client sont gérés par le back-office Laravel (`../immo-back`) : ce projet n'a ni base de données ni administration propres.

## Lancer en local
```bash
npm install
cp .env.example .env.local   # puis renseigner les variables utiles
npm run dev                  # http://localhost:3000 (Laravel doit tourner : php artisan serve dans ../immo-back)
npm run build && npm start   # test de production
```

## Langues (FR / EN)
- La langue suit le navigateur (en-tête Accept-Language) : français si préféré, anglais pour toute autre langue.
- Le sélecteur FR | EN de l'en-tête mémorise un choix manuel (cookie `immo-locale`) prioritaire sur le navigateur.
- Textes d'interface et pages fixes : `src/lib/dictionaries/` (fr.ts, pages-fr.ts, en.ts, pages-en.ts). Pour modifier un texte, éditer les deux langues.
- Contenus éditoriaux (accueil, pages, articles, avis) : saisis en FR et EN dans l'admin Laravel.

## CMS (Laravel, `../immo-back`)
- Accueil et pages : composés dans l'admin Laravel (**Pages & Sections**) : choix des sections, ordre, visibilité, textes FR/EN. `home` = `/`, toute autre page publiée = `/<slug>` (`src/app/[slug]/page.tsx` ; les routes fixes du site restent prioritaires).
- Le front lit l'API (`src/server/cms-api.ts`) et affiche les sections via le registre `src/components/blocks/Blocks.tsx`.
- Un champ vide dans le CMS reprend la langue par défaut, puis le texte du dictionnaire. Accueil avec CMS injoignable : textes des dictionnaires.
- Articles Tech Insights : admin Laravel (**Blog & Articles**). Corps en texte simple (`## ` intertitre, lignes `- ` liste, paragraphes séparés par une ligne vide), lu par `src/lib/articles.ts`. Un article sans traduction complète s'affiche entièrement en français.
- Avis clients (`/recommandations`) : admin Laravel (**Avis & Témoignages**).
- Cache par tags (`page:<slug>`, `pages`, `posts`, `testimonials`), vidés par Laravel via `POST /api/revalidate` (en-tête `x-revalidate-secret` = `REVALIDATE_SECRET`), expiration de secours 5 min.
- Articles, avis ou pages avec Laravel injoignable : page d'erreur du site (« Réessayer »).
- Nouveau type de section : composant React + `case` dans `Blocks.tsx` + type dans `src/lib/blocks.ts` + schéma dans `immo-back/config/cms.php`.

## À fournir avant la mise en ligne
- `public/fonts/` : fichiers Gilroy `.woff2` (voir LISEZMOI.txt). Ils sont pris en compte automatiquement au démarrage suivant ; en attendant, Outfit s'affiche sans aucune requête en erreur.
- `src/lib/site.ts` : email de contact (téléphones lus sur la charte, à confirmer).
- `src/content/legal.ts` : forme juridique, capital, RCCM, NIU, directeur de la publication, hébergeur, durées de conservation. Chaque valeur manquante s'affiche « À compléter » sur `/mentions-legales` et `/confidentialite`.
- Avis clients : à saisir dans l'admin Laravel, uniquement avec l'accord écrit du client (règle affichée sur `/recommandations`).
- Chiffres publics : n'afficher que des résultats prouvables (client, période, méthode de mesure).
- Articles : relire les 3 articles de départ dans l'admin Laravel.
- Icônes PWA (`public/icons/`) générées depuis `symbol-dark.png` (220 px) : fournir le symbole en SVG pour des icônes parfaitement nettes.

## Variables d'environnement (.env.local)
ANTHROPIC_API_KEY, IMMO_AGENT_MODEL, N8N_DIAGNOSTIC_WEBHOOK_URL, CMS_API_URL, REVALIDATE_SECRET, CMS_API_KEY, NEXT_PUBLIC_SITE_URL, SESSION_SECRET, SECURE_COOKIES (détail dans `.env.example`).
Le cookie de session est `secure` par défaut en production ; `SECURE_COOKIES=false` uniquement pour tester `next start` en HTTP local.

## Formulaires (devis, rendez-vous)
- `/api/quote` et `/api/booking` filtrent les robots (champ caché `website`) et valident les champs, puis transmettent à Laravel (`POST {CMS_API_URL}/quotes` et `/bookings`, en-tête `X-Front-Key` = `CMS_API_KEY`, IP du visiteur dans `X-Client-Ip`).
- Laravel enregistre (admin → Demandes de devis, Rendez-vous), envoie l'email à l'équipe et les webhooks n8n (`LEADS_NOTIFY_EMAIL`, `N8N_QUOTE_WEBHOOK_URL`, `N8N_BOOKING_WEBHOOK_URL` dans `immo-back/.env`).
- Créneaux : générés par `src/lib/booking.ts`, créneaux pris lus dans Laravel (`GET /bookings/taken`). Un créneau ne peut être réservé qu'une fois (index unique côté Laravel).
- Sans `CMS_API_URL` + `CMS_API_KEY`, ou Laravel arrêté, ces formulaires affichent une erreur d'envoi : rien n'est perdu en silence.

## Espace client
- L'administrateur crée le compte dans l'admin Laravel (**Clients**), communique le mot de passe temporaire par un canal sûr, puis alimente le portail (tâches, jalons, livrables, documents, tickets). Le client se connecte sur `/connexion`.
- Connexion : `/api/auth/login` → Laravel (`POST /portal/login`). Le jeton Sanctum du client reste dans le cookie de session httpOnly et n'est utilisé que par le serveur Next (avec `CMS_API_KEY`) ; déconnexion = jeton révoqué.
- Aperçu : bouton **Aperçu du portail** dans l'admin Laravel → code à usage unique (2 min) → session en lecture seule de 30 min sur `/dashboard`.
- La « signature » d'un document est une acceptation en ligne horodatée, pas une signature électronique qualifiée : faire valider sa valeur juridique.
- Aucun secret (clés API) n'est stocké dans le portail : à transmettre par un coffre-fort de mots de passe.
- Sécurité : mots de passe hachés par Laravel (bcrypt), session signée en cookie httpOnly (secure en production), isolation des données par client vérifiée par Laravel, limites de tentatives.
- `/admin` redirige vers l'administration Laravel.

## Application installable (PWA)
- Manifest (`src/app/manifest.ts`), icônes 192/512/maskable/Apple (`public/icons/`), service worker (`public/sw.js`), page hors ligne (`public/offline.html`).
- Pages publiques : réseau d'abord, copie conservée pour la lecture hors ligne (30 pages max). Espace client, administration et API : jamais mis en cache.
- Après une modification de `sw.js`, incrémenter `VERSION` pour purger les anciens caches.

## Pas encore branché
- Agenda réel pour les créneaux de /contact (créneaux générés par le front, doublons bloqués par Laravel).
