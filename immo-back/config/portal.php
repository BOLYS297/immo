<?php

/*
|--------------------------------------------------------------------------
| Portail client — éléments affichés dans l'espace client du front Next.js
|--------------------------------------------------------------------------
|
| Chaque type correspond à un module du tableau de bord
| (immo-front/src/components/dashboard). Le schéma `fields` génère les
| formulaires de l'admin et la validation ; les clés et valeurs sont celles
| attendues par le front (immo-front/src/lib/portal-types.ts).
|
| Types de champs : text, textarea, url (http/https), integer (min, max),
| amount (entier ≥ 0, vide autorisé), select (options ; `int` = valeurs numériques).
| Tous les éléments ont en plus un titre (120 caractères).
|
*/

return [

    'types' => [

        'task' => [
            'label' => 'Tâches (Kanban)',
            'item_label' => 'tâche',
            'icon' => 'bi-kanban',
            'fields' => [
                'owner' => ['type' => 'text', 'label' => 'Pôle / responsable', 'max' => 40],
                'col' => ['type' => 'select', 'label' => 'Colonne', 'int' => true, 'default' => 0, 'options' => [0 => 'Backlog', 1 => 'Sprint en cours', 2 => 'En revue', 3 => 'Validé']],
            ],
        ],

        'milestone' => [
            'label' => 'Jalons',
            'item_label' => 'jalon',
            'icon' => 'bi-flag',
            'fields' => [
                'due' => ['type' => 'text', 'label' => 'Échéance (ex. « Terminé », « Fin novembre »)', 'max' => 40],
                'progress' => ['type' => 'integer', 'label' => 'Avancement (%)', 'min' => 0, 'max' => 100, 'default' => 0],
            ],
        ],

        'deliverable' => [
            'label' => 'Livrables',
            'item_label' => 'livrable',
            'icon' => 'bi-box-seam',
            'fields' => [
                'note' => ['type' => 'text', 'label' => 'Description', 'max' => 200],
                'url' => ['type' => 'url', 'label' => 'Lien (dépôt, document…)', 'max' => 500],
            ],
        ],

        'document' => [
            'label' => 'Documents & factures',
            'item_label' => 'document',
            'icon' => 'bi-receipt',
            'fields' => [
                'kind' => ['type' => 'select', 'label' => 'Type', 'default' => 'invoice', 'options' => ['quote' => 'Devis', 'contract' => 'Contrat', 'invoice' => 'Facture']],
                'amount' => ['type' => 'amount', 'label' => 'Montant (FCFA)'],
                'status' => ['type' => 'select', 'label' => 'Statut', 'default' => 'pending', 'options' => ['toSign' => 'À signer', 'signed' => 'Signé', 'paid' => 'Payée', 'pending' => 'En attente']],
            ],
        ],

        'ticket' => [
            'label' => 'Tickets de support',
            'item_label' => 'ticket',
            'icon' => 'bi-life-preserver',
            'fields' => [
                'message' => ['type' => 'textarea', 'label' => 'Message du client', 'max' => 2000],
                'priority' => ['type' => 'select', 'label' => 'Priorité', 'int' => true, 'default' => 0, 'options' => [0 => 'Normale', 1 => 'Haute', 2 => 'Critique']],
                'status' => ['type' => 'select', 'label' => 'Statut', 'default' => 'open', 'options' => ['open' => 'Ouvert', 'progress' => 'En cours', 'resolved' => 'Résolu']],
                'reply' => ['type' => 'textarea', 'label' => 'Réponse de l\'équipe', 'max' => 2000],
            ],
        ],

    ],

    // Durée de validité d'une connexion au portail (jeton Sanctum).
    'session_days' => 7,

];
