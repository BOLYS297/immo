<?php

/*
|--------------------------------------------------------------------------
| CMS — pages composées de sections
|--------------------------------------------------------------------------
|
| Chaque type de bloc correspond à un composant React du front Next.js
| (immo-front/src/components/blocks/Blocks.tsx). Le schéma `fields`
| génère le formulaire de l'admin et les règles de validation.
|
| Types de champs : text, textarea, richtext (« ## » intertitre, « - » liste,
| **gras**, [lien](/chemin ou https://…)), link (chemin interne « /… » ou URL
| http(s)), image (média de la médiathèque ; vide en anglais = image de la version
| française), repeater (liste d'éléments décrits par `fields`, `max` éléments).
| Le contenu est saisi par langue ; une valeur vide reprend la langue par défaut,
| puis le texte par défaut du front.
|
| `settings` : réglages de présentation communs aux langues (listes de choix).
| `group` : rubrique du menu « Ajouter une section ».
|
*/

// Réglages de présentation partagés par les blocs génériques.
$background = ['type' => 'select', 'label' => 'Fond', 'default' => 'satin', 'options' => [
    'satin' => 'Blanc satiné (fond du site)', 'white' => 'Carte blanche', 'sky' => 'Bleu ciel', 'dark' => 'Bleu nuit',
]];
$align = ['type' => 'select', 'label' => 'Alignement', 'default' => 'left', 'options' => ['left' => 'À gauche', 'center' => 'Centré']];
// « Compact » : marges réduites, pour enchaîner les blocs d'une page de contenu (titre, chiffres, liste…).
$spacing = ['type' => 'select', 'label' => 'Marges', 'default' => 'normal', 'options' => ['normal' => 'Normales', 'compact' => 'Réduites']];
// Image de fond (médiathèque) et voile qui garde le texte lisible ; ajoutés aux blocs dont le fond est réglable (fin du fichier).
$backgroundLook = [
    'background_image' => ['type' => 'image', 'label' => 'Image de fond (facultative)'],
    'overlay' => ['type' => 'select', 'label' => 'Voile sur l\'image de fond', 'default' => 'dark', 'options' => ['dark' => 'Bleu nuit (texte blanc)', 'light' => 'Clair (texte bleu nuit)', 'none' => 'Aucun (image décorative claire)']],
];

$config = [

    'locales' => ['fr' => 'Français', 'en' => 'English'],

    /*
    | Pages « système » : servies par des routes dédiées du front et liées depuis les menus.
    | Ni suppression, ni changement d'adresse, ni dépublication (seulement leurs sections et leur SEO).
    | Créées par database/seeders/PageSeeder.php (textes : data/system-pages.json, généré par le front).
    */
    'system_pages' => ['home', 'services', 'a-propos', 'recommandations', 'contact', 'tech-insights'],

    'default_locale' => 'fr',

    'blocks' => [

        /* ─── Blocs génériques : utilisables sur toutes les pages ─── */

        'text' => [
            'label' => 'Texte — titre et paragraphes',
            'group' => 'Blocs génériques',
            'icon' => 'bi-text-paragraph',
            'fields' => [
                'eyebrow' => ['type' => 'text', 'label' => 'Sur-titre'],
                'title' => ['type' => 'text', 'label' => 'Titre'],
                'body' => ['type' => 'richtext', 'label' => 'Texte'],
            ],
            'settings' => ['background' => $background, 'spacing' => $spacing, 'align' => $align],
        ],

        'cards' => [
            'label' => 'Grille de cartes',
            'group' => 'Blocs génériques',
            'icon' => 'bi-grid-3x2-gap',
            'fields' => [
                'title' => ['type' => 'text', 'label' => 'Titre'],
                'intro' => ['type' => 'textarea', 'label' => 'Introduction'],
                'items' => ['type' => 'repeater', 'label' => 'Cartes', 'item_label' => 'Carte', 'max' => 12, 'fields' => [
                    'image' => ['type' => 'image', 'label' => 'Image (facultative)'],
                    'title' => ['type' => 'text', 'label' => 'Titre'],
                    'text' => ['type' => 'richtext', 'label' => 'Texte'],
                    'link_label' => ['type' => 'text', 'label' => 'Libellé du lien (facultatif)'],
                    'href' => ['type' => 'link', 'label' => 'Lien'],
                ]],
            ],
            'settings' => [
                'background' => $background,
                'spacing' => $spacing,
                'columns' => ['type' => 'select', 'label' => 'Colonnes (grand écran)', 'default' => '3', 'options' => ['2' => '2 colonnes', '3' => '3 colonnes', '4' => '4 colonnes']],
            ],
        ],

        'media_text' => [
            'label' => 'Image et texte',
            'group' => 'Blocs génériques',
            'icon' => 'bi-image',
            'fields' => [
                'image' => ['type' => 'image', 'label' => 'Image'],
                'eyebrow' => ['type' => 'text', 'label' => 'Sur-titre'],
                'title' => ['type' => 'text', 'label' => 'Titre'],
                'body' => ['type' => 'richtext', 'label' => 'Texte'],
                'link_label' => ['type' => 'text', 'label' => 'Libellé du bouton (facultatif)'],
                'href' => ['type' => 'link', 'label' => 'Lien du bouton'],
            ],
            'settings' => [
                'background' => $background,
                'spacing' => $spacing,
                'image_position' => ['type' => 'select', 'label' => 'Position de l\'image', 'default' => 'right', 'options' => ['right' => 'À droite du texte', 'left' => 'À gauche du texte']],
            ],
        ],

        'stats' => [
            'label' => 'Chiffres clés',
            'group' => 'Blocs génériques',
            'icon' => 'bi-123',
            'fields' => [
                'title' => ['type' => 'text', 'label' => 'Titre'],
                'items' => ['type' => 'repeater', 'label' => 'Chiffres', 'item_label' => 'Chiffre', 'max' => 6, 'fields' => [
                    'value' => ['type' => 'text', 'label' => 'Valeur (ex. « 4 », « 30 min »)'],
                    'label' => ['type' => 'text', 'label' => 'Légende'],
                ]],
            ],
            'settings' => ['background' => $background, 'spacing' => $spacing],
        ],

        'faq' => [
            'label' => 'Questions fréquentes',
            'group' => 'Blocs génériques',
            'icon' => 'bi-question-circle',
            'fields' => [
                'title' => ['type' => 'text', 'label' => 'Titre'],
                'items' => ['type' => 'repeater', 'label' => 'Questions', 'item_label' => 'Question', 'max' => 20, 'fields' => [
                    'question' => ['type' => 'text', 'label' => 'Question'],
                    'answer' => ['type' => 'richtext', 'label' => 'Réponse'],
                ]],
            ],
            'settings' => ['background' => $background, 'spacing' => $spacing],
        ],

        'team_members' => [
            'label' => 'Équipe — membres avec photos',
            'group' => 'Blocs génériques',
            'icon' => 'bi-person-badge',
            'fields' => [
                'title' => ['type' => 'text', 'label' => 'Titre'],
                'intro' => ['type' => 'textarea', 'label' => 'Introduction'],
                'members' => ['type' => 'repeater', 'label' => 'Membres', 'item_label' => 'Membre', 'max' => 16, 'fields' => [
                    'photo' => ['type' => 'image', 'label' => 'Photo (carrée de préférence)'],
                    'name' => ['type' => 'text', 'label' => 'Nom'],
                    'role' => ['type' => 'text', 'label' => 'Fonction / pôle'],
                    'bio' => ['type' => 'textarea', 'label' => 'Présentation courte'],
                    'link' => ['type' => 'link', 'label' => 'Lien (LinkedIn, portfolio…)'],
                ]],
            ],
            'settings' => [
                'background' => $background,
                'spacing' => $spacing,
                'columns' => ['type' => 'select', 'label' => 'Colonnes (grand écran)', 'default' => '4', 'options' => ['2' => '2 colonnes', '3' => '3 colonnes', '4' => '4 colonnes']],
                'photo_shape' => ['type' => 'select', 'label' => 'Forme des photos', 'default' => 'round', 'options' => ['round' => 'Rondes', 'square' => 'Carrées arrondies']],
            ],
        ],

        'page_header' => [
            'label' => 'En-tête de page (titre principal)',
            'group' => 'Blocs génériques',
            'icon' => 'bi-type-h1',
            'fields' => [
                'eyebrow' => ['type' => 'text', 'label' => 'Sur-titre (facultatif)'],
                'title' => ['type' => 'text', 'label' => 'Titre principal (un seul par page)'],
                'intro' => ['type' => 'textarea', 'label' => 'Introduction'],
            ],
            'settings' => [
                'size' => ['type' => 'select', 'label' => 'Taille du titre', 'default' => 'xl', 'options' => ['xl' => 'Très grand', 'lg' => 'Grand']],
            ],
        ],

        'callout' => [
            'label' => 'Encadré (règle, garantie…)',
            'group' => 'Blocs génériques',
            'icon' => 'bi-shield-check',
            'fields' => [
                'title' => ['type' => 'text', 'label' => 'Titre'],
                'text' => ['type' => 'textarea', 'label' => 'Texte'],
                'link_label' => ['type' => 'text', 'label' => 'Libellé du lien (facultatif)'],
                'href' => ['type' => 'link', 'label' => 'Lien'],
            ],
            'settings' => ['spacing' => $spacing],
        ],

        'quote_panel' => [
            'label' => 'Citation sur bandeau Bleu Nuit',
            'group' => 'Blocs génériques',
            'icon' => 'bi-quote',
            'fields' => [
                'label' => ['type' => 'text', 'label' => 'Petit libellé (ex. « Notre philosophie »)'],
                'quote' => ['type' => 'textarea', 'label' => 'Citation'],
                'text' => ['type' => 'textarea', 'label' => 'Texte d\'accompagnement'],
            ],
        ],

        /* ─── Blocs dynamiques : contenus gérés ailleurs dans l'admin ─── */

        'posts_list' => [
            'label' => 'Derniers articles (Tech Insights)',
            'group' => 'Blocs dynamiques',
            'icon' => 'bi-journal-text',
            'fields' => [
                'title' => ['type' => 'text', 'label' => 'Titre'],
                'intro' => ['type' => 'textarea', 'label' => 'Introduction'],
                'link_label' => ['type' => 'text', 'label' => 'Libellé du lien « tous les articles » (vide = pas de lien)'],
            ],
            'settings' => [
                'background' => $background,
                'spacing' => $spacing,
                'count' => ['type' => 'select', 'label' => 'Nombre d\'articles', 'default' => '3', 'options' => ['3' => '3 articles', '6' => '6 articles', '9' => '9 articles', 'all' => 'Tous les articles publiés']],
            ],
        ],

        'projects_grid' => [
            'label' => 'Réalisations (cas clients)',
            'group' => 'Blocs dynamiques',
            'icon' => 'bi-briefcase',
            'fields' => [
                'title' => ['type' => 'text', 'label' => 'Titre'],
                'intro' => ['type' => 'textarea', 'label' => 'Introduction'],
                'results_label' => ['type' => 'text', 'label' => 'Libellé « Résultats » (ex. Résultat obtenu)'],
            ],
            'settings' => [
                'background' => $background,
                'spacing' => $spacing,
                'count' => ['type' => 'select', 'label' => 'Nombre', 'default' => '3', 'options' => ['3' => '3 réalisations', '6' => '6 réalisations', 'all' => 'Toutes les réalisations publiées']],
                'selection' => ['type' => 'select', 'label' => 'Sélection', 'default' => 'all', 'options' => ['all' => 'Toutes', 'featured' => 'Mises en avant seulement']],
            ],
        ],

        'faq_list' => [
            'label' => 'FAQ (questions gérées dans la rubrique FAQ)',
            'group' => 'Blocs dynamiques',
            'icon' => 'bi-patch-question',
            'fields' => [
                'title' => ['type' => 'text', 'label' => 'Titre'],
                'category' => ['type' => 'text', 'label' => 'Catégorie (nom français exact ; vide = toutes les questions)'],
            ],
            'settings' => ['background' => $background, 'spacing' => $spacing],
        ],

        'testimonials_wall' => [
            'label' => 'Mur d\'avis clients',
            'group' => 'Blocs dynamiques',
            'icon' => 'bi-chat-quote',
            'fields' => [
                'title' => ['type' => 'text', 'label' => 'Titre'],
                'intro' => ['type' => 'textarea', 'label' => 'Introduction'],
                'empty_text' => ['type' => 'textarea', 'label' => 'Message si aucun avis n\'est publié (vide = bloc masqué)'],
            ],
            'settings' => ['background' => $background, 'spacing' => $spacing],
        ],

        /* ─── Blocs fonctionnels : formulaires et outils du site ─── */

        'quote_form' => [
            'label' => 'Formulaire de chiffrage',
            'group' => 'Blocs fonctionnels',
            'icon' => 'bi-calculator',
            'fields' => [
                'title' => ['type' => 'text', 'label' => 'Titre'],
                'intro' => ['type' => 'textarea', 'label' => 'Introduction'],
            ],
            'settings' => [
                'background' => $background,
                'spacing' => $spacing,
                'default_domain' => ['type' => 'select', 'label' => 'Pôle présélectionné', 'default' => 'ia', 'options' => ['ia' => 'IA & Automatisation', 'web' => 'Web & Mobile', 'infra' => 'Infra & Sécurité']],
            ],
        ],

        'contact_form' => [
            'label' => 'Formulaire « Écrivez-nous » (messages)',
            'group' => 'Blocs fonctionnels',
            'icon' => 'bi-envelope',
            'fields' => [
                'title' => ['type' => 'text', 'label' => 'Titre'],
                'intro' => ['type' => 'textarea', 'label' => 'Introduction'],
            ],
            'settings' => ['background' => $background, 'spacing' => $spacing],
        ],

        'booking' => [
            'label' => 'Prise de rendez-vous (3 étapes)',
            'group' => 'Blocs fonctionnels',
            'icon' => 'bi-calendar-check',
            'fields' => [
                'title' => ['type' => 'text', 'label' => 'Titre'],
                'intro' => ['type' => 'textarea', 'label' => 'Introduction'],
                'aside_title' => ['type' => 'text', 'label' => 'Titre des coordonnées (mise en page « page »)'],
            ],
            'settings' => [
                'background' => $background,
                'spacing' => $spacing,
                'layout' => ['type' => 'select', 'label' => 'Mise en page', 'default' => 'section', 'options' => ['section' => 'Section (titre secondaire)', 'page' => 'Page de contact (titre principal et coordonnées du site à droite)']],
            ],
        ],

        /* ─── Blocs des pages du site (champ vide = texte par défaut du site) ─── */

        'service_domains' => [
            'label' => 'Domaines d\'expertise et livrables',
            'group' => 'Pages du site',
            'icon' => 'bi-diagram-3',
            'fields' => [
                'title' => ['type' => 'text', 'label' => 'Titre'],
                'deliverables_label' => ['type' => 'text', 'label' => 'Libellé « Livrables »'],
                'domains' => ['type' => 'repeater', 'label' => 'Domaines (source « saisis ici » ; sinon : repli si aucun service n\'est publié)', 'item_label' => 'Domaine', 'max' => 6, 'fields' => [
                    'title' => ['type' => 'text', 'label' => 'Titre'],
                    'benefit' => ['type' => 'textarea', 'label' => 'Bénéfice'],
                    'deliverables' => ['type' => 'textarea', 'label' => 'Livrables (un par ligne)'],
                    'cta' => ['type' => 'text', 'label' => 'Libellé du bouton'],
                    'href' => ['type' => 'link', 'label' => 'Lien du bouton'],
                ]],
            ],
            'settings' => [
                'source' => ['type' => 'select', 'label' => 'Domaines affichés', 'default' => 'manual', 'options' => ['manual' => 'Saisis dans ce bloc', 'services' => 'Services publiés (rubrique « Services »)']],
            ],
        ],

        'use_cases' => [
            'label' => 'Missions types (situation, approche, objectif)',
            'group' => 'Pages du site',
            'icon' => 'bi-kanban',
            'fields' => [
                'title' => ['type' => 'text', 'label' => 'Titre'],
                'situation_label' => ['type' => 'text', 'label' => 'Libellé « Situation »'],
                'solution_label' => ['type' => 'text', 'label' => 'Libellé « Notre approche »'],
                'result_label' => ['type' => 'text', 'label' => 'Libellé « Objectif mesuré »'],
                'cases' => ['type' => 'repeater', 'label' => 'Missions', 'item_label' => 'Mission', 'max' => 6, 'fields' => [
                    'title' => ['type' => 'text', 'label' => 'Titre'],
                    'situation' => ['type' => 'textarea', 'label' => 'Situation'],
                    'solution' => ['type' => 'textarea', 'label' => 'Approche'],
                    'result' => ['type' => 'text', 'label' => 'Objectif mesuré'],
                ]],
            ],
        ],

        'methodology' => [
            'label' => 'Méthode en étapes',
            'group' => 'Pages du site',
            'icon' => 'bi-signpost-split',
            'fields' => [
                'title' => ['type' => 'text', 'label' => 'Titre'],
                'step_label' => ['type' => 'text', 'label' => 'Libellé « Étape »'],
                'steps' => ['type' => 'repeater', 'label' => 'Étapes', 'item_label' => 'Étape', 'max' => 8, 'fields' => [
                    'title' => ['type' => 'text', 'label' => 'Titre'],
                    'text' => ['type' => 'textarea', 'label' => 'Texte'],
                ]],
            ],
        ],

        'about_intro' => [
            'label' => 'À propos — titre principal et définitions',
            'group' => 'Pages du site',
            'icon' => 'bi-info-circle',
            'fields' => [
                'title' => ['type' => 'text', 'label' => 'Titre principal'],
                'intro' => ['type' => 'textarea', 'label' => 'Introduction'],
                'items' => ['type' => 'repeater', 'label' => 'Définitions', 'item_label' => 'Définition', 'max' => 6, 'fields' => [
                    'title' => ['type' => 'text', 'label' => 'Mot'],
                    'text' => ['type' => 'textarea', 'label' => 'Texte'],
                ]],
            ],
        ],

        'team' => [
            'label' => 'Équipe — histoire et pôles',
            'group' => 'Pages du site',
            'icon' => 'bi-people',
            'fields' => [
                'title' => ['type' => 'text', 'label' => 'Titre'],
                'body' => ['type' => 'textarea', 'label' => 'Histoire (ligne vide = nouveau paragraphe)'],
                'members' => ['type' => 'repeater', 'label' => 'Pôles / membres', 'item_label' => 'Pôle', 'max' => 8, 'fields' => [
                    'title' => ['type' => 'text', 'label' => 'Nom du pôle'],
                    'text' => ['type' => 'textarea', 'label' => 'Texte'],
                ]],
            ],
        ],

        'commitments' => [
            'label' => 'Engagements numérotés et bouton',
            'group' => 'Pages du site',
            'icon' => 'bi-check2-square',
            'fields' => [
                'title' => ['type' => 'text', 'label' => 'Titre'],
                'intro' => ['type' => 'textarea', 'label' => 'Introduction'],
                'items' => ['type' => 'repeater', 'label' => 'Engagements', 'item_label' => 'Engagement', 'max' => 8, 'fields' => [
                    'title' => ['type' => 'text', 'label' => 'Titre'],
                    'text' => ['type' => 'textarea', 'label' => 'Texte'],
                ]],
                'button' => ['type' => 'text', 'label' => 'Libellé du bouton'],
                'href' => ['type' => 'link', 'label' => 'Lien du bouton'],
            ],
        ],

        /* ─── Blocs de l'accueil ─── */

        'hero' => [
            'label' => 'Hero — accroche et symbole 3D',
            'group' => 'Accueil',
            'icon' => 'bi-stars',
            'fields' => [
                'eyebrow' => ['type' => 'text', 'label' => 'Sur-titre'],
                'title' => ['type' => 'text', 'label' => 'Titre principal'],
                'text' => ['type' => 'textarea', 'label' => 'Texte d\'introduction'],
            ],
        ],

        'statement' => [
            'label' => 'Manifeste — grande phrase révélée au défilement',
            'group' => 'Accueil',
            'icon' => 'bi-quote',
            'fields' => [
                'text' => ['type' => 'textarea', 'label' => 'Phrase'],
            ],
        ],

        'services' => [
            'label' => 'Liste des pôles / services',
            'group' => 'Accueil',
            'icon' => 'bi-list-columns',
            'fields' => [
                'title' => ['type' => 'text', 'label' => 'Titre'],
                'all' => ['type' => 'text', 'label' => 'Libellé du lien « toutes nos solutions »'],
                'rows' => ['type' => 'repeater', 'label' => 'Lignes', 'item_label' => 'Ligne', 'max' => 8, 'fields' => [
                    'title' => ['type' => 'text', 'label' => 'Titre'],
                    'text' => ['type' => 'textarea', 'label' => 'Texte'],
                    'href' => ['type' => 'link', 'label' => 'Lien'],
                ]],
            ],
        ],

        'pillars' => [
            'label' => 'Piliers / valeurs',
            'group' => 'Accueil',
            'icon' => 'bi-columns-gap',
            'fields' => [
                'title' => ['type' => 'text', 'label' => 'Titre'],
                'items' => ['type' => 'repeater', 'label' => 'Piliers', 'item_label' => 'Pilier', 'max' => 8, 'fields' => [
                    'title' => ['type' => 'text', 'label' => 'Titre'],
                    'text' => ['type' => 'textarea', 'label' => 'Texte'],
                ]],
            ],
        ],

        'diagnostic' => [
            'label' => 'Bilan IA (widget de diagnostic)',
            'group' => 'Blocs fonctionnels',
            'icon' => 'bi-activity',
            'fields' => [
                'title' => ['type' => 'text', 'label' => 'Titre'],
                'text' => ['type' => 'textarea', 'label' => 'Texte'],
            ],
        ],

        'terminal' => [
            'label' => 'Agent IMMO (terminal de discussion)',
            'group' => 'Blocs fonctionnels',
            'icon' => 'bi-terminal',
            'fields' => [
                'title' => ['type' => 'text', 'label' => 'Titre'],
                'text' => ['type' => 'textarea', 'label' => 'Texte'],
            ],
        ],

        'portal_preview' => [
            'label' => 'Aperçu du portail client',
            'group' => 'Blocs fonctionnels',
            'icon' => 'bi-window-sidebar',
            'fields' => [
                'title' => ['type' => 'text', 'label' => 'Titre'],
                'text' => ['type' => 'textarea', 'label' => 'Texte'],
                'button' => ['type' => 'text', 'label' => 'Libellé du bouton'],
            ],
        ],

        'cta_band' => [
            'label' => 'Bandeau d\'appel à l\'action',
            'group' => 'Blocs génériques',
            'icon' => 'bi-megaphone',
            'fields' => [
                'title' => ['type' => 'text', 'label' => 'Titre'],
                'text' => ['type' => 'textarea', 'label' => 'Texte'],
                'primary' => ['type' => 'text', 'label' => 'Bouton principal — libellé'],
                'primary_href' => ['type' => 'link', 'label' => 'Bouton principal — lien'],
                'secondary' => ['type' => 'text', 'label' => 'Bouton secondaire — libellé'],
                'secondary_href' => ['type' => 'link', 'label' => 'Bouton secondaire — lien'],
            ],
        ],

    ],

    /*
    | Médiathèque. Disque Laravel (config/filesystems.php) : « public » = serveur Laravel (php artisan storage:link).
    | Pour passer sur un stockage objet (S3, Cloudflare R2…) : configurer le disque « s3 » (AWS_*), installer
    | league/flysystem-aws-s3-v3, puis MEDIA_DISK=s3. Les fichiers déjà envoyés gardent leur disque d'origine.
    | SVG exclu : il peut contenir du script.
    */
    'media' => [
        'disk' => env('MEDIA_DISK', 'public'),
        'directory' => 'media',
        'max_kb' => 5120,
        'mimes' => ['jpg', 'jpeg', 'png', 'webp', 'gif'],
        'max_dimension' => 6000,
    ],

    /*
    | Réglages globaux du site (admin « Réglages du site », API GET /api/v1/site).
    | Types : text, textarea, email, url (http(s) absolue), link (comme les blocs : « /… » ou http(s)),
    | phone, lines (une valeur par ligne). `translatable` : saisi par langue, vide = langue par défaut.
    | `default` : valeur affichée tant que le réglage n'est pas enregistré (textes actuels du site).
    */
    'settings' => [
        'display' => [
            'label' => 'Affichage',
            'icon' => 'bi-stars',
            'fields' => [
                'logo_animation' => ['type' => 'select', 'label' => 'Animation du logo (accueil)', 'default' => 'auto', 'options' => [
                    'auto' => 'Respecter le réglage « réduire les animations » du visiteur (recommandé)',
                    'always' => 'Toujours animer : particules qui se rassemblent et fuient le curseur',
                ], 'help' => 'Certains visiteurs (vertiges, troubles de l\'attention) demandent à leur appareil de réduire les animations. « Toujours animer » passe outre pour tous.'],
            ],
        ],
        'identity' => [
            'label' => 'Identité',
            'icon' => 'bi-building',
            'fields' => [
                'site_name' => ['type' => 'text', 'label' => 'Nom du site', 'default' => 'IMMO Digital', 'help' => 'Copyright du pied de page et titre de l\'administration.'],
                'site_slogan' => ['type' => 'text', 'label' => 'Slogan (pied de page)', 'translatable' => true, 'default' => ['fr' => 'Libérez le potentiel de vos idées', 'en' => 'Unlock the potential of your ideas']],
            ],
        ],
        'contact' => [
            'label' => 'Coordonnées',
            'icon' => 'bi-telephone',
            'fields' => [
                'contact_email' => ['type' => 'email', 'label' => 'E-mail de contact', 'help' => 'Affiché dans le pied de page, la page Contact et les mentions légales. Vide = non affiché.'],
                'contact_phones' => ['type' => 'lines', 'label' => 'Téléphones', 'max' => 4, 'default' => "+237 678 261 382\n+237 694 072 875", 'help' => 'Un numéro par ligne, au format international.'],
                'contact_whatsapp' => ['type' => 'phone', 'label' => 'Numéro WhatsApp', 'default' => '+237 678 261 382'],
                'contact_address' => ['type' => 'lines', 'label' => 'Adresse', 'max' => 4, 'translatable' => true, 'default' => ['fr' => "Douala, Yassa\nÀ côté de Eneo", 'en' => "Douala, Yassa\nNext to Eneo"], 'help' => 'Une ligne par ligne d\'adresse.'],
            ],
        ],
        'social' => [
            'label' => 'Réseaux sociaux',
            'icon' => 'bi-share',
            'help' => 'Vide = réseau non affiché dans le pied de page.',
            'fields' => [
                'social_linkedin' => ['type' => 'url', 'label' => 'LinkedIn'],
                'social_facebook' => ['type' => 'url', 'label' => 'Facebook'],
                'social_instagram' => ['type' => 'url', 'label' => 'Instagram'],
                'social_x' => ['type' => 'url', 'label' => 'X (Twitter)'],
                'social_youtube' => ['type' => 'url', 'label' => 'YouTube'],
            ],
        ],
        'header' => [
            'label' => 'En-tête',
            'icon' => 'bi-window-desktop',
            'fields' => [
                'header_cta_label' => ['type' => 'text', 'label' => 'Bouton d\'action — libellé', 'translatable' => true, 'default' => ['fr' => 'Lancer un projet', 'en' => 'Start a project']],
                'header_cta_url' => ['type' => 'link', 'label' => 'Bouton d\'action — lien', 'default' => '/contact'],
            ],
        ],
        'seo' => [
            'label' => 'Référencement par défaut',
            'icon' => 'bi-search',
            'help' => 'Utilisé par les pages qui n\'ont pas leur propre titre ou description SEO.',
            'fields' => [
                'seo_title' => ['type' => 'text', 'label' => 'Titre', 'translatable' => true, 'default' => ['fr' => 'IMMO Digital — Agence digitale', 'en' => 'IMMO Digital — Digital agency']],
                'seo_description' => ['type' => 'textarea', 'label' => 'Description', 'translatable' => true, 'default' => [
                    'fr' => 'IMMO Digital transforme vos idées en expériences digitales cohérentes, créatives et performantes : identité, communication et développement de solutions numériques.',
                    'en' => 'IMMO Digital turns your ideas into coherent, creative and high-performing digital experiences: identity, communication and digital solutions development.',
                ]],
            ],
        ],
    ],

    /*
    | Emplacements de menus du front (admin « Menus »). `depth` 2 : les éléments de premier niveau
    | sont des colonnes (titre sans lien) qui regroupent des liens. `display` : l'élément peut être
    | réservé à l'ordinateur ou au menu mobile.
    */
    'menus' => [
        'header' => ['label' => 'En-tête', 'help' => 'Liens de la barre de navigation. Le bouton d\'action se règle dans « Réglages du site ».', 'depth' => 1, 'display' => true],
        'footer' => ['label' => 'Pied de page', 'help' => 'Colonnes de liens. Créez d\'abord une colonne, puis ajoutez-y des liens.', 'depth' => 2, 'display' => false],
        'legal' => ['label' => 'Liens légaux', 'help' => 'Bas du pied de page, à côté du copyright.', 'depth' => 1, 'display' => false],
    ],

    /* Icônes proposées pour les services (noms lucide-react, rendues par le front : ServiceDomains.tsx). */
    'service_icons' => [
        'workflow' => 'Automatisation', 'brain-circuit' => 'Intelligence artificielle', 'app-window' => 'Application web', 'smartphone' => 'Mobile',
        'code' => 'Développement', 'pen-tool' => 'Design', 'server-cog' => 'Infrastructure', 'cloud' => 'Cloud', 'shield-check' => 'Sécurité', 'chart-line' => 'Données',
    ],

    'menu_display' => ['all' => 'Partout', 'desktop' => 'Ordinateur seulement', 'mobile' => 'Menu mobile seulement'],

];

// Fond réglable + image de fond : blocs affichés dans une section (immo-front BlockSection.tsx).
foreach (['text', 'cards', 'media_text', 'stats', 'faq', 'team_members', 'posts_list', 'testimonials_wall', 'projects_grid', 'faq_list', 'quote_form', 'contact_form', 'booking'] as $type) {
    $config['blocks'][$type]['settings'] += $backgroundLook;
}

return $config;
