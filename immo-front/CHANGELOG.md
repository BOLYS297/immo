# Journal des versions

## v11 — 19 octobre 2026 · Confiance, conformité, PWA

### Contenus (FR/EN)
- `/recommandations` : les chiffres non prouvés (100 %, -70 %, 99,99 % « garantie ») sont remplacés par des faits vérifiables (4 pôles, 30 min de cadrage, 1 portail client). Règle de publication des avis reformulée pour correspondre au fonctionnement réel.
- `/services` : « Cas d'usage » devient « Missions types » ; les résultats chiffrés deviennent ce que nous mesurons. Suppression des formulations « garantir zéro panne » et « 99,99 % ».
- Bilan de diagnostic : « chargement inférieur à une seconde » devient « budget de performance fixé dès le cadrage ».
- Pied de page : suppression du voyant « Systèmes opérationnels », qui n'était relié à aucune surveillance.

### Conformité
- Nouvelles pages `/mentions-legales` (loi n° 2010/021) et `/confidentialite` (loi n° 2024/017, en vigueur depuis le 23 juin 2026), rédigées d'après les traitements réels du code. Informations juridiques centralisées dans `src/content/legal.ts`.
- Mention d'information sous les formulaires de chiffrage et de rendez-vous. Liens légaux dans le pied de page et le sitemap.

### Marque
- Gilroy : plus aucune requête 404. La police est chargée et préchargée automatiquement dès que les fichiers sont déposés dans `public/fonts/`.

### PWA
- Application installable (vérifiée par Chrome : aucune erreur d'installabilité) : icônes 192/512/maskable/Apple sur Bleu Nuit, manifest complet avec raccourcis (Espace client, Lancer un projet), service worker, page hors ligne bilingue. Les données privées (portail, admin, API) ne sont jamais mises en cache.

### Sécurité et données
- Cookie de session `secure` par défaut en production.
- Portail : les éléments d'un client sont filtrés en base. Avant, le chargement des 3 000 derniers éléments tous clients confondus pouvait faire disparaître des données au-delà de ce seuil.
- PostgreSQL : index sur éléments par client, email et slug ; index unique sur les créneaux (8 réservations simultanées testées → 1 acceptée, 7 refusées en 409). Recherche par `id` sur la clé primaire. Une base indisponible au démarrage n'est plus considérée comme définitivement en échec.
