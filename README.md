# CMS Starter Kit — Symfony 7.4 (LTS)

CMS d'agence : pages construites en **blocs drag & drop**, **SEO natif** (meta, Open Graph, JSON-LD, sitemap, redirections 301 automatiques), **bibliothèque médias** (WebP + miniatures auto) et admin sur mesure.

Site de démo intégré : **BIVOUAK CAFÉ** (maquette one-page).

## Prérequis

PHP ≥ 8.2 (extensions `gd`, `pdo_mysql`, `intl` recommandée), Composer, Docker (pour MySQL) ou un MySQL 8 local.

## Installation

```bash
make install    # composer + assets + MySQL (Docker) + schéma + fixtures + serveur
```

- Site : https://localhost:8000
- Admin : https://localhost:8000/admin — `admin@agence.fr` / `admin` (à changer !)

Commandes utiles : `make asset.watch` (Tailwind en continu), `make db.reset` (base neuve + fixtures), `make logs`, `make stop`. Liste complète dans le `Makefile`.

## Tests

Les tests tournent sur **SQLite** (`var/test.db`) : aucun Docker requis.

```bash
make test             # PHPUnit complet
make test.unit        # unitaires (blocs, split_pairs, redirections…)
make test.functional  # smoke tests front + admin, SEO, redirections 301
make behat            # scénarios d'acceptance (features/)
make test.all         # tout
make lint             # lint twig / yaml / container
```

- PHPUnit : `tests/Unit` (logique pure) et `tests/Functional` (via `DatabaseWebTestCase`, qui recrée le schéma + fixtures avant chaque test)
- Behat : `features/*.feature` + contexte `tests/Behat/FeatureContext.php` (reset de la base à chaque scénario, étape `Given I am authenticated as admin`)

## Architecture

```
src/
├── Block/            # Système de blocs
│   ├── BlockTypeInterface.php   # 1 bloc = clé + template + formulaire + défauts
│   ├── BlockRegistry.php        # auto-découverte (tag app.block_type)
│   └── Type/                    # hero, cards, menu_list, feature_band, cta_card, contact, rich_text
├── Controller/
│   ├── PageController.php       # rendu front par slug (+ preview brouillons pour admins)
│   ├── SeoController.php        # sitemap.xml, robots.txt
│   ├── ContactController.php    # formulaire de contact (honeypot + CSRF)
│   └── Admin/                   # dashboard, pages/builder, médias, navigation, redirections, réglages
├── Entity/           # Page, Block, Media, Redirect, Setting, NavigationItem, User
├── EventListener/    # SlugHistoryListener (301 auto), RedirectSubscriber (404 → redirection)
├── Service/          # MediaUploader (WebP/miniatures GD), SettingsProvider
└── Twig/AppExtension # render_cms_block(), setting(), nav_items(), split_pairs

templates/
├── admin/            # interface d'administration (Tailwind)
├── blocks/           # templates front des blocs (thème du site)
└── front/            # base, page, sitemap

assets/styles/app.css # thème Tailwind v4 (@theme) — couleurs et fonts du client
```

## Créer un nouveau type de bloc

1. Créez `src/Block/Type/MonBlock.php` (étendez `AbstractBlockType`) : clé, libellé, champs du formulaire, données par défaut.
2. Créez `templates/blocks/mon_bloc.html.twig` (reçoit `block` et `data`).
3. C'est tout — le bloc apparaît dans la palette admin automatiquement.

## Nouveau site client

1. Dupliquez ce dépôt.
2. Remplacez les variables `@theme` dans `assets/styles/app.css` (couleurs, fonts extraites de la maquette).
3. Adaptez/ajoutez les templates de `templates/blocks/`.
4. Écrivez les fixtures avec le contenu réel, puis `doctrine:fixtures:load`.

Voir `CLAUDE.md` pour le workflow d'intégration de maquette assisté par IA, et `PROMPT-GENERATION-SITE.md` pour le prompt type.

## Production

`APP_ENV=prod` + `APP_SECRET` fort dans `.env.local`, vrai `MAILER_DSN`, puis :

```bash
composer install --no-dev --optimize-autoloader
php bin/console asset-map:compile
php bin/console tailwind:build --minify
php bin/console cache:clear
```

Pensez à changer le mot de passe admin (ou créez un utilisateur dédié en base).
