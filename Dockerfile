# syntax=docker/dockerfile:1

# =============================================================================
# Image de production FrankenPHP (mode worker) pour le CMS starter kit Symfony.
#
# Build :  docker build -t cms-starter .
# Voir compose.prod.yaml pour le déploiement complet (app + MySQL).
# =============================================================================

FROM dunglas/frankenphp:1-php8.4 AS frankenphp_upstream

# -----------------------------------------------------------------------------
# Image de base : extensions PHP + outils système communs
# -----------------------------------------------------------------------------
FROM frankenphp_upstream AS base

WORKDIR /app

# Dépendances système (curl pour le healthcheck, git pour composer)
RUN apt-get update && apt-get install --no-install-recommends -y \
        acl \
        file \
        gettext \
        git \
        curl \
    && rm -rf /var/lib/apt/lists/*

# Extensions PHP nécessaires à l'application (gd + pdo_mysql + intl + opcache…)
RUN set -eux; \
    install-php-extensions \
        @composer \
        apcu \
        intl \
        opcache \
        zip \
        pdo_mysql \
        gd \
    ;

# https://getcomposer.org/doc/03-cli.md#composer-allow-superuser
ENV COMPOSER_ALLOW_SUPERUSER=1

# Config PHP + Caddy + entrypoint
COPY --link frankenphp/conf.d/10-app.ini $PHP_INI_DIR/conf.d/
COPY --link --chmod=755 frankenphp/docker-entrypoint.sh /usr/local/bin/docker-entrypoint
COPY --link frankenphp/Caddyfile /etc/frankenphp/Caddyfile

ENTRYPOINT ["docker-entrypoint"]

# API admin de Caddy (toujours active sur :2019) pour le healthcheck
HEALTHCHECK --start-period=90s --interval=30s --timeout=5s --retries=5 \
    CMD curl -sf http://localhost:2019/config/ || exit 1

CMD [ "frankenphp", "run", "--config", "/etc/frankenphp/Caddyfile" ]

# -----------------------------------------------------------------------------
# Étape build : dépendances Composer + compilation des assets
# -----------------------------------------------------------------------------
FROM base AS builder

ENV APP_ENV=prod
ENV APP_DEBUG=0

# 1) Dépendances PHP d'abord (couche cache indépendante du code source).
#    composer.lock est volontairement gitignoré dans ce dépôt : on résout donc
#    les dépendances à partir de composer.json (`composer update`).
COPY --link composer.json ./
RUN set -eux; \
    composer update --no-cache --prefer-dist --no-dev \
        --no-autoloader --no-scripts --no-progress --no-interaction

# 2) Code source
COPY --link . ./
RUN rm -rf frankenphp/ compose*.yaml Dockerfile .dockerignore

# 3) Autoloader optimisé + .env.local.php figé pour la prod
RUN set -eux; \
    mkdir -p var/cache var/log public/uploads/media; \
    composer dump-autoload --classmap-authoritative --no-dev; \
    composer dump-env prod

# 4) Compilation des assets (importmap + Tailwind minifié + AssetMapper)
RUN set -eux; \
    php bin/console importmap:install; \
    php bin/console tailwind:build --minify; \
    php bin/console asset-map:compile; \
    php bin/console cache:clear --no-warmup; \
    php bin/console cache:warmup

# -----------------------------------------------------------------------------
# Image finale de production
# -----------------------------------------------------------------------------
FROM base AS prod

ENV APP_ENV=prod
ENV APP_DEBUG=0

# Mode worker Symfony : le kernel reste chargé en mémoire entre les requêtes.
ENV APP_RUNTIME="Runtime\FrankenPhpSymfony\Runtime"
ENV FRANKENPHP_CONFIG="worker ./public/index.php"

# php.ini de production (opcache activé, etc.)
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Récupération de l'application déjà buildée
COPY --from=builder --link /app /app

# Droits d'écriture sur les dossiers runtime
RUN set -eux; \
    chown -R www-data:www-data var public/uploads; \
    chmod -R a+rwX var public/uploads

VOLUME /app/public/uploads/media
