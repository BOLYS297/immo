#!/usr/bin/env bash
# Sauvegarde quotidienne de la pile immo (installé en /usr/local/sbin/immo-backup, minuteur systemd vers 03:30 UTC).
#
#   - base MySQL : mysqldump cohérent sans verrou (--single-transaction), compressé, vérifié (gzip + fin de dump) ;
#   - fichiers : volume storage (médiathèque app/public, pièces jointes des devis app/private) ;
#   - conservation : IMMO_BACKUP_KEEP_DAYS jours (14 par défaut), purge seulement après une sauvegarde réussie.
#
# Destination /opt/immo/backups (droits 700) : incluse dans les sauvegardes complètes du VPS faites par Hostinger,
# donc aussi copiée hors du serveur. Restauration : deploy/README.md.
set -euo pipefail
umask 077

DEST="${IMMO_BACKUP_DIR:-/opt/immo/backups}"
KEEP_DAYS="${IMMO_BACKUP_KEEP_DAYS:-14}"
stamp="$(date -u +%Y-%m-%dT%H%MZ)"
mkdir -p "$DEST"
chmod 700 "$DEST"

cid() { docker ps -q --filter label=com.docker.compose.project=immo --filter "label=com.docker.compose.service=$1" | head -n 1; }
fail() { echo "✖ $*" >&2; rm -f "$DEST"/*.part; exit 1; }

# 1. Base de données. Mot de passe passé par un fichier temporaire du conteneur (jamais en argument visible).
mysql_cid="$(cid mysql)"
[[ -n "$mysql_cid" ]] || fail "conteneur mysql de la pile immo introuvable"
db="$DEST/immo-db-$stamp.sql.gz"
# shellcheck disable=SC2016  # variables développées dans le conteneur
docker exec "$mysql_cid" sh -c '
  cnf="$(mktemp)" && printf "[client]\nuser=root\npassword=%s\n" "$MYSQL_ROOT_PASSWORD" >"$cnf" || exit 1
  mysqldump --defaults-extra-file="$cnf" --single-transaction --quick --routines --triggers --events \
    --hex-blob --default-character-set=utf8mb4 --no-tablespaces "$MYSQL_DATABASE"
  rc=$?; rm -f "$cnf"; exit $rc' | gzip -6 >"$db.part" || fail "mysqldump en échec"
gzip -t "$db.part" || fail "archive de la base corrompue"
zcat "$db.part" | tail -n 1 | grep -q '^-- Dump completed' || fail "dump de la base incomplet"
mv "$db.part" "$db"

# 2. Fichiers (médiathèque, pièces jointes) depuis le conteneur Laravel.
files=""
back_cid="$(cid back)"
if [[ -n "$back_cid" ]]; then
  files="$DEST/immo-storage-$stamp.tar.gz"
  docker exec "$back_cid" tar -C /app/storage -czf - app >"$files.part" || fail "archive des fichiers en échec"
  gzip -t "$files.part" || fail "archive des fichiers corrompue"
  mv "$files.part" "$files"
else
  echo "⚠ conteneur back absent : fichiers non sauvegardés cette fois" >&2
fi

# 3. Rotation.
find "$DEST" -maxdepth 1 -type f -name 'immo-*' -mtime "+$KEEP_DAYS" -delete
echo "✔ sauvegarde $stamp : $(du -h "$db" | cut -f1) base${files:+, $(du -h "$files" | cut -f1) fichiers} ($(find "$DEST" -maxdepth 1 -name 'immo-db-*' | wc -l) jours conservés)"
