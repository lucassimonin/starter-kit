<?php

namespace App\EventListener;

use App\Entity\Page;
use App\Entity\Redirect;
use Doctrine\ORM\Event\OnFlushEventArgs;

/**
 * Quand le slug d'une page publiée change, crée automatiquement
 * une redirection 301 de l'ancienne URL vers la nouvelle.
 */
class SlugHistoryListener
{
    public function onFlush(OnFlushEventArgs $args): void
    {
        $em = $args->getObjectManager();
        $uow = $em->getUnitOfWork();

        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            if (!$entity instanceof Page) {
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

            $existing = $em->getRepository(Redirect::class)->findOneBy(['source' => '/'.$old]);
            if ($existing) {
                $existing->setTarget('/'.$new);
                $uow->recomputeSingleEntityChangeSet($em->getClassMetadata(Redirect::class), $existing);
                continue;
            }

            $redirect = new Redirect();
            $redirect->setSource('/'.$old)->setTarget('/'.$new)->setStatusCode(301);
            $em->persist($redirect);
            $uow->computeChangeSet($em->getClassMetadata(Redirect::class), $redirect);
        }
    }
}
