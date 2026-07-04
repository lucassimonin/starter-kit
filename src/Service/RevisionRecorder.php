<?php

namespace App\Service;

use App\Entity\Block;
use App\Entity\Page;
use App\Entity\PageRevision;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Enregistre un instantané des blocs d'une page AVANT une modification,
 * et conserve les 20 derniers. Le flush est laissé à l'appelant.
 */
class RevisionRecorder
{
    private const KEEP = 20;

    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    public function record(Page $page, string $label): void
    {
        $revision = new PageRevision();
        $revision->setPage($page)
            ->setLabel($label)
            ->setSnapshot($this->snapshot($page));

        $this->em->persist($revision);
        $this->prune($page);
    }

    /** Restaure les blocs d'une page depuis une révision (les blocs actuels sont remplacés) */
    public function restore(Page $page, PageRevision $revision): void
    {
        // Snapshot de l'état courant avant restauration (pour pouvoir revenir)
        $this->record($page, 'Avant restauration du '.$revision->getCreatedAt()->format('d/m/Y H:i'));

        foreach ($page->getBlocks()->toArray() as $block) {
            $page->removeBlock($block);
            $this->em->remove($block);
        }

        foreach ($revision->getSnapshot() as $blockData) {
            $block = new Block();
            $block->setType($blockData['type'])
                ->setPosition($blockData['position'])
                ->setEnabled($blockData['enabled'])
                ->setData($blockData['data']);
            $page->addBlock($block);
            $this->em->persist($block);
        }

        $page->setUpdatedAt(new \DateTimeImmutable());
    }

    /** @return list<array{type: string, position: int, enabled: bool, data: array<string, mixed>}> */
    private function snapshot(Page $page): array
    {
        return array_values(array_map(static fn (Block $block) => [
            'type' => $block->getType(),
            'position' => $block->getPosition(),
            'enabled' => $block->isEnabled(),
            'data' => $block->getData(),
        ], $page->getBlocks()->toArray()));
    }

    private function prune(Page $page): void
    {
        $old = $this->em->getRepository(PageRevision::class)->createQueryBuilder('r')
            ->where('r.page = :page')->setParameter('page', $page)
            ->orderBy('r.createdAt', 'DESC')
            ->setFirstResult(self::KEEP - 1)
            ->getQuery()->getResult();

        foreach ($old as $revision) {
            $this->em->remove($revision);
        }
    }
}
