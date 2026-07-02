# Prompt de génération de site — Starter Kit CMS Symfony

> Copie ce prompt, remplis les sections entre `[...]`, joins la maquette HTML, et envoie.

---

## Prompt à copier

```
Intègre un nouveau site client dans mon CMS Symfony (starter kit).

MAQUETTE : ci-jointe (fichier HTML). Elle est la référence visuelle absolue —
respecte fidèlement les couleurs, typos, espacements et animations.

CLIENT
- Nom : [nom du client]
- Activité : [restaurant, portfolio, artisan…]
- Domaine cible : [exemple.fr]

TRAVAIL ATTENDU
1. Découpe la maquette en blocs réutilisables (hero, texte-image, galerie,
   carte/tarifs, témoignages, CTA, contact…). Liste-les moi avant d'intégrer.
2. Crée le thème : config Tailwind (couleurs, fonts) extraite de la maquette.
3. Pour chaque bloc : template Twig + formulaire d'édition admin + preview.
4. Seed la page d'accueil avec le contenu réel de la maquette (fixtures).
5. SEO : meta title/description, Open Graph, JSON-LD adapté à l'activité
   ([Restaurant / LocalBusiness / Organization…]), sitemap, slugs propres.
6. Images : passe-les par la bibliothèque médias (WebP, lazy loading, alt).

PAGES
- [Accueil (one-page) / + Mentions légales / + autres pages…]

INSTRUCTIONS SPÉCIFIQUES
- [Ex : le menu du restaurant doit être éditable ligne par ligne]
- [Ex : formulaire de réservation → envoi email à contact@client.fr]
- [Ex : garder l'animation reveal au scroll]

CONTRAINTES
- Tout le contenu visible doit être éditable dans l'admin, sans toucher au code.
- Pas de Tailwind CDN : compile via le bundle du starter kit.
- Vérifie à la fin : page identique à la maquette + chaque bloc éditable
  et réordonnable dans l'admin.
```

---

## Conseils d'utilisation

- **Une maquette = un fichier HTML autonome** (comme amui.html / bivouac.html) : c'est le format idéal.
- Si un bloc doit exister en plusieurs variantes (ex : hero avec/sans image), précise-le dans les instructions spécifiques.
- Pour un site multipages, joins une maquette par gabarit de page ou décris les gabarits manquants.
- Les blocs créés pour un client restent dans le starter kit : le catalogue s'enrichit à chaque projet.
