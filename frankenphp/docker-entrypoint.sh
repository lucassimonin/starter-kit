#!/bin/sh
set -e

# Entrypoint de production : attend la base, met le schéma à jour, puis lance FrankenPHP.
# Les commandes de préparation ne s'exécutent que lorsqu'on démarre le serveur
# (frankenphp / php-server), pas pour un simple `docker compose run app bin/console …`.

if [ "$1" = 'frankenphp' ] || [ "$1" = 'php' ] || [ "$1" = 'frankenphp-worker' ]; then

	# --- Attente de la base de données ---------------------------------------
	if grep -q "^DATABASE_URL=" .env 2>/dev/null || [ -n "$DATABASE_URL" ]; then
		echo "En attente de la base de données…"
		ATTEMPTS=0
		until [ "$ATTEMPTS" -ge 30 ] || php bin/console dbal:run-sql -q "SELECT 1" >/dev/null 2>&1; do
			ATTEMPTS=$((ATTEMPTS + 1))
			sleep 2
		done
		if [ "$ATTEMPTS" -ge 30 ]; then
			echo "La base de données est injoignable, on continue quand même." >&2
		fi

		# --- Mise en place / mise à jour du schéma ---------------------------
		# AUTO_DB_SETUP : schema (défaut) | migrate | none
		case "${AUTO_DB_SETUP:-schema}" in
			migrate)
				echo "Application des migrations Doctrine…"
				php bin/console doctrine:migrations:migrate --no-interaction --all-or-nothing || true
				;;
			schema)
				echo "Synchronisation du schéma Doctrine…"
				php bin/console doctrine:database:create --if-not-exists --no-interaction || true
				php bin/console doctrine:schema:update --force --complete --no-interaction || true
				;;
			none)
				echo "AUTO_DB_SETUP=none : pas de modification du schéma."
				;;
		esac

		# Chargement des fixtures de démo au premier démarrage (optionnel).
		if [ "${LOAD_FIXTURES:-0}" = "1" ]; then
			echo "Chargement des fixtures de démonstration…"
			php bin/console doctrine:fixtures:load --no-interaction || true
		fi
	fi

	# Cache prod (au cas où l'environnement diffère de celui du build)
	php bin/console cache:clear --no-warmup >/dev/null 2>&1 || true
	php bin/console cache:warmup >/dev/null 2>&1 || true
fi

exec docker-php-entrypoint "$@"
