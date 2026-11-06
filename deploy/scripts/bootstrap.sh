#!/usr/bin/env bash
# Préparation du serveur pour la pile immo (root, une seule fois ; ré-exécutable sans danger).
#   curl -fsSL https://raw.githubusercontent.com/BOLYS297/immo/main/deploy/scripts/bootstrap.sh | sudo bash
#   ou : sudo bash deploy/scripts/bootstrap.sh
#
#   - utilisateur système « immo » (groupe docker), /opt/immo/{repo,env,state,edge} ;
#   - clone du dépôt public, secrets générés sur place (init-env.sh) ;
#   - immo-autodeploy (déploiement continu) et immo-edge-sync (branchement au frontal nginx) : copies root dans
#     /usr/local/sbin + minuteurs systemd.
# Ne touche à rien de GLOBUS.
set -euo pipefail
[[ $EUID -eq 0 ]] || { echo "À lancer en root" >&2; exit 1; }

GITHUB_REPO="${IMMO_GITHUB_REPO:-BOLYS297/immo}"
ROOT=/opt/immo

id immo >/dev/null 2>&1 || useradd --system --home-dir "$ROOT" --shell /usr/sbin/nologin --groups docker immo
install -d -o immo -g immo -m 0750 "$ROOT" "$ROOT/env" "$ROOT/state" "$ROOT/edge"
chmod 0700 "$ROOT/env"

if [[ ! -d "$ROOT/repo/.git" ]]; then
  runuser -u immo -- git clone --quiet "https://github.com/$GITHUB_REPO.git" "$ROOT/repo"
else
  runuser -u immo -- git -C "$ROOT/repo" fetch --quiet --no-tags origin main
fi
runuser -u immo -- git -C "$ROOT/repo" -c advice.detachedHead=false checkout --quiet --force --detach origin/main

runuser -u immo -- "$ROOT/repo/deploy/scripts/init-env.sh"

install -o root -g root -m 0755 "$ROOT/repo/deploy/scripts/autodeploy.sh" /usr/local/sbin/immo-autodeploy
install -o root -g root -m 0755 "$ROOT/repo/deploy/scripts/edge-sync.sh" /usr/local/sbin/immo-edge-sync
install -o root -g root -m 0644 "$ROOT"/repo/deploy/systemd/immo-*.service "$ROOT"/repo/deploy/systemd/immo-*.timer /etc/systemd/system/
systemctl daemon-reload
systemctl enable --now immo-edge-sync.timer immo-autodeploy.timer
echo "✔ Serveur prêt. Déploiement : automatique dès que la CI est verte sur main (journalctl -fu immo-autodeploy)."
