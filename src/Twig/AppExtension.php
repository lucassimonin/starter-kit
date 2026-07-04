<?php

namespace App\Twig;

use App\Block\BlockRegistry;
use App\Entity\Block;
use App\Entity\ContactMessage;
use App\Entity\NavigationItem;
use App\Entity\Page;
use App\Entity\Post;
use App\Service\SettingsProvider;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension
{
    public function __construct(
        private readonly BlockRegistry $registry,
        private readonly SettingsProvider $settings,
        private readonly EntityManagerInterface $em,
        private readonly RequestStack $requestStack,
        private readonly string $appDefaultLocale,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('render_cms_block', $this->renderBlock(...), ['needs_environment' => true, 'is_safe' => ['html']]),
            new TwigFunction('setting', $this->settings->get(...)),
            new TwigFunction('nav_items', $this->navItems(...)),
            new TwigFunction('block_label', $this->blockLabel(...)),
            new TwigFunction('unread_messages', $this->unreadMessages(...)),
            new TwigFunction('page_path', $this->pagePath(...)),
            new TwigFunction('post_path', $this->postPath(...)),
            new TwigFunction('page_translations', $this->pageTranslations(...)),
        ];
    }

    public function getFilters(): array
    {
        return [
            // "Nom | Prix" par ligne -> [['left' => ..., 'right' => ...], ...]
            new TwigFilter('split_pairs', $this->splitPairs(...)),
        ];
    }

    public function renderBlock(Environment $twig, Block $block): string
    {
        if (!$block->isEnabled() || !$this->registry->has($block->getType())) {
            return '';
        }

        $type = $this->registry->get($block->getType());

        return $twig->render($type->getTemplate(), [
            'block' => $block,
            'data' => array_merge($type->getDefaultData(), $block->getData()),
        ]);
    }

    /** Liens de navigation dans la langue de la requête courante @return NavigationItem[] */
    public function navItems(string $location = NavigationItem::LOCATION_HEADER): array
    {
        $locale = $this->requestStack->getCurrentRequest()?->getLocale() ?? $this->appDefaultLocale;

        return $this->em->getRepository(NavigationItem::class)->findBy(
            ['location' => $location, 'locale' => $locale],
            ['position' => 'ASC'],
        );
    }

    /** Chemin front d'une page selon sa langue (/, /en, /contact, /en/contact) */
    public function pagePath(Page $page): string
    {
        $prefix = $page->getLocale() === $this->appDefaultLocale ? '' : '/'.$page->getLocale();

        if ($page->isHomepage()) {
            return $prefix ?: '/';
        }

        return $prefix.'/'.$page->getSlug();
    }

    /** Chemin front d'un article selon sa langue */
    public function postPath(Post $post): string
    {
        $prefix = $post->getLocale() === $this->appDefaultLocale ? '' : '/'.$post->getLocale();

        return $prefix.'/actualites/'.$post->getSlug();
    }

    /**
     * Traductions d'une page (elle-même incluse), pour hreflang et sélecteur de langue.
     *
     * @return Page[]
     */
    public function pageTranslations(Page $page, bool $onlyPublished = true): array
    {
        $criteria = ['translationGroup' => $page->getTranslationGroup()];
        if ($onlyPublished) {
            $criteria['status'] = Page::STATUS_PUBLISHED;
        }

        return $this->em->getRepository(Page::class)->findBy($criteria, ['locale' => 'ASC']);
    }

    /** Nombre de messages de contact non lus (badge admin) */
    public function unreadMessages(): int
    {
        return $this->em->getRepository(ContactMessage::class)->count(['isRead' => false]);
    }

    public function blockLabel(string $type): string
    {
        return $this->registry->has($type) ? $this->registry->get($type)->getLabel() : $type;
    }

    /** @return list<array{left: string, right: string}> */
    public function splitPairs(?string $text): array
    {
        $result = [];
        foreach (preg_split('/\r\n|\r|\n/', (string) $text) ?: [] as $line) {
            $line = trim($line);
            if ('' === $line) {
                continue;
            }
            $parts = array_map('trim', explode('|', $line, 2));
            $result[] = ['left' => $parts[0], 'right' => $parts[1] ?? ''];
        }

        return $result;
    }
}
