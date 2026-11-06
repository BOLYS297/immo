# syntax=docker/dockerfile:1.7
# IMMO Digital — site public Next.js (immo-front), serveur autonome (« output: standalone ») sur :3000.
# Contexte de construction : racine du dépôt.   docker build -f deploy/front.Dockerfile -t immo/front .
#
# next.config.mjs lit CMS_API_URL, ADMIN_ORIGINS et MEDIA_HOSTS à la construction (images autorisées, en-têtes
# d'encadrement de l'aperçu) et NEXT_PUBLIC_SITE_URL est intégrée au code client : ce sont des arguments de build.

FROM node:22-alpine AS deps
WORKDIR /app
COPY immo-front/package.json immo-front/package-lock.json ./
RUN npm ci --no-audit --no-fund

FROM node:22-alpine AS build
WORKDIR /app
ARG NEXT_PUBLIC_SITE_URL=https://immodigital.tech
ARG CMS_API_URL=https://admin.immodigital.tech/api/v1
ARG ADMIN_ORIGINS=https://admin.immodigital.tech
ARG MEDIA_HOSTS=
ENV NEXT_TELEMETRY_DISABLED=1 \
    NEXT_PUBLIC_SITE_URL=$NEXT_PUBLIC_SITE_URL \
    CMS_API_URL=$CMS_API_URL \
    ADMIN_ORIGINS=$ADMIN_ORIGINS \
    MEDIA_HOSTS=$MEDIA_HOSTS \
    NODE_OPTIONS=--max-old-space-size=2048
COPY --from=deps /app/node_modules ./node_modules
COPY immo-front/ ./
RUN npm run build

# sharp : optimisation des images (next/image), obligatoire en mode standalone avec Next 14.
FROM node:22-alpine AS sharp
WORKDIR /opt/sharp
RUN npm init -y >/dev/null && npm install --no-audit --no-fund --omit=dev sharp@0.33.5

FROM node:22-alpine
WORKDIR /app
ENV NODE_ENV=production \
    NEXT_TELEMETRY_DISABLED=1 \
    NEXT_SHARP_PATH=/opt/sharp/node_modules/sharp \
    PORT=3000 \
    HOSTNAME=0.0.0.0
COPY --from=sharp /opt/sharp /opt/sharp
COPY --from=build --chown=node:node /app/.next/standalone ./
COPY --from=build --chown=node:node /app/.next/static ./.next/static
COPY --from=build --chown=node:node /app/public ./public
USER node
EXPOSE 3000
HEALTHCHECK --interval=15s --timeout=5s --start-period=30s --retries=5 \
  CMD wget -q -O /dev/null http://127.0.0.1:3000/robots.txt || exit 1
CMD ["node", "server.js"]
