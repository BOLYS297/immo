#!/bin/sh
# Mise en cache de la configuration, des routes, des vues et des événements au démarrage : les variables
# d'environnement (secrets) ne sont connues qu'à l'exécution, jamais dans l'image.
set -e
cd /app
php artisan optimize --quiet
exec "$@"
