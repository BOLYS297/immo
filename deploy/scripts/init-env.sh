#!/usr/bin/env bash
# Génère les fichiers de secrets de production de la pile immo (une seule fois, sur le serveur).
#   sudo -u immo /opt/immo/repo/deploy/scripts/init-env.sh [--domain immodigital.tech] [--admin-domain admin.immodigital.tech]
# N'écrase jamais un fichier existant. Aucun secret n'est affiché : ils restent dans /opt/immo/env (droits 600).
# Le mot de passe du premier administrateur (db:seed) est dans /opt/immo/env/back.env (ADMIN_PASSWORD).
set -euo pipefail
umask 077

ENV_DIR="${IMMO_ENV_DIR:-/opt/immo/env}"
DOMAIN="immodigital.tech"
ADMIN_DOMAIN=""
while [[ $# -gt 0 ]]; do
  case "$1" in
    --domain) DOMAIN="$2"; shift 2 ;;
    --admin-domain) ADMIN_DOMAIN="$2"; shift 2 ;;
    *) echo "Option inconnue : $1" >&2; exit 2 ;;
  esac
done
ADMIN_DOMAIN="${ADMIN_DOMAIN:-admin.$DOMAIN}"
mkdir -p "$ENV_DIR"

rand() { openssl rand -base64 48 | tr -dc 'A-Za-z0-9' | head -c "${1:-40}"; }
write_once() {
  local file="$ENV_DIR/$1"
  if [[ -e "$file" ]]; then echo "• $file existe déjà : conservé"; cat >/dev/null; return; fi
  cat >"$file"
  chmod 600 "$file"
  echo "✔ $file créé"
}

DB_PASSWORD="$(rand 40)"
FRONT_API_KEY="$(rand 48)"
REVALIDATE_SECRET="$(rand 48)"

write_once compose.env <<EOF
IMMO_DOMAIN=$DOMAIN
IMMO_ADMIN_DOMAIN=$ADMIN_DOMAIN
IMMO_ENV_DIR=$ENV_DIR
EOF

write_once mysql.env <<EOF
MYSQL_DATABASE=immo
MYSQL_USER=immo
MYSQL_PASSWORD=$DB_PASSWORD
MYSQL_ROOT_PASSWORD=$(rand 40)
EOF

write_once back.env <<EOF
APP_NAME="IMMO Digital"
APP_ENV=production
APP_KEY=base64:$(openssl rand -base64 32)
APP_DEBUG=false
APP_URL=https://$ADMIN_DOMAIN
APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US
APP_MAINTENANCE_DRIVER=file
BCRYPT_ROUNDS=12

LOG_CHANNEL=stderr
LOG_LEVEL=warning

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=immo
DB_USERNAME=immo
DB_PASSWORD=$DB_PASSWORD

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_SECURE_COOKIE=true
SESSION_DOMAIN=null
CACHE_STORE=database
QUEUE_CONNECTION=sync
BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
MEDIA_DISK=public

# E-mails (notifications des demandes du site) : « log » tant qu'aucun serveur SMTP n'est configuré.
MAIL_MAILER=log
MAIL_FROM_ADDRESS="contact@$DOMAIN"
MAIL_FROM_NAME="IMMO Digital"

FRONT_URL=https://$DOMAIN
FRONT_REVALIDATE_SECRET=$REVALIDATE_SECRET
FRONT_API_KEY=$FRONT_API_KEY

LEADS_NOTIFY_EMAIL=
N8N_QUOTE_WEBHOOK_URL=
N8N_BOOKING_WEBHOOK_URL=
N8N_MESSAGE_WEBHOOK_URL=

# Premier administrateur (php artisan db:seed) : changer le mot de passe dès la première connexion.
ADMIN_EMAIL=admin@$DOMAIN
ADMIN_PASSWORD=$(rand 24)
EOF

write_once front.env <<EOF
NEXT_PUBLIC_SITE_URL=https://$DOMAIN
CMS_API_URL=https://$ADMIN_DOMAIN/api/v1
CMS_API_KEY=$FRONT_API_KEY
REVALIDATE_SECRET=$REVALIDATE_SECRET
ADMIN_ORIGINS=https://$ADMIN_DOMAIN
MEDIA_HOSTS=
SESSION_SECRET=$(rand 48)
SECURE_COOKIES=
ANTHROPIC_API_KEY=
IMMO_AGENT_MODEL=claude-sonnet-5-5
N8N_DIAGNOSTIC_WEBHOOK_URL=
EOF
