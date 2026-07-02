<?php

namespace App\Twig;

use App\Block\BlockRegistry;
use App\Entity\Block;
use App\Entity\NavigationItem;
use App\Service\SettingsProvider;
use Doctrine\ORM\EntityManagerInterface;
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
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('render_cms_block', $this->renderBlock(...), ['needs_environment' => true, 'is_safe' => ['html']]),
            new TwigFunction('setting', $this->settings->get(...)),
            new TwigFunction('nav_items', $this->navItems(...)),
            new TwigFunction('block_label', $this->blockLabel(...)),
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

    /** @return NavigationItem[] */
    public function navItems(string $location = NavigationItem::LOCATION_HEADER): array
    {
        return $this->em->getRepository(NavigationItem::class)->findBy(
            ['location' => $location],
            ['position' => 'ASC'],
        );
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
