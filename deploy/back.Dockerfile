# syntax=docker/dockerfile:1.7
# IMMO Digital — administration Laravel + API (immo-back), servie par FrankenPHP (HTTP :8080, TLS au frontal nginx).
# Contexte de construction : racine du dépôt.   docker build -f deploy/back.Dockerfile -t immo/back .

FROM composer:2 AS vendor
WORKDIR /app
COPY immo-back/composer.json immo-back/composer.lock ./
RUN composer install --no-dev --no-interaction --no-progress --prefer-dist --no-scripts --no-autoloader --ignore-platform-reqs
COPY immo-back/ ./
RUN composer dump-autoload --no-dev --optimize --classmap-authoritative --no-scripts \
 && rm -rf .env tests node_modules storage/logs/*.log bootstrap/cache/*.php public/storage

FROM dunglas/frankenphp:1-php8.3-alpine
RUN install-php-extensions pdo_mysql intl zip bcmath exif gd pcntl opcache \
 && cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
 # Utilisateur non privilégié : port 8080, pas besoin de la capacité « bind < 1024 » (incompatible no-new-privileges).
 && setcap -r /usr/local/bin/frankenphp \
 && chown -R www-data:www-data /config/caddy /data/caddy
COPY deploy/back/php.ini $PHP_INI_DIR/conf.d/zz-immo.ini
COPY deploy/back/Caddyfile /etc/immo/Caddyfile
COPY deploy/back/entrypoint.sh /usr/local/bin/immo-entrypoint

WORKDIR /app
COPY --from=vendor --chown=www-data:www-data /app /app
# Médiathèque (disque « public ») : équivalent de « php artisan storage:link ». storage/ = volume persistant.
RUN ln -s /app/storage/app/public /app/public/storage \
 && mkdir -p storage/app/public storage/app/private storage/framework/cache/data storage/framework/sessions \
             storage/framework/views storage/logs bootstrap/cache \
 && chmod +x /usr/local/bin/immo-entrypoint \
 && php artisan package:discover --no-ansi \
 && chown -R www-data:www-data storage bootstrap/cache

USER www-data
EXPOSE 8080
HEALTHCHECK --interval=15s --timeout=5s --start-period=30s --retries=5 \
  CMD php -r 'exit(@file_get_contents("http://127.0.0.1:8080/up") === false ? 1 : 0);'
ENTRYPOINT ["immo-entrypoint"]
CMD ["frankenphp", "run", "--config", "/etc/immo/Caddyfile"]
