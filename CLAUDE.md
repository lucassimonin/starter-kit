# CLAUDE.md — Contexte du projet

## Qui / quoi

Je suis une **agence web**. Ce dépôt est mon **CMS starter kit Symfony** : je le duplique pour chaque client, puis j'intègre la maquette HTML du client sous forme de blocs éditables. L'objectif : que le client puisse tout modifier dans l'admin sans toucher au code.

## Stack (ne pas changer sans me demander)

- Symfony 7.4 LTS, PHP ≥ 8.2, Doctrine ORM 3, MySQL 8 (Docker : `compose.yaml`)
- Emails en local : **Mailpit** (service `mailer` dans compose.yaml, SMTP :1025, interface http://localhost:8025) — `MAILER_DSN=smtp://localhost:1025` en dev
- Tests : PHPUnit (`tests/Unit`, `tests/Functional` sur SQLite) + Behat (`features/`) — toute nouvelle fonctionnalité doit être couverte
- CI : GitHub Actions (`.github/workflows/ci.yml`) — lint twig/yaml/container + PHPUnit + Behat à chaque push
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
- Chaque envoi du formulaire de contact part par email **et** est enregistré en base (`ContactMessage`), consultable dans Admin → Messages (badge non-lus dans la sidebar, marquer lu, répondre, supprimer)
- Cookies (RGPD) : barre de consentement affichée seulement si `setting('analytics_id')` est renseigné ; Analytics chargé par `assets/app.js` **uniquement après acceptation** (jamais injecté côté serveur), choix conservé 6 mois (cookie `cookie_consent`), retrait via « Gérer les cookies » dans le footer. Textes personnalisables dans Réglages (`cookie_banner_text`, `privacy_url`)
- **Champs image dans les formulaires admin : toujours `MediaPickerType`** (`src/Form/MediaPickerType.php`) — bouton « Parcourir » ouvrant la bibliothèque en modale (`#mediaPicker` dans `admin/base.html.twig`, endpoint JSON `admin_media_picker`, JS dans `admin.js`)
- **Contenu HTML éditable : toujours `RichTextType`** (`src/Form/RichTextType.php`) — éditeur contenteditable vanilla (H2/H3, gras, italique, listes, liens), rendu par `rich_text_editor_widget` dans `_form_theme`, synchronisé avec le textarea par `admin.js`
- Checklist SEO : service `SeoChecker` (`src/Service/SeoChecker.php`) — score /100 et pastilles dans le panneau SEO du builder (meta title/description, OG image, blocs actifs, alt manquants, JSON-LD valide, noindex)
- **Module actualités** : entités `Post` (publié si `publishedAt` non nul et passé, sinon brouillon prévisualisable par les admins) et `PostCategory`. Front : `/actualites` (listing paginé 9/page, filtre `?categorie=slug`), `/actualites/{slug}` (article + OG + JSON-LD BlogPosting), `/actualites/rss.xml`. Admin : `/admin/posts` (CRUD, publier/brouillon, catégories). Les articles sont dans le sitemap et le changement de slug d'un Post crée aussi une 301 (préfixe `/actualites/`)
- **Multilingue** : locales dans `services.yaml` (`app.locales`, `app.default_locale`, `app.extra_locales_pattern`). Langue par défaut sans préfixe d'URL, autres langues sous `/{locale}/…`. `Page`/`Post`/`NavigationItem` portent `locale` ; les traductions d'une page partagent un `translationGroup` (hreflang + sélecteur de langue automatiques, une accueil par langue, slug unique par langue). Panneau « Traductions » dans le builder pour créer une version à traduire. Helpers Twig : `page_path()`, `post_path()`, `page_translations()`
- **Prévisualisation partageable** : chaque page a un `previewToken` — un brouillon est visible via `?preview={token}` sans compte (bouton « Lien de prévisualisation » dans le builder)
- **Duplication de page** (blocs + SEO, en brouillon) et **export/import JSON** (`/admin/pages/{id}/export`, bouton « Importer un JSON ») pour faire circuler les pages entre projets — les types de blocs inconnus sont ignorés à l'import
- **Page 404 éditable** : la page de slug `erreur-404` (publiée) est rendue sur toute URL introuvable (`RedirectSubscriber`). **Mode maintenance** : case à cocher dans Réglages → front en 503 (admin et admins connectés épargnés), template `front/maintenance.html.twig`
- **Historique** : `PageRevision` — snapshot des blocs pris avant chaque modification (ajout, édition, réorganisation, suppression…), 20 conservés, restauration en un clic depuis le builder (`RevisionRecorder`)
- Login admin protégé par rate limiting (`login_throttling` : 5 essais / 15 min)

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

Homepage = intégration de la maquette **Bivouak Café** (restaurant, Montpellier) : hero, cards (concept), menu_list (carte), feature_band (avis), cta_card (workshop), contact. Version anglaise réduite sous `/en` (même translationGroup). Autres pages : mentions légales (rich_text, noindex) et `erreur-404`. Actualités : 2 articles publiés + 1 brouillon, catégories « Événements » et « Côté cuisine ». Réglage `analytics_id` = `G-DEMO000000` (barre cookies visible — remplacer ou vider en prod).
