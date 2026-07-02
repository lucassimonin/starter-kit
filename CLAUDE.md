# CLAUDE.md — Contexte du projet

## Qui / quoi

Je suis une **agence web**. Ce dépôt est mon **CMS starter kit Symfony** : je le duplique pour chaque client, puis j'intègre la maquette HTML du client sous forme de blocs éditables. L'objectif : que le client puisse tout modifier dans l'admin sans toucher au code.

## Stack (ne pas changer sans me demander)

- Symfony 7.4 LTS, PHP ≥ 8.2, Doctrine ORM 3, MySQL 8 (Docker : `compose.yaml`)
- Tests : PHPUnit (`tests/Unit`, `tests/Functional` sur SQLite) + Behat (`features/`) — toute nouvelle fonctionnalité doit être couverte
- Front : Twig + **Tailwind v4** compilé (symfonycasts/tailwind-bundle, config dans `assets/styles/app.css` via `@theme`) — **jamais de Tailwind CDN**
- JS : AssetMapper + importmap, **vanilla JS** (pas de framework front). Drag & drop admin : SortableJS
- Icônes front : Lucide (CDN unpkg, tolérée pour les icônes uniquement)
- Admin : templates dans `templates/admin/`, palette stone/emerald, formulaires stylés par `templates/admin/_form_theme.html.twig`

## Architecture à respecter

- **1 type de bloc = 3 choses** : classe dans `src/Block/Type/` (clé, label, formulaire, défauts) + template `templates/blocks/{clé}.html.twig` + rien d'autre (auto-enregistré via le tag `app.block_type`)
- Contenu des blocs : tableau JSON dans `Block::data`. Champs multi-lignes "gauche | droite" parsés par le filtre Twig `split_pairs` (menus, témoignages, liens, bullets)
- Collections (cartes, catégories) : `CollectionType` + prototype, géré par `assets/admin.js`
- SEO : champs sur `Page` (metaTitle, metaDescription, ogImage, canonical, noindex, structuredData JSON-LD). Sitemap + robots dans `SeoController`. **Un changement de slug crée automatiquement une redirection 301** (`SlugHistoryListener`)
- Réglages globaux : entité `Setting` clé/valeur via `SettingsProvider`, accessibles en Twig par `setting('clé')`
- Navigation : entité `NavigationItem` (header/footer, position, isButton), rendue dans `templates/front/base.html.twig`
- Tous les POST admin passent un token CSRF `csrf_token('admin')` ; formulaire de contact : token `contact` + honeypot `website` + destinataire lu depuis le bloc en base (jamais depuis le POST)

## Workflow d'intégration d'une maquette client

1. Lire la maquette HTML fournie, **lister les blocs identifiés et attendre ma validation** avant d'intégrer
2. Extraire couleurs/fonts → variables `@theme` dans `assets/styles/app.css`
3. Réutiliser les blocs existants quand la structure correspond ; sinon créer de nouveaux types de blocs (le catalogue s'enrichit à chaque projet)
4. Seeder le contenu réel dans `src/DataFixtures/AppFixtures.php`
5. SEO : meta + JSON-LD adapté à l'activité (Restaurant, LocalBusiness…)
6. Fidélité visuelle maximale à la maquette (espacements, animations `reveal`, comportement du header au scroll)

## Conventions

- Tout le contenu visible doit être éditable dans l'admin
- Textes UI et commentaires en **français**
- Classes Tailwind littérales dans les templates (jamais de classes construites dynamiquement — le scanner ne les verrait pas)
- Images de contenu : passer par la bibliothèque Médias (WebP auto), `loading="lazy"` hors hero, `alt` toujours renseigné
- Pas de nouveau bundle sans me demander

## Commandes utiles (Makefile)

```bash
make install       # installation complète (deps, assets, db, fixtures, serveur)
make db.reset      # base neuve + fixtures (admin@agence.fr / admin)
make asset.watch   # Tailwind en continu
make test          # PHPUnit ; make behat ; make test.all ; make lint
```

Après toute modification de code : lancer `make test.all` et `make lint` avant de considérer le travail terminé.

## Site de démo actuel

Homepage = intégration de la maquette **Bivouak Café** (restaurant, Montpellier) : hero, cards (concept), menu_list (carte), feature_band (avis), cta_card (workshop), contact. Deuxième page : mentions légales (rich_text, noindex).
