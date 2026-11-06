# Déploiement — immodigital.tech

Production sur le VPS Hostinger `srv2071586` (88.223.95.244), **partagé avec GLOBUS** (globus-engineering-sarl.com).

| Hôte | Service |
|---|---|
| `https://immodigital.tech` (`www.` → redirection) | site Next.js (`immo-front`) |
| `https://admin.immodigital.tech` | administration Laravel `/admin` + API `/api/v1` (`immo-back`) |

## Architecture

```
Internet ─► nginx GLOBUS (80/443, conteneur globus-nginx-1)
              ├─ globus-engineering-sarl.com ─► pile GLOBUS (inchangée)
              └─ *.immodigital.tech  (conf.d/immo.conf) ─► réseau immo_edge
                                                            ├─ front   (Next.js :3000)
                                                            └─ back    (FrankenPHP/Laravel :8080)
                                                                 └─ réseau immo_internal ─► mysql 8.4
```

- Projet Compose **`immo`** séparé (`deploy/docker-compose.yml`) : aucun port publié, mémoire/CPU plafonnés
  (mysql 768 Mo, back 512 Mo, front 512 Mo, scheduler 192 Mo).
- Le frontal nginx appartient à GLOBUS : `immo-edge-sync` (minuteur 1 min) y ajoute `immo.conf`, le relie au réseau
  `immo_edge`, teste (`nginx -t`) puis recharge **sans coupure** ; un fichier refusé est retiré aussitôt. Si GLOBUS
  recrée son conteneur nginx, le branchement est rétabli en moins d'une minute.
- `immo.conf` est autonome (aucun include GLOBUS) et résout les conteneurs à la requête : nginx démarre même si la
  pile immo est arrêtée (les hôtes immo affichent alors « maintenance »).
- Certificat Let's Encrypt `immodigital.tech` (3 noms) dans le volume `globus_letsencrypt`, renouvelé par le certbot
  de GLOBUS (webroot `/var/www/acme`).

## Déploiement continu (CI/CD)

1. `git push` sur `main` → workflow **CI** (`.github/workflows/ci.yml`) : tests Laravel (MySQL), construction des deux
   images Docker, ShellCheck, `docker compose config`, `nginx -t`.
2. Le serveur (`immo-autodeploy`, minuteur 1 min) voit le nouveau commit (`git ls-remote`), attend la fin de la CI
   (API GitHub publique) et, si elle est verte, lance `deploy/scripts/deploy.sh <commit>` :
   construction dans un builder BuildKit plafonné (1 processeur, 3 Go), migrations, redémarrage, contrôle de santé,
   **retour automatique à la version précédente** en cas d'échec.
3. CI rouge ou déploiement en échec → le commit est mis de côté, la version en service reste en ligne.

Aucun secret n'est stocké sur GitHub : le dépôt est public et le serveur tire les changements lui-même.

## Exploitation (sur le serveur, en root)

```bash
journalctl -fu immo-autodeploy                       # suivre un déploiement
cat /opt/immo/state/deployed /opt/immo/state/releases.log
sudo -u immo /opt/immo/repo/deploy/scripts/deploy.sh <commit>    # déploiement manuel
sudo -u immo /opt/immo/repo/deploy/scripts/deploy.sh --rollback  # version précédente
rm /opt/immo/state/rejected                          # réessayer un commit refusé

cd /opt/immo/repo/deploy && export IMMO_VERSION=$(cat /opt/immo/state/deployed)
docker compose -p immo --env-file /opt/immo/env/compose.env -f docker-compose.yml ps
docker compose -p immo --env-file /opt/immo/env/compose.env -f docker-compose.yml logs -f back front
docker compose -p immo --env-file /opt/immo/env/compose.env -f docker-compose.yml exec back php artisan <commande>
```

Secrets : `/opt/immo/env/{back,front,mysql,compose}.env` (droits 600, utilisateur `immo`). Après modification :
`docker compose … up -d back front` (`front.env` : variables lues au démarrage ; `NEXT_PUBLIC_*`, `CMS_API_URL`,
`ADMIN_ORIGINS`, `MEDIA_HOSTS` sont aussi intégrées à la construction → redéployer).

Sauvegarde de la base :
```bash
docker exec immo-mysql-1 sh -c 'mysqldump -uroot -p"$MYSQL_ROOT_PASSWORD" --single-transaction immo' | gzip > immo-$(date +%F).sql.gz
```

## Installation initiale (déjà faite, pour mémoire)

1. DNS : `A @` et `A admin` → 88.223.95.244 (`www` = CNAME vers `immodigital.tech`).
2. `sudo bash deploy/scripts/bootstrap.sh` : utilisateur `immo`, `/opt/immo`, secrets, minuteurs systemd.
3. Certificat (une fois le DNS propagé) :
   ```bash
   docker run --rm -v globus_letsencrypt:/etc/letsencrypt -v globus_acme:/var/www/acme certbot/certbot:v3.1.0 \
     certonly --webroot -w /var/www/acme --cert-name immodigital.tech \
     -d immodigital.tech -d www.immodigital.tech -d admin.immodigital.tech --non-interactive --agree-tos
   ```
4. Premier déploiement puis contenu initial : `… exec back php artisan db:seed --force`
   (administrateur `ADMIN_EMAIL` / `ADMIN_PASSWORD` de `back.env` : changer le mot de passe à la première connexion).
