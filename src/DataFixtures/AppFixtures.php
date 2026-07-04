<?php

namespace App\DataFixtures;

use App\Entity\Block;
use App\Entity\NavigationItem;
use App\Entity\Page;
use App\Entity\Post;
use App\Entity\PostCategory;
use App\Entity\Setting;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Site de démonstration : BIVOUAK CAFÉ (intégration de la maquette).
 * Connexion admin : admin@agence.fr / admin
 */
class AppFixtures extends Fixture
{
    public function __construct(private readonly UserPasswordHasherInterface $hasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $this->loadUser($manager);
        $this->loadSettings($manager);
        $this->loadNavigation($manager);
        $this->loadHomepage($manager);
        $this->loadLegalPage($manager);
        $this->loadPosts($manager);

        $manager->flush();
    }

    private function loadUser(ObjectManager $manager): void
    {
        $user = new User();
        $user->setEmail('admin@agence.fr')
            ->setRoles(['ROLE_ADMIN'])
            ->setPassword($this->hasher->hashPassword($user, 'admin'));
        $manager->persist($user);
    }

    private function loadSettings(ObjectManager $manager): void
    {
        $settings = [
            'site_name' => 'BIVOUAK CAFÉ',
            'tagline' => 'Concept restaurant au Marché du Lez',
            'logo_url' => 'https://www.bivouakcafe.fr/wp-content/uploads/2018/06/logo_accueil.png',
            'contact_email' => 'contact@bivouakcafe.fr',
            'mailer_from' => 'no-reply@bivouakcafe.fr',
            'phone' => '06 81 37 82 75',
            'address' => "1348 avenue de la Mer-Raymond Dugrand, 34000 Montpellier",
            'footer_text' => 'BIVOUAK CAFÉ — Marché du Lez, Montpellier',
            // ID de démo : rend la barre cookies visible dès l'installation.
            // Remplacer par le vrai ID GA4 du client (ou vider pour désactiver).
            'analytics_id' => 'G-DEMO000000',
        ];

        foreach ($settings as $key => $value) {
            $manager->persist((new Setting())->setKey($key)->setValue($value));
        }
    }

    private function loadNavigation(ObjectManager $manager): void
    {
        $header = [
            ['Le lieu', '#concept', false],
            ['La carte', '#menu', false],
            ['Workshop', '#workshop', false],
            ['Contact', '#contact', false],
            ['Réserver', '#contact', true],
        ];

        foreach ($header as $position => [$label, $url, $isButton]) {
            $manager->persist((new NavigationItem())
                ->setLabel($label)->setUrl($url)
                ->setLocation(NavigationItem::LOCATION_HEADER)
                ->setPosition($position)->setIsButton($isButton));
        }

        // Menu de la version anglaise
        $headerEn = [
            ['The place', '#concept', false],
            ['Menu', '#menu', false],
            ['Contact', '#contact', false],
            ['Book a table', '#contact', true],
        ];

        foreach ($headerEn as $position => [$label, $url, $isButton]) {
            $manager->persist((new NavigationItem())
                ->setLabel($label)->setUrl($url)->setLocale('en')
                ->setLocation(NavigationItem::LOCATION_HEADER)
                ->setPosition($position)->setIsButton($isButton));
        }

        $manager->persist((new NavigationItem())
            ->setLabel('Actualités')->setUrl('/actualites')
            ->setLocation(NavigationItem::LOCATION_FOOTER)->setPosition(0));
        $manager->persist((new NavigationItem())
            ->setLabel('Mentions légales')->setUrl('/mentions-legales')
            ->setLocation(NavigationItem::LOCATION_FOOTER)->setPosition(1));
    }

    private function loadHomepage(ObjectManager $manager): void
    {
        $page = new Page();
        $page->setTitle('BIVOUAK CAFÉ — Concept restaurant au Marché du Lez')
            ->setSlug('accueil')
            ->setIsHomepage(true)
            ->setStatus(Page::STATUS_PUBLISHED)
            ->setMetaTitle('BIVOUAK CAFÉ — Concept restaurant au Marché du Lez')
            ->setMetaDescription('Bivouak Café — Concept restaurant au Marché du Lez, Montpellier. Cantine, café racer, workshop & road trip. Ouvert 7j/7.')
            ->setOgImage('https://www.bivouakcafe.fr/wp-content/uploads/2022/04/wim-lippens-BC02_HD-0031-1920x1091.jpg')
            ->setStructuredData(json_encode([
                '@context' => 'https://schema.org',
                '@type' => 'Restaurant',
                'name' => 'Bivouak Café',
                'servesCuisine' => 'Méditerranéenne, tapas, cocktails',
                'telephone' => '+33681378275',
                'address' => [
                    '@type' => 'PostalAddress',
                    'streetAddress' => '1348 avenue de la Mer-Raymond Dugrand',
                    'addressLocality' => 'Montpellier',
                    'postalCode' => '34000',
                    'addressCountry' => 'FR',
                ],
                'openingHours' => 'Mo-Su 10:00-24:00',
                'url' => 'https://www.bivouakcafe.fr',
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

        $blocks = [
            ['hero', [
                'kicker' => 'Marché du Lez · Montpellier',
                'title' => 'Le BIVOUAK',
                'subtitle' => 'cantine · cafe racer · workshop · road trip',
                'tagline' => 'Base × Camp of Simple Pleasures',
                'text' => "Situé au Marché du Lez, le Bivouak Café est le point de départ de votre prochaine expédition culinaire à Montpellier. Cuisine généreuse en journée, cocktails au coucher du soleil.",
                'image' => 'https://www.bivouakcafe.fr/wp-content/uploads/2022/04/wim-lippens-BC02_HD-0031-1920x1091.jpg',
                'image_alt' => 'Ambiance Bivouak Café — Marché du Lez',
                'primary_label' => 'Voir le menu',
                'primary_link' => '#menu',
                'secondary_label' => 'Faire une réservation',
                'secondary_link' => '#contact',
            ]],
            ['cards', [
                'anchor' => 'concept',
                'kicker' => 'Notre concept',
                'title' => 'Bivouak Café : cantine – café racer – workshop',
                'intro' => "<em>Bivouac(k) n.m.</em> — Un bivouac est un campement rudimentaire permettant de passer la nuit en pleine nature. Une expérience à vivre en famille, entre amis, en van, à moto. Se retrouver dans un endroit chaleureux, simplement.",
                'intro_highlight' => 'Un bivouac(k) ne se décrit pas, il se vit.',
                'cards' => [
                    [
                        'image' => 'https://www.bivouakcafe.fr/wp-content/uploads/2022/04/wim-lippens-BC02_HD-0031-1024x683.jpg',
                        'image_alt' => 'Cuisine du Bivouak — cantine',
                        'title' => 'Cantine',
                        'text' => "Tout au long de la journée, notre cuisine rayonne pour vos pauses gourmandes en non-stop : tapas & mezze, formule du midi, carte du moment.",
                        'anchor' => '',
                    ],
                    [
                        'image' => 'https://www.bivouakcafe.fr/wp-content/uploads/2022/04/wim-lippens-BC02_HD-0031-1024x683.jpg',
                        'image_alt' => 'Bar cocktails — café racer',
                        'title' => 'Café racer',
                        'text' => "Au coucher du soleil, place aux cocktails et à l'ambiance road trip. Bar à cocktails, vins et boissons dans un décor chiné et décontracté.",
                        'anchor' => '',
                    ],
                    [
                        'image' => 'https://www.bivouakcafe.fr/wp-content/uploads/2026/01/terrasse-montpellier-bivouak.jpg',
                        'image_alt' => 'Terrasse Bivouak Café',
                        'title' => 'Workshop & road trip',
                        'text' => "Ateliers, store et expériences « road trip ». Réunion de travail ou tête-à-tête : le Bivouak est le camp de base idéal pour vos moments d'exception.",
                        'anchor' => 'workshop',
                    ],
                ],
                'footnote' => "Que ce soit dans notre intérieur au mobilier chiné ou sur notre terrasse ombragée, l'invitation est simple : profitez de l'instant.",
            ]],
            ['menu_list', [
                'anchor' => 'menu',
                'kicker' => 'La carte',
                'title' => 'Carte du moment',
                'intro' => 'Tapas · formule midi · cocktails · vins — carte complète sur <a href="https://www.bivouakcafe.fr/la-carte-du-moment/" class="underline decoration-sage/40 hover:text-forest" target="_blank" rel="noopener noreferrer">bivouakcafe.fr</a>',
                'categories' => [
                    [
                        'title' => 'Tapas & mezze',
                        'items' => "Assortiment mezze maison |\nPlanche à partager |\nTapas du marché |",
                        'note' => 'Carte évolutive selon les arrivages du Marché du Lez.',
                    ],
                    [
                        'title' => 'Formule du midi & enfant',
                        'items' => "Formule déjeuner |\nMenu enfant |",
                        'note' => '',
                    ],
                    [
                        'title' => 'Cocktails & boissons',
                        'items' => "Cocktails signature |\nVins (carte dédiée) |\nBières & softs |",
                        'note' => '',
                    ],
                ],
            ]],
            ['feature_band', [
                'kicker' => 'Le Marché du Lez',
                'title' => "Plus qu'un restaurant",
                'text' => "Bien plus qu'un restaurant bar à cocktails, c'est une escale chaleureuse pensée pour durer : cuisine créative, accueil attentionné et ambiance « camp de base » au cœur de Montpellier.",
                'bullets' => "calendar-days | Ouvert 7 jours / 7 — 10h00 – 00h00\nusers | Groupes & privatisation sur demande\nmap-pin | 1348 avenue de la Mer-Raymond Dugrand",
                'testimonials' => "Superbe endroit et excellent, très bonne adresse. | Annie Gosse\nCuisine généreuse et raffinée. Personnel au top. Je recommande fortement. | Mathilde Trelcat\nEntre amis ou en couple cet endroit est parfait. Personnel à l'écoute et très attentionné. | Aude Vallain",
            ]],
            ['cta_card', [
                'anchor' => '',
                'kicker' => 'Workshop & store',
                'title' => 'Road trip & ateliers',
                'text' => "Découvrez nos expériences « road trip », la boutique et les ateliers workshop — le Bivouak, c'est aussi une aventure hors assiette.",
                'links' => "Atelier workshop | https://www.bivouakcafe.fr/workshop/\nRoad trip | https://www.bivouakcafe.fr/road-trip/",
                'button_label' => 'Réserver',
                'button_link' => '#contact',
            ]],
            ['contact', [
                'anchor' => 'contact',
                'kicker' => 'Infos pratiques',
                'title' => 'Nous trouver',
                'place_name' => 'BIVOUAK CAFÉ — Marché du Lez',
                'address' => "1348 avenue de la Mer-Raymond Dugrand\n34000 Montpellier",
                'access_note' => "Dans l'enceinte du Marché du Lez : suivez les allées jusqu'à notre terrasse ombragée.",
                'hours' => '7 jours / 7 · 10h00 – 00h00',
                'phone' => '06 81 37 82 75',
                'phone_href' => '+33681378275',
                'call_label' => 'Appeler pour réserver',
                'form_title' => 'Réservation & groupes',
                'form_intro' => 'Demande de table ou privatisation — réponse rapide.',
                'subjects' => "Réservation table\nPrivatisation / groupe\nWorkshop\nAutre question",
                'recipient' => '',
                'map_embed' => 'https://maps.google.com/maps?q=43.592382,3.905457&z=15&output=embed',
            ]],
        ];

        foreach ($blocks as $position => [$type, $data]) {
            $block = new Block();
            $block->setType($type)->setData($data)->setPosition($position);
            $page->addBlock($block);
        }

        $manager->persist($page);
        $this->loadEnglishHomepage($manager, $page);
    }

    /** Version anglaise (réduite) de la homepage — démontre hreflang + sélecteur de langue */
    private function loadEnglishHomepage(ObjectManager $manager, Page $frenchHomepage): void
    {
        $page = new Page();
        $page->setTitle('BIVOUAK CAFÉ — Concept restaurant at Marché du Lez')
            ->setSlug('home')
            ->setLocale('en')
            ->setTranslationGroup($frenchHomepage->getTranslationGroup())
            ->setIsHomepage(true)
            ->setStatus(Page::STATUS_PUBLISHED)
            ->setMetaTitle('BIVOUAK CAFÉ — Concept restaurant at Marché du Lez, Montpellier')
            ->setMetaDescription('Bivouak Café — concept restaurant at the Marché du Lez, Montpellier. Canteen, café racer, workshop & road trip. Open 7 days a week.');

        $blocks = [
            ['hero', [
                'kicker' => 'Marché du Lez · Montpellier',
                'title' => 'Le BIVOUAK',
                'subtitle' => 'canteen · cafe racer · workshop · road trip',
                'tagline' => 'Base × Camp of Simple Pleasures',
                'text' => 'Located at the Marché du Lez, Bivouak Café is the base camp of your next culinary expedition in Montpellier. Generous cooking all day long, cocktails at sunset.',
                'image' => 'https://www.bivouakcafe.fr/wp-content/uploads/2022/04/wim-lippens-BC02_HD-0031-1920x1091.jpg',
                'image_alt' => 'Bivouak Café atmosphere — Marché du Lez',
                'primary_label' => 'See the menu',
                'primary_link' => '#menu',
                'secondary_label' => 'Book a table',
                'secondary_link' => '#contact',
            ]],
            ['contact', [
                'anchor' => 'contact',
                'kicker' => 'Practical info',
                'title' => 'Find us',
                'place_name' => 'BIVOUAK CAFÉ — Marché du Lez',
                'address' => "1348 avenue de la Mer-Raymond Dugrand\n34000 Montpellier, France",
                'access_note' => 'Inside the Marché du Lez: follow the alleys to our shaded terrace.',
                'hours' => 'Open 7/7 · 10am – midnight',
                'phone' => '+33 6 81 37 82 75',
                'phone_href' => '+33681378275',
                'call_label' => 'Call to book',
                'form_title' => 'Booking & groups',
                'form_intro' => 'Table request or private events — quick reply.',
                'subjects' => "Table booking\nPrivate event / group\nWorkshop\nOther",
                'recipient' => '',
                'map_embed' => 'https://maps.google.com/maps?q=43.592382,3.905457&z=15&output=embed',
            ]],
        ];

        foreach ($blocks as $position => [$type, $data]) {
            $block = new Block();
            $block->setType($type)->setData($data)->setPosition($position);
            $page->addBlock($block);
        }

        $manager->persist($page);
    }

    private function loadLegalPage(ObjectManager $manager): void
    {
        $page = new Page();
        $page->setTitle('Mentions légales')
            ->setSlug('mentions-legales')
            ->setStatus(Page::STATUS_PUBLISHED)
            ->setNoindex(true)
            ->setMetaTitle('Mentions légales — Bivouak Café');

        $block = new Block();
        $block->setType('rich_text')->setPosition(0)->setData([
            'kicker' => '',
            'title' => 'Mentions légales',
            'content' => "<h2>Éditeur du site</h2>\n<p>BIVOUAK CAFÉ — 1348 avenue de la Mer-Raymond Dugrand, 34000 Montpellier.</p>\n<h2>Hébergement</h2>\n<p>À compléter.</p>\n<h2>Données personnelles</h2>\n<p>Les informations transmises via le formulaire de contact sont utilisées uniquement pour répondre à votre demande.</p>",
        ]);
        $page->addBlock($block);

        $manager->persist($page);
        $this->loadNotFoundPage($manager);
    }

    /** Page 404 éditable : rendue automatiquement sur les URL introuvables */
    private function loadNotFoundPage(ObjectManager $manager): void
    {
        $page = new Page();
        $page->setTitle('Page introuvable')
            ->setSlug('erreur-404')
            ->setStatus(Page::STATUS_PUBLISHED)
            ->setNoindex(true)
            ->setMetaTitle('Page introuvable — Bivouak Café');

        $block = new Block();
        $block->setType('rich_text')->setPosition(0)->setData([
            'kicker' => 'Erreur 404',
            'title' => 'Vous vous êtes égaré en chemin',
            'content' => "<p>Cette page n'existe pas (ou plus). Comme au bivouac : on reprend la carte et on retrouve le sentier.</p>\n<p><a href=\"/\">← Retour au camp de base</a></p>",
        ]);
        $page->addBlock($block);

        $manager->persist($page);
    }

    private function loadPosts(ObjectManager $manager): void
    {
        $events = (new PostCategory())->setName('Événements')->setSlug('evenements');
        $kitchen = (new PostCategory())->setName('Côté cuisine')->setSlug('cote-cuisine');
        $manager->persist($events);
        $manager->persist($kitchen);

        $posts = [
            [
                'title' => 'Soirée jazz sur la terrasse tous les jeudis',
                'slug' => 'soiree-jazz-terrasse-jeudis',
                'category' => $events,
                'excerpt' => 'Dès 19h, un trio jazz accompagne vos cocktails au coucher du soleil. Entrée libre, réservation conseillée.',
                'content' => "<p>À partir de ce mois-ci, le Bivouak accueille chaque jeudi soir un trio jazz sur la terrasse ombragée. Une programmation locale, des standards revisités, et notre carte de cocktails signature pour accompagner.</p>\n<h2>Infos pratiques</h2>\n<ul>\n<li>Tous les jeudis dès 19h</li>\n<li>Entrée libre — table conseillée via le formulaire de réservation</li>\n<li>Carte tapas disponible en continu</li>\n</ul>",
                'cover' => 'https://www.bivouakcafe.fr/wp-content/uploads/2026/01/terrasse-montpellier-bivouak.jpg',
                'coverAlt' => 'La terrasse du Bivouak Café au coucher du soleil',
                'publishedAt' => '-3 days',
            ],
            [
                'title' => 'La carte d\'été est arrivée',
                'slug' => 'carte-ete-arrivee',
                'category' => $kitchen,
                'excerpt' => 'Nouveaux mezze, salades du marché et cocktails fruités : la carte d\'été s\'installe au Marché du Lez.',
                'content' => "<p>Le plein de fraîcheur pour la saison : notre chef a composé une carte d'été autour des arrivages du Marché du Lez.</p>\n<h2>Les nouveautés</h2>\n<ul>\n<li>Mezze houmous-grenade et labneh aux herbes</li>\n<li>Salade de pastèque, feta et menthe</li>\n<li>Cocktail signature « Road Trip » au citron vert</li>\n</ul>\n<p>La carte évolue chaque semaine selon le marché — suivez nos actualités !</p>",
                'cover' => 'https://www.bivouakcafe.fr/wp-content/uploads/2022/04/wim-lippens-BC02_HD-0031-1024x683.jpg',
                'coverAlt' => 'Assiettes de mezze de la carte d\'été',
                'publishedAt' => '-10 days',
            ],
            [
                'title' => 'Atelier workshop : entretenir son café racer',
                'slug' => 'atelier-workshop-cafe-racer',
                'category' => $events,
                'excerpt' => 'Un samedi par mois, notre atelier mécanique ouvre ses portes aux passionnés de motos vintage.',
                'content' => "<p>Le workshop du Bivouak, c'est aussi des ateliers pratiques. Prochain rendez-vous : les bases de l'entretien d'un café racer, animé par un mécano passionné.</p>\n<p>Places limitées à 8 participants — inscription via le formulaire de contact.</p>",
                'cover' => '',
                'coverAlt' => '',
                'publishedAt' => null, // brouillon : exemple de prévisualisation
            ],
        ];

        foreach ($posts as $data) {
            $post = (new Post())
                ->setTitle($data['title'])
                ->setSlug($data['slug'])
                ->setCategory($data['category'])
                ->setExcerpt($data['excerpt'])
                ->setContent($data['content'])
                ->setCoverImage($data['cover'] ?: null)
                ->setCoverAlt($data['coverAlt'] ?: null)
                ->setMetaDescription($data['excerpt']);

            if ($data['publishedAt']) {
                $post->setPublishedAt(new \DateTimeImmutable($data['publishedAt']));
            }

            $manager->persist($post);
        }
    }
}
