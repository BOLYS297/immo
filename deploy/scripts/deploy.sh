#!/usr/bin/env bash
# Mise en ligne d'un commit de la pile immo (idempotent). Exécuté par l'utilisateur « immo » sur le serveur :
#   /opt/immo/repo/deploy/scripts/deploy.sh <commit>     construit, migre, démarre, vérifie ; retour arrière si échec
#   /opt/immo/repo/deploy/scripts/deploy.sh --rollback   remet en service la version précédente (images en cache)
# Appelé automatiquement par immo-autodeploy quand la CI GitHub est verte sur main.
#
# Ne gêne pas GLOBUS : construction dans un builder BuildKit plafonné (1 processeur, 3 Go), projet Compose distinct,
# frontal nginx seulement rechargé (sans coupure) et uniquement si sa configuration immo a changé.
#
# Tout le script est dans main() : bash le lit en entier avant de l'exécuter, le « git checkout » qui remplace ce
# fichier en cours de route est donc sans effet sur l'exécution en cours.
set -euo pipefail
umask 027

main() {
  local ROOT="${IMMO_ROOT:-/opt/immo}"
  local REPO="$ROOT/repo" ENV_DIR="$ROOT/env" STATE="$ROOT/state" BUILDER="immo-builder"
  mkdir -p "$STATE" "$ROOT/edge"

  exec 9>"$ROOT/deploy.lock"
  flock -w 1800 9 || { log "Un autre déploiement est en cours" >&2; exit 75; }

  set -a
  # shellcheck disable=SC1091
  source "$ENV_DIR/compose.env"
  set +a
  COMPOSE=(docker compose -p immo --env-file "$ENV_DIR/compose.env" -f "$REPO/deploy/docker-compose.yml")

  local prev target skip_build
  prev="$(cat "$STATE/deployed" 2>/dev/null || true)"
  if [[ "${1:-}" == "--rollback" ]]; then
    target="$(grep -vx ".* $prev" "$STATE/releases.log" 2>/dev/null | tail -n 1 | cut -d' ' -f2 || true)"
    [[ -n "$target" ]] && docker image inspect "immo/back:$target" "immo/front:$target" >/dev/null 2>&1 \
      || { log "Aucune version précédente en cache" >&2; exit 1; }
    git -C "$REPO" -c advice.detachedHead=false checkout --quiet --force --detach "$target"
    skip_build=1
  else
    target="${1:-}"
    [[ "$target" =~ ^[0-9a-f]{40}$ ]] || { echo "Usage : deploy.sh <commit 40 caractères> | --rollback" >&2; exit 2; }
    skip_build=0
  fi

  if [[ $skip_build -eq 0 ]]; then
    git -C "$REPO" fetch --quiet --no-tags origin main
    git -C "$REPO" -c advice.detachedHead=false checkout --quiet --force --detach "$target"
    if ! docker buildx inspect "$BUILDER" >/dev/null 2>&1; then
      log "▶ Création du builder plafonné $BUILDER (1 processeur, 3 Go)"
      docker buildx create --name "$BUILDER" --driver docker-container \
        --driver-opt cpu-period=100000 --driver-opt cpu-quota=100000 \
        --driver-opt memory=3g --driver-opt memory-swap=4g >/dev/null
    fi
    log "▶ Construction des images (${target:0:12})"
    docker buildx build --builder "$BUILDER" --load --progress=plain \
      -f "$REPO/deploy/back.Dockerfile" -t "immo/back:$target" "$REPO"
    docker buildx build --builder "$BUILDER" --load --progress=plain \
      --build-arg "NEXT_PUBLIC_SITE_URL=https://$IMMO_DOMAIN" \
      --build-arg "CMS_API_URL=https://$IMMO_ADMIN_DOMAIN/api/v1" \
      --build-arg "ADMIN_ORIGINS=https://$IMMO_ADMIN_DOMAIN" \
      -f "$REPO/deploy/front.Dockerfile" -t "immo/front:$target" "$REPO"
  fi

  log "▶ Migrations puis démarrage (${target:0:12})"
  if ! up "$target" || ! health; then
    if [[ -n "$prev" && "$prev" != "$target" ]] && docker image inspect "immo/back:$prev" "immo/front:$prev" >/dev/null 2>&1; then
      log "↺ Échec : retour à la version ${prev:0:12}" >&2
      git -C "$REPO" -c advice.detachedHead=false checkout --quiet --force --detach "$prev" || true
      { up "$prev" && health; } || true
    fi
    exit 1
  fi

  # Configuration du frontal : validée par immo-edge-sync (nginx -t) avant rechargement, retirée si refusée.
  install -m 0644 "$REPO/deploy/nginx/immo.conf" "$ROOT/edge/immo.conf"
  if [[ -x /usr/local/sbin/immo-edge-sync ]]; then
    /usr/local/sbin/immo-edge-sync || log "⚠ immo-edge-sync en échec (journalctl -u immo-edge-sync)" >&2
  fi

  printf '%s\n' "$target" >"$STATE/deployed"
  printf '%s %s\n' "$(date -u +%Y-%m-%dT%H:%M:%SZ)" "$target" >>"$STATE/releases.log"

  # Purge : version en service + deux précédentes conservées (retour arrière immédiat).
  local repo tag
  for repo in immo/back immo/front; do
    { docker image ls "$repo" --format '{{.Tag}}' | grep -vx "$target" || true; } | tail -n +3 \
      | while read -r tag; do docker image rm "$repo:$tag" >/dev/null 2>&1 || true; done
  done
  docker buildx prune --builder "$BUILDER" --force --keep-storage 4gb >/dev/null 2>&1 || true

  "${COMPOSE[@]}" ps --format 'table {{.Service}}\t{{.Status}}'
  log "✔ immo déployé (${target:0:12})"
}

log() { printf '%s %s\n' "$(date -u +%H:%M:%S)" "$*"; }

# IMMO_VERSION exportée : toutes les commandes Compose suivantes (ps, logs) en ont besoin.
up() { export IMMO_VERSION="$1"; "${COMPOSE[@]}" up -d --remove-orphans; }

health() { # attend l'état « healthy » des services web
  local svc cid status
  for svc in back front; do
    status=absent
    for _ in $(seq 1 60); do
      cid="$("${COMPOSE[@]}" ps -q "$svc" 2>/dev/null || true)"
      status="$([[ -n "$cid" ]] && docker inspect --format '{{if .State.Health}}{{.State.Health.Status}}{{else}}{{.State.Status}}{{end}}' "$cid" || echo absent)"
      [[ "$status" == healthy ]] && break
      sleep 3
    done
    if [[ "$status" != healthy ]]; then
      log "✖ $svc non opérationnel (état : $status)" >&2
      "${COMPOSE[@]}" logs --tail 60 "$svc" >&2 || true
      return 1
    fi
  done
}

main "$@"
exit
