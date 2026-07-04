<?php

namespace App\Service;

use App\Entity\Page;

/**
 * Checklist SEO d'une page, affichée dans le panneau SEO du builder.
 * Chaque contrôle : ok (vert) / warning (orange).
 */
class SeoChecker
{
    /** @return list<array{ok: bool, label: string}> */
    public function check(Page $page): array
    {
        $checks = [];

        // Meta title
        $title = $page->getMetaTitle() ?: $page->getTitle();
        $length = mb_strlen($title);
        $checks[] = match (true) {
            $length < 10 => ['ok' => false, 'label' => 'Meta title trop court (moins de 10 caractères)'],
            $length > 65 => ['ok' => false, 'label' => sprintf('Meta title trop long (%d caractères, ≤ 65 recommandé)', $length)],
            default => ['ok' => true, 'label' => sprintf('Meta title correct (%d caractères)', $length)],
        };

        // Meta description
        $description = (string) $page->getMetaDescription();
        $length = mb_strlen($description);
        $checks[] = match (true) {
            '' === $description => ['ok' => false, 'label' => 'Meta description manquante'],
            $length < 50 => ['ok' => false, 'label' => sprintf('Meta description courte (%d caractères, 50–160 recommandé)', $length)],
            $length > 160 => ['ok' => false, 'label' => sprintf('Meta description longue (%d caractères, risque de troncature)', $length)],
            default => ['ok' => true, 'label' => sprintf('Meta description correcte (%d caractères)', $length)],
        };

        // Image de partage
        $checks[] = $page->getOgImage()
            ? ['ok' => true, 'label' => 'Image de partage (Open Graph) définie']
            : ['ok' => false, 'label' => 'Pas d\'image de partage — aperçu pauvre sur les réseaux sociaux'];

        // Contenu
        $enabledBlocks = $page->getBlocks()->filter(fn ($b) => $b->isEnabled());
        $checks[] = \count($enabledBlocks) > 0
            ? ['ok' => true, 'label' => sprintf('%d bloc(s) de contenu actif(s)', \count($enabledBlocks))]
            : ['ok' => false, 'label' => 'Aucun bloc actif — page vide'];

        // Textes alternatifs des images
        $missingAlts = $this->countMissingAlts($page);
        $checks[] = 0 === $missingAlts
            ? ['ok' => true, 'label' => 'Toutes les images ont un texte alternatif']
            : ['ok' => false, 'label' => sprintf('%d image(s) sans texte alternatif', $missingAlts)];

        // Données structurées
        if ($page->getStructuredData()) {
            json_decode($page->getStructuredData());
            $checks[] = JSON_ERROR_NONE === json_last_error()
                ? ['ok' => true, 'label' => 'Données structurées JSON-LD valides']
                : ['ok' => false, 'label' => 'JSON-LD invalide (erreur de syntaxe JSON)'];
        } else {
            $checks[] = ['ok' => false, 'label' => 'Pas de données structurées JSON-LD (recommandé pour le référencement local)'];
        }

        // Indexation
        if ($page->isNoindex()) {
            $checks[] = ['ok' => false, 'label' => 'Page exclue des moteurs de recherche (noindex actif)'];
        }

        return $checks;
    }

    public function score(Page $page): int
    {
        $checks = $this->check($page);
        $ok = \count(array_filter($checks, fn ($c) => $c['ok']));

        return (int) round(100 * $ok / max(1, \count($checks)));
    }

    /** Parcourt récursivement les données des blocs : image renseignée sans alt correspondant */
    private function countMissingAlts(Page $page): int
    {
        $missing = 0;
        foreach ($page->getBlocks() as $block) {
            if ($block->isEnabled()) {
                $missing += $this->scanArray($block->getData());
            }
        }

        return $missing;
    }

    /** @param array<string, mixed> $data */
    private function scanArray(array $data): int
    {
        $missing = 0;
        foreach ($data as $key => $value) {
            if (\is_array($value)) {
                foreach ($value as $item) {
                    if (\is_array($item)) {
                        $missing += $this->scanArray($item);
                    }
                }
                continue;
            }
            if ('image' === $key && \is_string($value) && '' !== $value) {
                $alt = $data['image_alt'] ?? $data['alt'] ?? '';
                if ('' === trim((string) $alt)) {
                    ++$missing;
                }
            }
        }

        return $missing;
    }
}
