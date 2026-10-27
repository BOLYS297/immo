<?php

/*
|--------------------------------------------------------------------------
| Rôles de l'équipe et permissions de l'administration
|--------------------------------------------------------------------------
|
| Chaque permission est une Gate Laravel (AppServiceProvider) : routes protégées par
| le middleware « can:<permission> », liens du menu par @can. Le rôle « admin »
| a toutes les permissions (« * »). Les clients du portail (rôle « client »)
| n'ont jamais accès à l'administration.
|
*/

return [

    'permissions' => [
        'leads.manage' => 'Demandes de devis, rendez-vous et messages',
        'clients.manage' => 'Clients du portail',
        'pages.manage' => 'Composer les pages (brouillons)',
        'pages.publish' => 'Publier, mettre hors ligne et supprimer les pages',
        'media.manage' => 'Médiathèque',
        'menus.manage' => 'Menus du site',
        'content.manage' => 'Services, réalisations, témoignages et FAQ',
        'posts.write' => 'Rédiger ses propres articles (brouillons)',
        'posts.manage' => 'Modifier et supprimer tous les articles',
        'posts.publish' => 'Publier et dépublier les articles',
        'settings.manage' => 'Réglages du site',
        'users.manage' => 'Utilisateurs et rôles',
    ],

    'roles' => [
        'admin' => [
            'label' => 'Administrateur',
            'description' => 'Accès complet, y compris les utilisateurs et les réglages du site.',
            'permissions' => ['*'],
        ],
        'editor' => [
            'label' => 'Éditeur',
            'description' => 'Gère tout le contenu du site : pages, médias, menus, articles (et leur publication), services, réalisations, témoignages, FAQ.',
            'permissions' => ['pages.manage', 'pages.publish', 'media.manage', 'menus.manage', 'content.manage', 'posts.write', 'posts.manage', 'posts.publish'],
        ],
        'web_editor' => [
            'label' => 'Rédacteur web',
            'description' => 'Compose les pages et rédige ses articles en brouillon, avec aperçu ; un éditeur relit et publie.',
            'permissions' => ['pages.manage', 'media.manage', 'posts.write'],
        ],
        'author' => [
            'label' => 'Rédacteur',
            'description' => 'Rédige ses propres articles en brouillon ; un éditeur les relit et les publie.',
            'permissions' => ['posts.write'],
        ],
        'sales' => [
            'label' => 'Commercial',
            'description' => 'Traite les demandes de devis, les rendez-vous, les messages et les clients du portail.',
            'permissions' => ['leads.manage', 'clients.manage'],
        ],
    ],

    /*
    | Journal d'activité (App\Services\Activity) : libellés des actions et des contenus.
    | `routes` : écran de modification d'un contenu (lien depuis le journal), paramètre = identifiant.
    */
    'activity' => [
        'actions' => [
            'created' => 'a créé', 'updated' => 'a modifié', 'deleted' => 'a supprimé',
            'published' => 'a publié', 'restored' => 'a restauré une version de', 'discarded' => 'a annulé les modifications de',
            'reordered' => 'a réordonné', 'duplicated' => 'a dupliqué', 'role_changed' => 'a changé le rôle de',
            'settings_updated' => 'a modifié les réglages du site',
            'auth.login' => 's\'est connecté', 'auth.logout' => 's\'est déconnecté', 'auth.failed' => 'Connexion refusée',
        ],
        'types' => [
            'Page' => 'la page', 'PageSection' => 'la section', 'Post' => 'l\'article', 'Service' => 'le service', 'Project' => 'la réalisation',
            'Faq' => 'la question', 'Testimonial' => 'l\'avis', 'Media' => 'l\'image', 'MenuItem' => 'le lien de menu', 'User' => 'le compte',
            'QuoteRequest' => 'la demande de devis', 'Booking' => 'le rendez-vous', 'ContactMessage' => 'le message', 'PortalItem' => 'l\'élément du portail',
        ],
        'routes' => [
            'Page' => 'admin.pages.edit', 'PageSection' => 'admin.sections.edit', 'Post' => 'admin.posts.edit', 'Service' => 'admin.services.edit',
            'Project' => 'admin.projects.edit', 'Faq' => 'admin.faqs.edit', 'Testimonial' => 'admin.testimonials.edit', 'MenuItem' => 'admin.menus.items.edit',
            'QuoteRequest' => 'admin.quotes.show', 'ContactMessage' => 'admin.contact.show',
        ],
    ],

    // Ancien nom du rôle administrateur : mêmes droits.
    'aliases' => ['super_admin' => 'admin'],

];
