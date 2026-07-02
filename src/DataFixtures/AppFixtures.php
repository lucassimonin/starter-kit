<?php

namespace App\DataFixtures;

use App\Entity\Block;
use App\Entity\NavigationItem;
use App\Entity\Page;
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

        $manager->persist((new NavigationItem())
            ->setLabel('Mentions légales')->setUrl('/mentions-legales')
            ->setLocation(NavigationItem::LOCATION_FOOTER)->setPosition(0));
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
    }
}
