<?php

namespace App\EventListener;

use App\Entity\Page;
use App\Entity\Post;
use App\Entity\Redirect;
use Doctrine\ORM\Event\OnFlushEventArgs;

/**
 * Quand le slug d'une page ou d'un article change, crée automatiquement
 * une redirection 301 de l'ancienne URL vers la nouvelle.
 */
class SlugHistoryListener
{
    public function onFlush(OnFlushEventArgs $args): void
    {
        $em = $args->getObjectManager();
        $uow = $em->getUnitOfWork();

        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            $prefix = match (true) {
                $entity instanceof Page => '/',
                $entity instanceof Post => '/actualites/',
                default => null,
            };
            if (null === $prefix) {
                continue;
            }

            $changes = $uow->getEntityChangeSet($entity);
            if (!isset($changes['slug'])) {
                continue;
            }

            [$old, $new] = $changes['slug'];
            if (!$old || $old === $new) {
                continue;
            }

            $existing = $em->getRepository(Redirect::class)->findOneBy(['source' => $prefix.$old]);
            if ($existing) {
                $existing->setTarget($prefix.$new);
                $uow->recomputeSingleEntityChangeSet($em->getClassMetadata(Redirect::class), $existing);
                continue;
            }

            $redirect = new Redirect();
            $redirect->setSource($prefix.$old)->setTarget($prefix.$new)->setStatusCode(301);
            $em->persist($redirect);
            $uow->computeChangeSet($em->getClassMetadata(Redirect::class), $redirect);
        }
    }
}
