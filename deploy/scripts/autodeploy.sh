#!/usr/bin/env bash
# Déploiement continu « pull » de la pile immo (installé en /usr/local/sbin/immo-autodeploy, minuteur systemd 1 min).
#
#   1. lit le dernier commit de main sur GitHub (git ls-remote : aucun quota d'API) ;
#   2. s'il n'est pas déjà en service, attend que le workflow CI de ce commit soit terminé ;
#   3. CI verte → deploy.sh <commit> ; CI rouge ou déploiement en échec → commit mis de côté (pas de nouvelle tentative
#      en boucle ; le commit suivant sera essayé normalement).
#
# Aucun secret côté GitHub ni sur le serveur : dépôt public, API GitHub lue sans authentification.
# Relancer un commit refusé : rm /opt/immo/state/rejected
set -euo pipefail

ROOT="${IMMO_ROOT:-/opt/immo}"
REPO="$ROOT/repo"
STATE="$ROOT/state"
GITHUB_REPO="${IMMO_GITHUB_REPO:-BOLYS297/immo}"
WORKFLOW="${IMMO_CI_WORKFLOW:-ci.yml}"
mkdir -p "$STATE"
cd "$ROOT"

sha="$(git ls-remote "https://github.com/$GITHUB_REPO.git" refs/heads/main | cut -f1)"
[[ "$sha" =~ ^[0-9a-f]{40}$ ]] || { echo "main introuvable sur $GITHUB_REPO" >&2; exit 1; }
[[ "$sha" == "$(cat "$STATE/deployed" 2>/dev/null || true)" ]] && exit 0
[[ "$sha" == "$(cat "$STATE/rejected" 2>/dev/null || true)" ]] && exit 0

runs="$(curl -fsS --max-time 20 -H 'Accept: application/vnd.github+json' -H 'User-Agent: immo-autodeploy' \
  "https://api.github.com/repos/$GITHUB_REPO/actions/workflows/$WORKFLOW/runs?head_sha=$sha&event=push&per_page=1")"
read -r status conclusion < <(python3 -c '
import json, sys
runs = json.load(sys.stdin).get("workflow_runs") or []
print(runs[0]["status"], runs[0]["conclusion"] or "-") if runs else print("absent -")
' <<<"$runs")

case "$status/$conclusion" in
  completed/success) ;;
  completed/*)
    echo "CI $conclusion pour ${sha:0:12} : commit non déployé"
    printf '%s\n' "$sha" >"$STATE/rejected"
    exit 0 ;;
  *) exit 0 ;;  # CI en attente ou en cours : prochain passage
esac

echo "▶ CI verte pour ${sha:0:12} : déploiement"
git -C "$REPO" fetch --quiet --no-tags origin main
git -C "$REPO" -c advice.detachedHead=false checkout --quiet --force --detach "$sha"
if ! "$REPO/deploy/scripts/deploy.sh" "$sha"; then
  printf '%s\n' "$sha" >"$STATE/rejected"
  echo "✖ Déploiement de ${sha:0:12} en échec (version précédente conservée)" >&2
  exit 1
fi
