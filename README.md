# IMMO Digital — site et CMS

Site de l'agence IMMO Digital et son back-office, en deux applications :

| Dossier | Rôle | Technologies |
|---|---|---|
| [`immo-front/`](immo-front/) | Site public, espace client, aperçu des brouillons | Next.js 14 (App Router), TypeScript, Tailwind CSS |
| [`immo-back/`](immo-back/) | Administration (CMS) et API lue par le site | Laravel 13, PHP 8.3, MySQL |

Le site n'a ni base de données ni administration propres : pages, sections, menus, réglages, articles, services, réalisations,
avis et FAQ sont gérés dans l'admin Laravel (FR/EN) et lus par le site via l'API `immo-back/routes/api.php`.
Les formulaires du site (devis, rendez-vous, messages) et l'espace client passent par le serveur Next, qui appelle Laravel avec une clé partagée.

## Démarrer en local

Prérequis : PHP 8.3, Composer, MySQL 8, Node.js 20+.

```bash
# 1. Back-office et API
cd immo-back
composer install
cp .env.example .env            # base MySQL, FRONT_URL, clés partagées avec le front
php artisan key:generate
php artisan migrate --seed      # crée l'admin (mot de passe : ADMIN_PASSWORD, sinon affiché une fois)
php artisan storage:link        # images de la médiathèque
php artisan serve               # http://127.0.0.1:8000/admin

# 2. Site
cd ../immo-front
npm install
cp .env.example .env.local      # CMS_API_URL, CMS_API_KEY, REVALIDATE_SECRET…
npm run dev                     # http://localhost:3000
```

Les clés partagées doivent être identiques des deux côtés : `CMS_API_KEY` (front) = `FRONT_API_KEY` (back),
`REVALIDATE_SECRET` (front) = `FRONT_REVALIDATE_SECRET` (back). Voir les deux fichiers `.env.example`.

## Tests

```bash
cd immo-back
php artisan config:clear   # indispensable : une configuration en cache ferait tourner les tests sur la vraie base
php artisan test           # base dédiée « immo_testing » (phpunit.xml)
```

`tests/TestCase.php` refuse de démarrer si une configuration est en cache ou si la base ne se termine pas par `_testing`.

## Mise en production — à ne pas oublier

- `APP_ENV=production`, `APP_DEBUG=false`, nouvelles clés et nouveaux secrets (jamais ceux du développement).
- Compte administrateur : définir `ADMIN_PASSWORD` avant `db:seed`, ou changer le mot de passe dès la première connexion.
- Tâche planifiée : `php artisan schedule:run` chaque minute (purge du journal d'activité après 12 mois).
- Sauvegardes régulières de la base MySQL.
- `ADMIN_ORIGINS` (front) si l'admin a une autre adresse que `CMS_API_URL` : nécessaire à l'aperçu en direct.

## Documentation

- [`immo-back/README.md`](immo-back/README.md) : CMS, blocs, rôles, workflow de publication, API.
- [`immo-front/README.md`](immo-front/README.md) : site, langues, composants.
