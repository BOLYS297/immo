# IMMO Digital — administration et API (Laravel)

Back-office du site IMMO Digital : seule administration du projet, et API lue par le site Next.js (`../immo-front`).
Contenus bilingues FR/EN (spatie/laravel-translatable, langue de repli : français). Base MySQL.

## Ce que gère l'admin (`/admin`)

| Rubrique | Contenu |
|---|---|
| Pages & Sections | Pages composées de blocs (`config/cms.php`) ; brouillon, aperçu en direct, publication, historique des versions |
| Médiathèque | Images des sections, réalisations, avis, partage (stockage `MEDIA_DISK`, prêt pour S3/R2) |
| Services, Réalisations, Avis, FAQ, Articles | Contenus structurés affichés par des blocs dynamiques |
| Réglages du site, Menus | Coordonnées, réseaux, SEO par défaut, animation du logo ; menus d'en-tête et de pied de page |
| Devis, Rendez-vous, Messages, Clients | Demandes reçues par les formulaires du site ; comptes de l'espace client |
| Utilisateurs & rôles, Journal | Équipe (`config/admin.php`) et journal d'activité (12 mois) |

## Principes

- **Blocs** : un type de bloc = une entrée de `config/cms.php` (champs, réglages, groupe) + un composant React dans
  `immo-front/src/components/blocks/Blocks.tsx` + son type dans `immo-front/src/lib/blocks.ts`.
- **Publication** : sections, titre et SEO d'une page forment un brouillon ; « Publier » crée une révision (`page_revisions`),
  seule servie par l'API publique. Le site est prévenu (`FrontRevalidator`) et vide son cache aussitôt.
- **Pages système** (`cms.system_pages`) : accueil, Services, À propos, Recommandations, Contact, Tech Insights — ni suppression,
  ni changement d'adresse, ni mise hors ligne. Textes de départ : `database/seeders/data/system-pages.json`,
  généré par `immo-front/scripts/export-system-pages.mjs`.
- **Rôles** : Administrateur, Éditeur, Rédacteur web, Rédacteur, Commercial ; chaque permission est une Gate Laravel.
- **Réalisations** : mise en ligne seulement avec la date de l'accord écrit du client. Aucun contenu fictif n'est publié.

## API (`routes/api.php`, préfixe `/api/v1`)

- Lecture publique : `site`, `pages`, `pages/{slug}`, `posts`, `testimonials`, `services`, `projects`, `faqs` (paramètre `locale`).
- Avec la clé du front (`X-Front-Key` = `FRONT_API_KEY`) : formulaires (`quotes`, `bookings`, `messages`), aperçu (`preview/{jeton}`),
  espace client (`portal/*`, jeton Sanctum).

## Commandes

```bash
php artisan migrate --seed   # admin : ADMIN_EMAIL / ADMIN_PASSWORD (.env), sinon mot de passe aléatoire affiché une fois
php artisan serve            # http://127.0.0.1:8000/admin
php artisan config:clear && php artisan test   # jamais avec une configuration en cache (vraie base effacée)
```

Planification en production : `php artisan schedule:run` chaque minute (purge `model:prune`).
