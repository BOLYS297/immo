#!/usr/bin/env bash
# Branche les hôtes immo sur le frontal nginx de GLOBUS (installé en /usr/local/sbin/immo-edge-sync, minuteur 1 min).
#
# Le frontal (conteneur nginx du projet Compose « globus ») possède les ports 80/443. Ce script, idempotent :
#   1. relie ce conteneur au réseau « immo_edge » (perdu si GLOBUS recrée son conteneur nginx) ;
#   2. y installe /etc/nginx/conf.d/immo.conf (copie validée de deploy/nginx/immo.conf) quand il manque ou a changé ;
#   3. teste toute la configuration (nginx -t) : rechargement sans coupure si elle est valide, sinon retour immédiat au
#      fichier précédent. La configuration de GLOBUS n'est jamais modifiée.
# Ne fait rien tant que le certificat immodigital.tech n'existe pas (nginx refuserait la configuration).
set -euo pipefail

SRC="${IMMO_EDGE_CONF:-/opt/immo/edge/immo.conf}"
NET="immo_edge"
DEST="/etc/nginx/conf.d/immo.conf"
PREV="/etc/nginx/immo.conf.prev"   # hors de conf.d : jamais chargé
CERT="/etc/letsencrypt/live/immodigital.tech/fullchain.pem"

cid="$(docker ps -q --filter label=com.docker.compose.project=globus --filter label=com.docker.compose.service=nginx | head -n 1)"
[[ -n "$cid" ]] || exit 0
# Frontal en cours de (re)démarrage : on attend qu'il soit opérationnel pour ne jamais interférer.
[[ "$(docker inspect -f '{{if .State.Health}}{{.State.Health.Status}}{{else}}{{.State.Status}}{{end}}' "$cid")" == healthy ]] || exit 0

if docker network inspect "$NET" >/dev/null 2>&1 \
  && ! docker inspect -f '{{range $k, $v := .NetworkSettings.Networks}}{{$k}} {{end}}' "$cid" | tr ' ' '\n' | grep -qx "$NET"; then
  docker network connect "$NET" "$cid"
  echo "✔ frontal relié au réseau $NET"
fi

[[ -s "$SRC" ]] || exit 0
docker exec "$cid" test -s "$CERT" || exit 0

want="$(sha256sum <"$SRC" | cut -d' ' -f1)"
have="$(docker exec "$cid" sh -c "sha256sum $DEST 2>/dev/null | cut -d' ' -f1" || true)"
[[ "$want" == "$have" ]] && exit 0

docker exec "$cid" sh -c "if [ -f $DEST ]; then cp $DEST $PREV; else rm -f $PREV; fi"
docker cp "$SRC" "$cid:$DEST"
if docker exec "$cid" nginx -t -q 2>/tmp/immo-nginx-test.log; then
  docker exec "$cid" nginx -s reload
  echo "✔ configuration immo installée sur le frontal (rechargement sans coupure)"
else
  docker exec "$cid" sh -c "if [ -f $PREV ]; then mv $PREV $DEST; else rm -f $DEST; fi"
  echo "✖ configuration immo refusée par nginx -t, fichier précédent restauré :" >&2
  cat /tmp/immo-nginx-test.log >&2
  exit 1
fi
