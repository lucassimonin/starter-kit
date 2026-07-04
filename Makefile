COMPOSER=symfony composer
CONSOLE=symfony console
PHP=symfony php
DOCKER=docker compose

.PHONY: up stop logs install clean db.init db.fixture db.clean db.reset \
        asset.build asset.watch test test.unit test.functional behat test.all \
        lint cc prod.build prod.up prod.down prod.logs prod.shell prod.deploy

## —— Projet ————————————————————————————————————————————————————————
up: ## Démarre MySQL + serveur Symfony (en tâche de fond)
	$(DOCKER) up -d
	@symfony serve -d

stop: ## Stoppe le serveur et les conteneurs
	-@symfony server:stop
	$(DOCKER) stop

logs: ## Logs du serveur Symfony
	@symfony server:log

install: ## Installation complète (deps + assets + db + fixtures + serveur)
	$(COMPOSER) install
	$(MAKE) asset.build
	$(DOCKER) up -d
	@sleep 5 # Attente du démarrage de MySQL
	$(MAKE) db.init
	$(MAKE) db.fixture
	@symfony serve -d
	@echo "→ Site  : https://localhost:8000"
	@echo "→ Admin : https://localhost:8000/admin (admin@agence.fr / admin)"

clean: ## Supprime db, vendor, var
	$(MAKE) db.clean
	$(MAKE) stop
	-rm -rf vendor/ node_modules/ var/ assets/vendor/

## —— Base de données ———————————————————————————————————————————————
db.init: ## Crée la base et le schéma
	$(CONSOLE) doctrine:database:create --if-not-exists
	$(CONSOLE) doctrine:schema:create

db.fixture: ## Charge les fixtures (démo Bivouak)
	$(CONSOLE) doctrine:fixtures:load --no-interaction

db.clean: ## Supprime la base
	$(CONSOLE) doctrine:database:drop --force --if-exists

db.reset: ## Base neuve + fixtures
	$(MAKE) db.clean
	$(MAKE) db.init
	$(MAKE) db.fixture

## —— Assets ————————————————————————————————————————————————————————
asset.build: ## importmap + compilation Tailwind
	$(CONSOLE) importmap:install
	$(CONSOLE) tailwind:build

asset.watch: ## Recompile Tailwind à chaque modification
	$(CONSOLE) tailwind:build --watch

## —— Tests —————————————————————————————————————————————————————————
# Les tests utilisent SQLite (var/test.db) : pas besoin de Docker.
test: ## Tous les tests PHPUnit (unitaires + fonctionnels)
	$(PHP) vendor/bin/phpunit

test.unit: ## Tests unitaires seulement
	$(PHP) vendor/bin/phpunit --testsuite=unit

test.functional: ## Tests fonctionnels seulement (smoke front/admin, redirections)
	$(PHP) vendor/bin/phpunit --testsuite=functional

behat: ## Tests d'acceptance Behat (features/)
	$(PHP) vendor/bin/behat --format=progress

test.all: ## PHPUnit + Behat
	$(MAKE) test
	$(MAKE) behat

lint: ## Lint Twig, YAML et container
	$(CONSOLE) lint:twig templates
	$(CONSOLE) lint:yaml config
	$(CONSOLE) lint:container

## —— Divers ————————————————————————————————————————————————————————
cc: ## Vide le cache
	$(CONSOLE) cache:clear

prod.build: ## Build de production (Tailwind minifié + assets compilés)
	$(CONSOLE) tailwind:build --minify
	$(CONSOLE) asset-map:compile

## —— Déploiement Docker (FrankenPHP) ———————————————————————————————
PROD_COMPOSE=docker compose --env-file .env.prod -f compose.prod.yaml

prod.up: ## Build + démarre la prod (FrankenPHP worker + MySQL)
	$(PROD_COMPOSE) up -d --build

prod.down: ## Arrête la prod
	$(PROD_COMPOSE) down

prod.logs: ## Logs de la prod
	$(PROD_COMPOSE) logs -f

prod.shell: ## Shell dans le conteneur app
	$(PROD_COMPOSE) exec app sh

prod.deploy: ## Déploiement : git pull + rebuild + assets + schéma DB + cache + nettoyage
	@echo "→ Récupération du code…"
	git pull --ff-only
	@echo "→ Reconstruction de l'image et redémarrage…"
	$(PROD_COMPOSE) up -d --build
	@echo "→ Compilation des assets (Tailwind minifié + AssetMapper)…"
	$(PROD_COMPOSE) exec -T app php bin/console tailwind:build --minify
	$(PROD_COMPOSE) exec -T app php bin/console asset-map:compile
	@echo "→ Mise à jour du schéma de base de données…"
	$(PROD_COMPOSE) exec -T app php bin/console doctrine:schema:update --force --complete --no-interaction
	@echo "→ Vidage et réchauffage du cache…"
	$(PROD_COMPOSE) exec -T app php bin/console cache:clear
	$(PROD_COMPOSE) exec -T app php bin/console cache:warmup
	@echo "→ Nettoyage des anciennes images…"
	-docker image prune -f
	@echo "✓ Déploiement terminé. Logs : make prod.logs"
